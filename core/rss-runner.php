<?php
defined('ABSPATH') || exit;

function rss_admin_extractor_ejecutar_tarea($tarea)
{
    $feed_url = esc_url_raw($tarea->rss_url);
    $limite = max(0, intval($tarea->rss_limit));

    // 1) Descargar XML
    $resp = wp_remote_get($feed_url, [
        'timeout' => 25,
        'headers' => ['Accept' => 'application/xml,text/xml,*/*;q=0.9'],
    ]);
    if (is_wp_error($resp)) {
        error_log("RSS ERROR: " . $resp->get_error_message());
        return "Error: " . $resp->get_error_message();
    }

    require_once plugin_dir_path(__FILE__) . '../includes/imagen.php';

    $body = wp_remote_retrieve_body($resp);
    if (!$body)
        return "Error: Cuerpo de respuesta vacío.";

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if ($xml === false) {
        error_log("RSS ERROR XML");
        return "Error: Formato XML inválido.";
    }

    // 2) Extraer nodos <noticia> o <item> (Soporte básico RSS estándar)
    $items = [];
    $is_standard_rss = false;

    if ($xml->xpath('//noticia')) {
        $items = $xml->xpath('//noticia');
    } elseif ($xml->xpath('//item')) {
        $items = $xml->xpath('//item');
        $is_standard_rss = true;
    }

    if (!$items)
        return "No se encontraron noticias en el feed.";

    global $wpdb;

    // Nombres de medios a ocultar: los del catálogo de fuentes + agencias comunes
    $medios = array_merge(
        (array) $wpdb->get_col("SELECT DISTINCT periodico FROM {$wpdb->prefix}rss_fuentes"),
        ['EFE', 'Reuters', 'AP', 'AFP', 'Notimex', 'Europa Press', 'Infobae', 'Milenio', 'Reforma', 'La Jornada', 'El Universal']
    );

    $importados = 0;
    $saltados = 0;

    foreach ($items as $n) {

        if ($limite > 0 && $importados >= $limite)
            break;

        if ($is_standard_rss) {
            $titulo_original = trim((string) ($n->title ?? ''));
            // content:encoded trae el artículo completo; description suele ser solo un extracto
            $completo = trim((string) $n->children('http://purl.org/rss/1.0/modules/content/')->encoded);
            $contenido_original = $completo !== '' ? $completo : trim((string) $n->description);
            $imagen_url = '';
            $url_fuente = trim((string) ($n->link ?? ''));
            $autores = '';
        } else {
            $titulo_original = trim((string) ($n->titulo ?? ''));
            $contenido_original = trim((string) ($n->texto ?? ''));
            $imagen_url = trim((string) ($n->imagen_url ?? ''));
            $url_fuente = trim((string) ($n->url ?? ''));
            $autores = trim((string) ($n->autores ?? ''));
        }

        if (!$titulo_original || !$contenido_original)
            continue;

        $hash = hash('sha256', $titulo_original . '|' . $contenido_original);

        // duplicados
        $ya = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_original_hash' AND meta_value = %s LIMIT 1",
            $hash
        ));

        if ($ya) {
            $saltados++;
            continue;
        }

        // Quita solo líneas de firma completas (ej. "Por Juan Pérez", "<p>Con información de EFE</p>").
        // Anclado al inicio de línea para no cortar palabras como "por", "jefe" o "jornada" dentro del texto.
        $contenido = preg_replace(
            '/(^|>)\s*(Con información de|Por|Escrito por|Fuente|Redacción)\b[^\n<]{0,80}(?=<|$)/mu',
            '$1',
            $contenido_original
        );
        $contenido = trim(rss_quitar_periodicos($contenido, $medios));
        $titulo = trim(rss_quitar_periodicos($titulo_original, $medios));

        // Insertar post
        $post_id = wp_insert_post([
            'post_title' => wp_strip_all_tags($titulo),
            'post_content' => wp_kses_post($contenido),
            'post_status' => $tarea->rss_post_status,
            'post_category' => [intval($tarea->rss_category_id)],
            'post_author' => intval($tarea->rss_author_id),
        ], true);

        if (is_wp_error($post_id))
            continue;

        update_post_meta($post_id, '_original_hash', $hash);

        if ($url_fuente)
            update_post_meta($post_id, '_source_url', esc_url_raw($url_fuente));

        if ($autores)
            update_post_meta($post_id, '_source_authors', sanitize_text_field($autores));

        if ($imagen_url)
            asignar_imagen_destacada(esc_url_raw($imagen_url), $post_id);

        $importados++;
    }

    $res = "Finalizado: $importados importados, $saltados saltados.";
    error_log("RSS RESULTADO: $res");
    return $res;
}

/**
 * Quita atribuciones a medios: "(EFE)", "según El Universal", ", en entrevista con Excélsior,",
 * "De acuerdo con Infobae, la ..." -> "La ...".
 * ponytail: basado en reglas; una mención suelta sin frase de atribución ("Estudios Universal") se respeta a propósito.
 */
function rss_quitar_periodicos($texto, $medios)
{
    // Variantes sin acento ("Excelsior", "Cronica") y sin duplicados/vacíos
    $sin_acento = str_replace(['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'], $medios);
    $medios = array_filter(array_unique(array_map('trim', array_merge($medios, $sin_acento))));
    if (!$medios)
        return $texto;

    // Nombres más largos primero para que "La Jornada" gane sobre "Jornada"
    usort($medios, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
    $n = implode('|', array_map(fn($m) => preg_quote($m, '/'), $medios));

    // El nombre respeta mayúsculas (evita "marca", "universal", "crónica" como palabras comunes)
    $medio = "(?i:(?:el |la )?(?:diario|periódico|periodico|revista|agencia|portal|sitio|medio|cadena) )?(?:El |La )?(?:$n)\b(?: (?:MX|México|Mexico|Deportes|Noticias|Digital))?";
    $atrib = "(?i:según|de acuerdo con|de acuerdo a|con información de|informó|informa|reportó|reporta|publicó|publica|consignó|detalló|en entrevista con|en entrevista para|en declaraciones a|en declaraciones para|dijo a|declaró a|consultado por|citado por|vía)";

    // "(EFE)", "(Reuters).-"
    $texto = preg_replace("/\s*\((?:$n)\)(?:\s*\.?-)?/u", '', $texto);
    // ", según X," / ", informó X." -> se elimina el inciso
    $texto = preg_replace("/,\s*$atrib\s+$medio\s*(?:,|(?=[.;:]))/u", '', $texto);
    // "Según X, la policía ..." al inicio de oración/párrafo -> "La policía ..."
    $texto = preg_replace_callback(
        "/(^|[.!?]\s+|>\s*)$atrib\s+$medio\s*,\s*(\p{L})/mu",
        fn($m) => $m[1] . mb_strtoupper($m[2]),
        $texto
    );
    // "... según X." sin coma
    $texto = preg_replace("/\s+$atrib\s+$medio(?=\s*[.;:])/u", '', $texto);

    return $texto;
}
