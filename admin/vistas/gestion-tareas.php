<div class="flux-container">
    <div class="flux-header">
        <div class="flux-logo">
            <?php
            $logo_img = plugin_dir_url(__FILE__) . '../../assets/logo.png';
            if (file_exists(plugin_dir_path(__FILE__) . '../../assets/logo.png')) {
                echo '<img src="' . esc_url($logo_img) . '" alt="Logo" class="flux-logo-img">';
            } else {
                echo '<span class="dashicons dashicons-rss"></span>';
            }
            ?>
        </div>
        <h1 class="flux-title">Programar Nueva Tarea</h1>
    </div>

    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
            <h2 class="flux-card-title">Configurar Extracción</h2>
        </div>
        <form method="post">
            <?php wp_nonce_field('rss_nueva_tarea_nonce'); ?>

            <div class="flux-form-grid">
                <div class="flux-form-field">
                    <label for="nombre_tarea">Nombre de la tarea</label>
                    <input name="nombre_tarea" id="nombre_tarea" type="text" required
                        placeholder="Ej: Noticias de tecnología">
                </div>
                <div class="flux-form-field">
                    <label for="fuente_id">Seleccionar Fuente del Catálogo</label>
                    <select name="fuente_id" id="fuente_id" required>
                        <option value="">-- Elige una fuente --</option>
                        <?php foreach ($fuentes as $f): ?>
                            <option value="<?= esc_attr($f->id) ?>"><?= esc_html($f->periodico) ?> /
                                <?= esc_html($f->tipo_nota) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flux-form-field">
                    <label for="rss_limit">Límite de entradas</label>
                    <input name="rss_limit" id="rss_limit" type="number" value="3" min="1">
                </div>
                <div class="flux-form-field">
                    <label for="rss_hora">Hora de ejecución (por tarea, opcional)</label>
                    <input name="rss_hora" id="rss_hora" type="time" value="">
                </div>
                <div class="flux-form-field">
                    <label for="rss_category_id">Categoría</label>
                    <select name="rss_category_id" id="rss_category_id">
                        <?php
                        foreach (get_categories(['hide_empty' => false]) as $cat) {
                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="flux-form-field">
                    <label for="rss_author_id">Asignar Autor</label>
                    <select name="rss_author_id" id="rss_author_id">
                        <?php
                        foreach (get_users(['orderby' => 'display_name']) as $usuario) {
                            echo '<option value="' . esc_attr($usuario->ID) . '">' . esc_html($usuario->display_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="flux-form-field">
                    <label for="rss_post_status">Estado del post</label>
                    <select name="rss_post_status" id="rss_post_status">
                        <option value="draft">Borrador</option>
                        <option value="publish">Publicado</option>
                    </select>
                </div>
                
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" name="nueva_tarea" class="flux-button" style="width: auto; padding: 12px 30px;">
                    <span class="dashicons dashicons-plus-alt"></span> Guardar Nueva Tarea
                </button>
            </div>
        </form>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="?page=rss-listar-tareas" class="flux-button" style="background: var(--accent);">
            <span class="dashicons dashicons-list-view"></span> Ver tareas existentes
        </a>
    </div>
</div>