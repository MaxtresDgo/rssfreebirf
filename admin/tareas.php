<?php
defined('ABSPATH') || exit;

function rss_admin_extractor_menu()
{
    // Menú principal
    add_menu_page(
        'RSS Extractor',
        'RSS Extractor',
        'manage_options',
        'rss-admin-extractor',
        'rss_admin_extractor_gestion_tareas',
        'dashicons-rss',
        20
    );

    // 1. Programar (Default)
    add_submenu_page(
        'rss-admin-extractor',
        'Programar Tarea',
        'Programar Tarea',
        'manage_options',
        'rss-admin-extractor',
        'rss_admin_extractor_gestion_tareas'
    );

    // 2. Listado de Tareas
    add_submenu_page(
        'rss-admin-extractor',
        'Tareas Existentes',
        'Tareas Existentes',
        'manage_options',
        'rss-listar-tareas',
        'rss_admin_extractor_listado_tareas'
    );

    // 3. Gestionar fuentes
    add_submenu_page(
        'rss-admin-extractor',
        'Gestionar Fuentes',
        'Gestionar Fuentes',
        'manage_options',
        'rss-gestionar-fuentes',
        'rss_admin_extractor_gestion_fuentes'
    );

    // 4. Ajustes (API Key)
    add_submenu_page(
        'rss-admin-extractor',
        'Ajustes',
        'Ajustes',
        'manage_options',
        'rss-ajustes',
        'rss_admin_extractor_ajustes'
    );
}

add_action('admin_enqueue_scripts', function ($hook) {
    if (!strpos($hook, 'rss-admin-extractor') && !strpos($hook, 'rss-gestionar-fuentes') && !strpos($hook, 'rss-listar-tareas') && !strpos($hook, 'rss-ajustes'))
        return;

    wp_enqueue_style(
        'rss_admin_styles',
        plugin_dir_url(__DIR__) . 'assets/css/rss-admin.css',
        [],
        '1.1'
    );

    wp_enqueue_script(
        'rss_ajax_script',
        plugin_dir_url(__DIR__) . 'assets/js/rss-ajax.js',
        ['jquery'],
        '1.1',
        true
    );

    wp_localize_script('rss_ajax_script', 'rss_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rss_ajax_nonce')
    ]);
});

// Ajax Handler
add_action('wp_ajax_rss_ejecutar_tarea_ajax', function () {
    check_ajax_referer('rss_ajax_nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('No autorizado');

    $tarea_id = intval($_POST['tarea_id']);
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $tarea = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $tarea_id));

    if (!$tarea)
        wp_send_json_error('Tarea no encontrada');

    require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
    $resultado = rss_admin_extractor_ejecutar_tarea($tarea);

    wp_send_json_success($resultado);
});

// Ajax Handler Paso 2: Contenido
add_action('wp_ajax_rss_procesar_contenido_ajax', function () {
    check_ajax_referer('rss_ajax_nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('No autorizado');

    $post_id = intval($_POST['post_id']);
    if (!$post_id)
        wp_send_json_error('ID de post no válido');

    require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
    $resultado = rss_admin_extractor_completar_contenido($post_id);

    wp_send_json_success($resultado);
});

/**
 * PÁGINA 1: PROGRAMAR TAREA (Formulario)
 */
function rss_admin_extractor_gestion_tareas()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $tabla_fuentes = $wpdb->prefix . 'rss_fuentes';

    if (isset($_POST['nueva_tarea'])) {
        check_admin_referer('rss_nueva_tarea_nonce');
        if (!current_user_can('manage_options'))
            return;

        $fuente_id = intval($_POST['fuente_id']);
        $fuente = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_fuentes WHERE id = %d", $fuente_id));
        if ($fuente) {
            $wpdb->insert($tabla, [
                'nombre_tarea' => sanitize_text_field($_POST['nombre_tarea']),
                'rss_url' => $fuente->url,
                'rss_limit' => intval($_POST['rss_limit']),
                'rss_hora' => isset($_POST['rss_hora']) ? sanitize_text_field($_POST['rss_hora']) : '',
                'rss_category_id' => intval($_POST['rss_category_id']),
                'rss_post_status' => sanitize_text_field($_POST['rss_post_status']),
                'rss_author_id' => intval($_POST['rss_author_id']),
                'periodico' => $fuente->periodico,
                'tipo_nota' => $fuente->tipo_nota
            ]);
            echo '<div class="flux-notification flux-success"><div class="flux-notification-content"><h4>Tarea Creada</h4><p>Ve a "Tareas Existentes" para verla.</p></div></div>';
        }
    }

    // Nota: la configuración global de hora CRON ahora se gestiona fuera de este formulario.

    $fuentes = $wpdb->get_results("SELECT * FROM $tabla_fuentes");
    include plugin_dir_path(__FILE__) . 'vistas/gestion-tareas.php';
}

