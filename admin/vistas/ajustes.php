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
        <h1 class="flux-title">Ajustes</h1>
    </div>

    <div class="flux-card">
        <div class="flux-card-header">
            <div class="flux-card-icon"><span class="dashicons dashicons-admin-network"></span></div>
            <h2 class="flux-card-title">API Key de Mistral AI</h2>
        </div>
        <form method="post">
            <?php wp_nonce_field('rss_ajustes_nonce'); ?>
            <div class="flux-form-grid">
                <div class="flux-form-field" style="grid-column: 1 / -1;">
                    <label for="rss_mistral_api_key">API Key</label>
                    <input name="rss_mistral_api_key" id="rss_mistral_api_key" type="password"
                        value="<?= esc_attr($api_key) ?>"
                        placeholder="Introduce tu API Key de Mistral AI"
                        style="font-family: monospace;">
                    <p style="margin-top: 8px; color: var(--text-medium); font-size: 13px;">
                        Puedes obtener tu API Key en
                        <a href="https://console.mistral.ai/api-keys" target="_blank" style="color: var(--primary);">console.mistral.ai</a>.
                        La key se almacena de forma segura en la base de datos de WordPress.
                    </p>
                </div>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" name="guardar_ajustes" class="flux-button" style="width: auto; padding: 12px 30px;">
                    <span class="dashicons dashicons-saved"></span> Guardar Ajustes
                </button>
            </div>
        </form>

        <?php if (!empty($api_key)): ?>
            <div style="margin-top: 20px; padding: 12px 16px; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                <span class="dashicons dashicons-yes-alt" style="color: #16a34a; margin-right: 8px;"></span>
                <span style="color: #166534; font-weight: 600;">API Key configurada</span>
                <span style="color: #166534;"> — terminación: ...<?= esc_html(substr($api_key, -4)) ?></span>
            </div>
        <?php else: ?>
            <div style="margin-top: 20px; padding: 12px 16px; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
                <span class="dashicons dashicons-warning" style="color: #dc2626; margin-right: 8px;"></span>
                <span style="color: #991b1b; font-weight: 600;">API Key no configurada</span>
                <span style="color: #991b1b;"> — las tareas no podrán reescribir contenido con IA.</span>
            </div>
        <?php endif; ?>
    </div>
</div>
