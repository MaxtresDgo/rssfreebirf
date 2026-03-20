<?php
defined('ABSPATH') || exit;

/*
Plugin Name: RSS Admin Extractor
Description: Extrae y publica noticias desde un feed RSS con imagen destacada, usando inteligencia artificial local (Ollama).
Version: 2.1
Author: TuNombre
*/

define('RSS_ADMIN_EXTRACTOR_DIR', plugin_dir_path(__FILE__));

require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/db.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/ollama.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/imagen.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'includes/cron.php';
require_once RSS_ADMIN_EXTRACTOR_DIR . 'admin/tareas.php';

// Hooks de activación y desactivación para registrar el cron personalizado
register_activation_hook(__FILE__, 'rss_admin_extractor_activar_plugin');
register_deactivation_hook(__FILE__, 'rss_admin_extractor_desactivar_plugin');

function rss_admin_extractor_activar_plugin()
{
    // Instalar tablas
    rss_admin_extractor_instalar();

    // Programar cron de verificación (cada 5 min)
    if (!wp_next_scheduled('rss_admin_extractor_verificar_hora')) {
        wp_schedule_event(time(), 'cada_5_minutos', 'rss_admin_extractor_verificar_hora');
    }
}

function rss_admin_extractor_desactivar_plugin()
{
    $timestamp = wp_next_scheduled('rss_admin_extractor_verificar_hora');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'rss_admin_extractor_verificar_hora');
    }
}





add_action('admin_menu', 'rss_admin_extractor_menu');

// Parche para forzar og:image en notas automáticas (Bypass para Yoast SEO)
add_action('wp_head', function() {
    if (is_single()) {
        $post_id = get_the_ID();
        if (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'full');
            if ($image_url) {
                echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
            }
        }
    }
}, 1);