/**
 * PÁGINA 2: LISTADO DE TAREAS (Acciones Masivas + Cron)
 */
function rss_admin_extractor_listado_tareas()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';

    if (isset($_GET['eliminar'])) {
        $wpdb->delete($tabla, ['id' => intval($_GET['eliminar'])]);
        echo '<div class="flux-notification flux-warning"><div class="flux-notification-content"><h4>Tarea Eliminada</h4></div></div>';
    }

    if (isset($_GET['eliminar_todas'])) {
        $wpdb->query("DELETE FROM $tabla");
        echo '<div class="flux-notification flux-warning"><div class="flux-notification-content"><h4>Todas las tareas eliminadas</h4></div></div>';
    }

    if (isset($_GET['probar'])) {
        $id = intval($_GET['probar']);
        $tarea = $wpdb->get_row("SELECT * FROM $tabla WHERE id = $id");
        if ($tarea) {
            require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
            $resultado = rss_admin_extractor_ejecutar_tarea($tarea, true);
            $mensaje = is_array($resultado) ? $resultado['mensaje'] : $resultado;
            echo '<div class="flux-notification flux-info"><div class="flux-notification-content"><h4>Resultado</h4><p>' . esc_html($mensaje) . '</p></div></div>';
        }
    }

    if (isset($_GET['probar_todas'])) {
        $todas_tareas = $wpdb->get_results("SELECT * FROM $tabla");
        if ($todas_tareas) {
            require_once plugin_dir_path(__FILE__) . '../core/rss-runner.php';
            foreach ($todas_tareas as $tarea) {
                rss_admin_extractor_ejecutar_tarea($tarea, true);
            }
            echo '<div class="flux-notification flux-info"><div class="flux-notification-content"><h4>Ejecución Finalizada</h4><p>Todas las tareas han sido procesadas.</p></div></div>';
        }
    }

    $tareas = $wpdb->get_results("SELECT * FROM $tabla");
    include plugin_dir_path(__FILE__) . 'vistas/listado-tareas.php';
}

/**
 * PÁGINA 3: CATÁLOGO DE FUENTES
 */
function rss_admin_extractor_gestion_fuentes()
{
    global $wpdb;
    $tabla_fuentes = $wpdb->prefix . 'rss_fuentes';

    if (isset($_POST['nueva_fuente'])) {
        $wpdb->insert($tabla_fuentes, [
            'periodico' => sanitize_text_field($_POST['periodico']),
            'tipo_nota' => sanitize_text_field($_POST['tipo_nota']),
            'url' => esc_url_raw($_POST['rss_url'])
        ]);
        echo '<div class="flux-notification flux-success"><div class="flux-notification-content"><h4>Fuente Guardada</h4></div></div>';
    }

    if (isset($_GET['eliminar_fuente'])) {
        $wpdb->delete($tabla_fuentes, ['id' => intval($_GET['eliminar_fuente'])]);
        echo '<div class="flux-notification flux-warning"><div class="flux-notification-content"><h4>Fuente Eliminada</h4></div></div>';
    }

    $fuentes = $wpdb->get_results("SELECT * FROM $tabla_fuentes");

    // Si no hay fuentes, intentar instalar/sembrar de nuevo
    if (empty($fuentes)) {
        rss_admin_extractor_instalar();
        $fuentes = $wpdb->get_results("SELECT * FROM $tabla_fuentes");
    }

    include plugin_dir_path(__FILE__) . 'vistas/gestion-fuentes.php';
}

/**
 * PÁGINA 4: AJUSTES (API Key)
 */
function rss_admin_extractor_ajustes()
{
    if (isset($_POST['guardar_ajustes'])) {
        check_admin_referer('rss_ajustes_nonce');
        if (!current_user_can('manage_options'))
            return;

        $api_key = sanitize_text_field($_POST['rss_mistral_api_key']);
        update_option('rss_mistral_api_key', $api_key);
        echo '<div class="flux-notification flux-success"><div class="flux-notification-content"><h4>Ajustes Guardados</h4><p>La API Key se ha actualizado correctamente.</p></div></div>';
    }

    $api_key = get_option('rss_mistral_api_key', '');
    include plugin_dir_path(__FILE__) . 'vistas/ajustes.php';
}
