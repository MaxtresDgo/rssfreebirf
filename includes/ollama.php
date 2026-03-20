<?php
defined('ABSPATH') || exit;

/* ===========================
   CONFIG GLOBAL ANTI-CUELGUES
=========================== */
if (!defined('OLLAMA_TIMEOUT_TITULO'))
    define('OLLAMA_TIMEOUT_TITULO', 25);
if (!defined('OLLAMA_TIMEOUT_CONTENIDO'))
    define('OLLAMA_TIMEOUT_CONTENIDO', 120);
if (!defined('OLLAMA_MAX_WORDS_INPUT'))
    define('OLLAMA_MAX_WORDS_INPUT', 350);

@set_time_limit(0);

/* ===========================
   HELPER: OBTENER API KEY
=========================== */
function rss_admin_extractor_get_api_key()
{
    $api_key = get_option('rss_mistral_api_key', '');
    if (empty($api_key)) {
        error_log('[RSS Extractor] API Key de Mistral no configurada. Ve a RSS Extractor > Ajustes para agregarla.');
    }
    return $api_key;
}

/* ===========================
   REESCRITURA DE TÍTULO
=========================== */
function reescribir_titulo_con_ollama($titulo)
{
    $api_key = rss_admin_extractor_get_api_key();
    if (empty($api_key)) return $titulo;

    $prompt = "Reescribe el siguiente título periodístico para que sea más atractivo y profesional. " .
        "No menciones nombres de periódicos, sitios web ni autores originales. " .
        "Devuelve únicamente el nuevo título, en español, sin comillas ni explicaciones.\n\n" .
        "Título original: $titulo";

    $body = json_encode([
        'model' => 'mistral-large-latest',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]);

    $response = wp_remote_post('https://api.mistral.ai/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => $body,
        'timeout' => OLLAMA_TIMEOUT_TITULO
    ]);

    if (is_wp_error($response)) {
        error_log('[Ollama Error Titulo] ' . $response->get_error_message());
        return $titulo;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['choices'][0]['message']['content'])) {
        $nuevo_titulo = trim($data['choices'][0]['message']['content']);
        // Limpieza profunda: quitamos asteriscos, comillas (normales y curvas) y espacios al inicio/final
        return preg_replace('/^[\s\*"' . "'" . '\x{201C}\x{201D}\x{2018}\x{2019}]+|[\s\*"' . "'" . '\x{201C}\x{201D}\x{2018}\x{2019}]+$/u', '', $nuevo_titulo);
    }

    return $titulo;
}

/* ===========================
   REESCRITURA DE CONTENIDO
=========================== */
function reescribir_contenido_con_ollama($contenido)
{
    $api_key = rss_admin_extractor_get_api_key();
    if (empty($api_key)) return $contenido;

    $contenido_original_clean = wp_strip_all_tags($contenido);

    // Reducimos input para evitar cuelgues
    $contenido_para_prompt = trim(
        wp_trim_words($contenido_original_clean, OLLAMA_MAX_WORDS_INPUT, '...')
    );

    $prompt = "Actúa como redactor periodístico profesional. Reescribe el siguiente texto en español (México/Iberoamérica), " .
        "con estilo claro, fluido y atractivo. Mantén la veracidad de los hechos. " .
        "IMPORTANTE: Elimina cualquier mención al nombre del periódico original, autores, periodistas, firmas, agencias (como EFE, Reuters, etc.) o sitios web. " .
        "El texto final debe tener al menos 350 palabras. " .
        "No agregues títulos, subtítulos, firmas ni introducciones tipo 'Aquí tienes'. " .
        "Devuelve únicamente el texto final.\n\n" .
        "Texto original:\n$contenido_para_prompt";

    $body = json_encode([
        'model' => 'mistral-large-latest',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]);

    $response = wp_remote_post('https://api.mistral.ai/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => $body,
        'timeout' => OLLAMA_TIMEOUT_CONTENIDO
    ]);

    if (is_wp_error($response)) {
        error_log('[Ollama Error Contenido] ' . $response->get_error_message());
        return $contenido;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']);
    }

    return $contenido;
}
