<?php
defined('ABSPATH') || exit;

function asignar_imagen_destacada($imagen_url, $post_id)
{
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $imagen_url = html_entity_decode(trim($imagen_url));
    if (empty($imagen_url) || empty($post_id))
        return false;

    // --- helper: crea nombre con extensión en base a MIME ---
    $resolver_nombre = function ($url, $mime) {
        $path = parse_url($url, PHP_URL_PATH);
        $base = $path ? basename($path) : 'imagen';
        $base = preg_replace('/\?.*$/', '', $base);
        $base = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $base);
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg'
        ];
        $ext = isset($map[strtolower($mime)]) ? $map[strtolower($mime)] : 'jpg';
        if (!preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $base)) {
            $base .= '.' . $ext;
        }
        return $base;
    };

    $tmp = false;
    $tmp_ok = false;
    $info = false;

    // --- SOLUCIÓN: Copiar el archivo localmente desde el disco si ya está en nuestro uploads ---
    $upload_dir = wp_upload_dir();
    $baseurl = $upload_dir['baseurl'];
    $basedir = $upload_dir['basedir'];

    if (strpos($imagen_url, $baseurl) === 0) {
        // En vez de descargarlo, buscamos dónde está en el disco
        $local_path = str_replace($baseurl, $basedir, $imagen_url);
        if (file_exists($local_path)) {
            $tmp = wp_tempnam($imagen_url);
            // Copiamos el archivo al temporal directamente (sin red web, sin cortafuegos)
            if (copy($local_path, $tmp)) {
                $tmp_ok = true;
                $info = @getimagesize($tmp);
                if ($info === false) {
                    $tmp_ok = false;
                    @unlink($tmp);
                }
            }
        }
    }

    // --- 1) Intento con download_url() (Si no era un archivo de nuestro propio servidor) ---
    if (!$tmp_ok) {
        $tmp = download_url($imagen_url);
        if (!is_wp_error($tmp)) {
            $tmp_ok = true;
            $info = @getimagesize($tmp);
            if ($info === false || empty($info['mime']) || stripos($info['mime'], 'image/') !== 0) {
                @unlink($tmp);
                $tmp_ok = false;
            }
        }
    }

    // --- 2) Fallback final con wp_remote_get y headers ---
    if (!$tmp_ok) {
        $args = [
            'timeout' => 20,
            'redirection' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer' => home_url('/'),
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ]
        ];
        $response = wp_remote_get($imagen_url, $args);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            if (!empty($body)) {
                $content_type = wp_remote_retrieve_header($response, 'content-type');
                $is_image_header = is_string($content_type) && stripos($content_type, 'image/') === 0;

                $tmp = wp_tempnam($imagen_url);
                if ($tmp) {
                    $bytes = file_put_contents($tmp, $body);
                    if ($bytes !== false && $bytes !== 0) {
                        $info = @getimagesize($tmp);
                        if ($info !== false || $is_image_header) {
                            $tmp_ok = true;
                            if ($info === false)
                                $info = ['mime' => $content_type];
                        } else {
                            @unlink($tmp);
                        }
                    }
                }
            }
        }
    }

    // Si fallan todos los métodos para obtener la imagen, abortamos
    if (!$tmp_ok || !$tmp)
        return false;

    // --- 3) Generar nombre e información para registrar el adjunto ---
    $mime = isset($info['mime']) ? strtolower($info['mime']) : 'image/jpeg';
    $file_array = [
        'name' => $resolver_nombre($imagen_url, $mime),
        'tmp_name' => $tmp,
    ];

    // --- 4) Registra en la librería de medios de WP formalmente ---
    $attach_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return false;
    }

    // --- 5) ¡Se asigna finalmente como Imagen Destacada! ---
    set_post_thumbnail($post_id, $attach_id);

    // Si se puede, colocar el texto alternativo
    if ($post = get_post($post_id)) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($post->post_title));
    }

    return $attach_id;
}
