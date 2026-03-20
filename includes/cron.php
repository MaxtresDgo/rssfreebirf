<?php
defined('ABSPATH') || exit;

// Agregar intervalo personalizado de 5 minutos (para que el cron corra frecuente)
add_filter('cron_schedules', function ($schedules) {
    $schedules['cada_5_minutos'] = array(
        'interval' => 300, // 5 minutos = 300 segundos
        'display' => 'Cada 5 minutos'
    );
    return $schedules;
});

// Hook para verificar si es hora de ejecutar tareas según hora configurada
add_action('rss_admin_extractor_verificar_hora', function () {
    // Ejecutamos la acción principal cada vez que corre este hook (cada 5 minutos)
    do_action('rss_admin_extractor_ejecutar_cron');
});

// 5) Acción que ejecuta todas las tareas
add_action('rss_admin_extractor_ejecutar_cron', 'rss_admin_extractor_ejecutar_todas_las_tareas');

function rss_admin_extractor_ejecutar_todas_las_tareas()
{
    // Evitar ejecuciones duplicadas muy seguidas (bloqueo de 4 min)
    if (get_transient('rss_ejecutando_cron')) {
        return;
    }
    set_transient('rss_ejecutando_cron', true, 30 * MINUTE_IN_SECONDS);

    error_log('[RSS Cron] Iniciando ejecución de tareas programadas');

    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $tareas = $wpdb->get_results("SELECT * FROM $tabla");

    if (!$tareas) {
        error_log('[RSS Cron] No se encontraron tareas para ejecutar.');
        delete_transient('rss_ejecutando_cron');
        return;
    }

    require_once plugin_dir_path(__FILE__) . '/../core/rss-runner.php';

    $zona_horaria = wp_timezone();
    $ahora = new DateTime('now', $zona_horaria);
    $ts_actual = $ahora->getTimestamp();
    $hora_global = get_option('rss_cron_hora', '07:00');

    foreach ($tareas as $tarea) {
        $debe_ejecutar = false;

        $hoy_str = $ahora->format('Y-m-d');
        $transient_name = 'rss_t_ok_' . $tarea->id . '_' . $hoy_str;
        if (get_transient($transient_name)) {
            continue; // Ya se ejecutó hoy
        }

        $hora_a_evaluar = !empty($tarea->rss_hora) ? $tarea->rss_hora : $hora_global;

        // Asegurar que la hora está en formato H:i y evitar fallos por "19:38:00"
        $partes = explode(':', $hora_a_evaluar);
        $h = str_pad($partes[0] ?? '00', 2, '0', STR_PAD_LEFT);
        $m = str_pad($partes[1] ?? '00', 2, '0', STR_PAD_LEFT);
        $hora_limpia = "$h:$m";

        $fecha_tarea = DateTime::createFromFormat('H:i', $hora_limpia, $zona_horaria);
        if ($fecha_tarea) {
            $fecha_tarea->setDate($ahora->format('Y'), $ahora->format('m'), $ahora->format('d'));
            $fecha_tarea->setTime($fecha_tarea->format('H'), $fecha_tarea->format('i'), 0);

            $ts_tarea = $fecha_tarea->getTimestamp();
            
            // Si la hora actual ya superó o es igual a la hora de la tarea, la ejecutamos.
            if ($ts_actual >= $ts_tarea) {
                $debe_ejecutar = true;
            } else {
                error_log(sprintf("[RSS Cron] Tarea %d programada para %s (ts=%d). Aún no es la hora actual %s (ts=%d).", $tarea->id, $hora_limpia, $ts_tarea, $ahora->format('H:i:00'), $ts_actual));
            }
        } else {
            error_log("[RSS Cron] Error parsing time block para la tarea ID {$tarea->id} con hora original: " . $hora_a_evaluar);
        }

        if ($debe_ejecutar) {
            // Marcamos como ejecutado HOY inmediatamente ANTES de empezar el proceso pesado.
            // Esto evita que si el proceso tarda mucho o da timeout, el cron lo reinicie a los 5 min.
            set_transient($transient_name, true, DAY_IN_SECONDS);

            error_log("[RSS Cron] Ejecutando tarea ID: {$tarea->id} ({$tarea->nombre_tarea})");
            rss_admin_extractor_ejecutar_tarea($tarea);
        }
    }

    error_log('[RSS Cron] Ejecución completada.');
    delete_transient('rss_ejecutando_cron');
}
