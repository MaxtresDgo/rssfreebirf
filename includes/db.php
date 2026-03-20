<?php
defined('ABSPATH') || exit;

function rss_admin_extractor_instalar()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'rss_tareas';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id INT NOT NULL AUTO_INCREMENT,
        nombre_tarea VARCHAR(255) DEFAULT '',
        rss_url TEXT NOT NULL,
        rss_limit INT DEFAULT 3,
        rss_category_id INT,
        rss_hora VARCHAR(5) DEFAULT '',
        rss_post_status VARCHAR(20) DEFAULT 'draft',
        rss_author_id BIGINT DEFAULT 0,
        periodico VARCHAR(255) DEFAULT '',
        tipo_nota VARCHAR(255) DEFAULT '',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    $tabla_fuentes = $wpdb->prefix . 'rss_fuentes';
    $sql_fuentes = "CREATE TABLE $tabla_fuentes (
        id INT NOT NULL AUTO_INCREMENT,
        periodico VARCHAR(255) NOT NULL,
        tipo_nota VARCHAR(255) NOT NULL,
        url TEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql_fuentes);

    // Semilla: Agregar fuentes de ejemplo
    $bloque_existente = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_fuentes");

    if ($bloque_existente == 0) {

        // Crónica
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/cronica_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/cronica_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Crónica',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/cronica_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Noventagrados
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Noventagrados',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/noventagrados_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Noventagrados',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/noventagrados_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Noventagrados',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/noventagrados_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Netnoticias
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Netnoticias',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/netnoticias_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Netnoticias',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/netnoticias_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Netnoticias',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/netnoticias_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Excélsior
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Excélsior',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/excelsior_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Excélsior',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/excelsior_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Excélsior',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/excelsior_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Forbes
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Forbes',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/forbes_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Forbes',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/forbes_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Forbes',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/forbes_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Marca
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Marca',
            'tipo_nota' => 'Fútbol',
            'url' => 'https://xml.maxtres.org/files/marca_futbol.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Unánimo
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Unánimo',
            'tipo_nota' => 'Fútbol',
            'url' => 'https://xml.maxtres.org/files/unanimo_futbol.xml?b2b_token=tokenDeEjemplo'
        ]);

        // El Universal
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Universal',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/universal_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Universal',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/universal_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Universal',
            'tipo_nota' => 'Nacional',
            'url' => 'https://xml.maxtres.org/files/universal_nacional.xml?b2b_token=tokenDeEjemplo'
        ]);

        // La Jornada
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Jornada',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/jornada_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Jornada',
            'tipo_nota' => 'Mundo',
            'url' => 'https://xml.maxtres.org/files/jornada_mundo.xml?b2b_token=tokenDeEjemplo'
        ]);
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Jornada',
            'tipo_nota' => 'Capital',
            'url' => 'https://xml.maxtres.org/files/jornada_capital.xml?b2b_token=tokenDeEjemplo'
        ]);

        // SDP
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'SDP',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/sdp_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);

        // Infobae
        $wpdb->insert($tabla_fuentes, [
            'periodico' => 'Infobae',
            'tipo_nota' => 'Espectáculos',
            'url' => 'https://xml.maxtres.org/files/infobae_espectaculos.xml?b2b_token=tokenDeEjemplo'
        ]);
    }
}
