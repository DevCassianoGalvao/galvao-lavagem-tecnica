<?php

$settingsValues = [];
$adminUsers = [];
$adminLogs = [];
$loginHistory = [];
$settingsError = null;

try {
    $pdo = Connection::get($GLOBALS['config']);
    $settingsValues = (new SettingsService($pdo))->all(true);
    $adminUsers = $pdo->query(
        'SELECT id, name, email, phone, role, status, last_login_at, created_at
         FROM users
         WHERE deleted_at IS NULL
         ORDER BY FIELD(role, "owner", "admin", "manager", "operator", "commercial", "viewer"), name'
    )->fetchAll();
    $adminLogs = $pdo->query(
        'SELECT level, channel, action, message, ip_address, created_at
         FROM logs
         ORDER BY created_at DESC
         LIMIT 14'
    )->fetchAll();
    $loginHistory = $pdo->query(
        'SELECT lh.status, lh.email, lh.ip_address, lh.failure_reason, lh.created_at, u.name
         FROM login_history lh
         LEFT JOIN users u ON u.id = lh.user_id
         ORDER BY lh.created_at DESC
         LIMIT 12'
    )->fetchAll();
} catch (Throwable $exception) {
    $settingsError = 'Banco ainda nao disponivel para leitura das configuracoes.';
}

$setting = fn (string $key): string => (string) ($settingsValues[$key] ?? '');
$roles = ['owner' => 'Proprietario', 'admin' => 'Admin', 'manager' => 'Gestor', 'operator' => 'Operador', 'commercial' => 'Comercial', 'viewer' => 'Visualizacao'];
$statuses = ['active' => 'Ativo', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado'];
?>

<div class="settings-page">
    <section class="card settings-hero">
        <div>
            <span class="eyebrow">Sistema</span>
            <h2>Central de configuracoes premium</h2>
            <p>Branding, scripts, integracoes, usuarios, IA e auditoria em um unico painel operacional.</p>
        </div>
        <div class="settings-status" data-settings-status>
            <strong>Seguro</strong>
            <span>CSRF, permissoes e logs ativos</span>
        </div>
    </section>

    <?php if ($settingsError): ?>
        <div class="security-alert"><?= e($settingsError); ?></div>
    <?php endif; ?>

    <nav class="settings-tabs card" aria-label="Secoes de configuracoes">
        <button class="is-active" type="button" data-settings-tab="branding">Branding</button>
        <button type="button" data-settings-tab="scripts">Scripts</button>
        <button type="button" data-settings-tab="integrations">Integracoes</button>
        <button type="button" data-settings-tab="users">Usuarios</button>
        <button type="button" data-settings-tab="ai">IA</button>
        <button type="button" data-settings-tab="system">Sistema</button>
        <button type="button" data-settings-tab="logs">Logs</button>
    </nav>

    <section class="settings-panel is-active" data-settings-panel="branding">
        <form class="card settings-card" data-settings-form>
            <input type="hidden" name="action" value="save_settings">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">Identidade</span>
                    <h3>Branding da plataforma</h3>
                </div>
                <button class="btn btn--primary" type="submit">Salvar branding</button>
            </header>

            <div class="settings-grid">
                <label class="field">
                    <span>Logo</span>
                    <input class="input" name="settings[branding.logo_url]" value="<?= e($setting('branding.logo_url')); ?>" placeholder="../assets/images/logo.svg">
                </label>
                <label class="field">
                    <span>Favicon</span>
                    <input class="input" name="settings[branding.favicon_url]" value="<?= e($setting('branding.favicon_url')); ?>" placeholder="../assets/images/favicon.png">
                </label>
                <label class="field">
                    <span>Dourado principal</span>
                    <input class="input" type="color" name="settings[branding.primary_color]" value="<?= e($setting('branding.primary_color') ?: '#C8A95B'); ?>">
                </label>
                <label class="field">
                    <span>Dourado destaque</span>
                    <input class="input" type="color" name="settings[branding.accent_color]" value="<?= e($setting('branding.accent_color') ?: '#D4AF37'); ?>">
                </label>
                <label class="field">
                    <span>Nome publico</span>
                    <input class="input" name="settings[branding.company_name]" value="<?= e($setting('branding.company_name')); ?>">
                </label>
                <label class="field">
                    <span>Texto curto da marca</span>
                    <input class="input" name="settings[branding.hero_badge]" value="<?= e($setting('branding.hero_badge')); ?>">
                </label>
            </div>
        </form>
    </section>

    <section class="settings-panel" data-settings-panel="scripts">
        <form class="card settings-card" data-settings-form>
            <input type="hidden" name="action" value="save_settings">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">Landing page</span>
                    <h3>Pixels, analytics e scripts</h3>
                    <p>Os scripts salvos aqui sao injetados automaticamente na landing com IDs validados.</p>
                </div>
                <button class="btn btn--primary" type="submit">Salvar scripts</button>
            </header>

            <div class="settings-grid">
                <label class="field">
                    <span>Meta Pixel ID</span>
                    <input class="input" name="settings[landing.meta_pixel_id]" value="<?= e($setting('landing.meta_pixel_id')); ?>" placeholder="123456789012345">
                </label>
                <label class="field">
                    <span>Google Analytics</span>
                    <input class="input" name="settings[landing.ga_measurement_id]" value="<?= e($setting('landing.ga_measurement_id')); ?>" placeholder="G-XXXXXXXXXX">
                </label>
                <label class="field">
                    <span>Google Tag Manager</span>
                    <input class="input" name="settings[landing.gtm_id]" value="<?= e($setting('landing.gtm_id')); ?>" placeholder="GTM-XXXXXXX">
                </label>
                <label class="field field--wide">
                    <span>Scripts customizados no head</span>
                    <textarea class="textarea settings-code" name="settings[landing.custom_head_scripts]" spellcheck="false"><?= e($setting('landing.custom_head_scripts')); ?></textarea>
                </label>
                <label class="field field--wide">
                    <span>Scripts customizados antes do fechamento do body</span>
                    <textarea class="textarea settings-code" name="settings[landing.custom_body_scripts]" spellcheck="false"><?= e($setting('landing.custom_body_scripts')); ?></textarea>
                </label>
            </div>
        </form>
    </section>

    <section class="settings-panel" data-settings-panel="integrations">
        <form class="card settings-card" data-settings-form>
            <input type="hidden" name="action" value="save_settings">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">APIs</span>
                    <h3>Integracoes externas</h3>
                    <p>Chaves privadas ficam ocultas na interface e salvas como configuracoes internas.</p>
                </div>
                <button class="btn btn--primary" type="submit">Salvar integracoes</button>
            </header>

            <div class="settings-grid">
                <label class="field field--wide">
                    <span>OpenAI API Key</span>
                    <input class="input" name="settings[integrations.openai_api_key]" value="<?= e($setting('integrations.openai_api_key')); ?>" autocomplete="off">
                </label>
                <label class="field">
                    <span>Google Calendar Client ID</span>
                    <input class="input" name="settings[integrations.google_calendar_client_id]" value="<?= e($setting('integrations.google_calendar_client_id')); ?>">
                </label>
                <label class="field">
                    <span>Google Calendar Client Secret</span>
                    <input class="input" name="settings[integrations.google_calendar_client_secret]" value="<?= e($setting('integrations.google_calendar_client_secret')); ?>" autocomplete="off">
                </label>
            </div>
        </form>
    </section>

    <section class="settings-panel" data-settings-panel="users">
        <div class="settings-users-grid">
            <form class="card settings-card" data-settings-form>
                <input type="hidden" name="action" value="create_user">
                <header class="settings-card__header">
                    <div>
                        <span class="eyebrow">Acesso</span>
                        <h3>Novo usuario</h3>
                    </div>
                    <button class="btn btn--primary" type="submit">Criar usuario</button>
                </header>
                <div class="settings-grid">
                    <label class="field"><span>Nome</span><input class="input" name="name" required></label>
                    <label class="field"><span>E-mail</span><input class="input" type="email" name="email" required></label>
                    <label class="field"><span>Telefone</span><input class="input" name="phone"></label>
                    <label class="field"><span>Senha inicial</span><input class="input" type="password" name="password" minlength="10" required></label>
                    <label class="field"><span>Permissao</span><?= renderSelect('role', $roles, 'operator'); ?></label>
                    <label class="field"><span>Status</span><?= renderSelect('status', $statuses, 'active'); ?></label>
                </div>
            </form>

            <div class="card settings-card">
                <header class="settings-card__header">
                    <div>
                        <span class="eyebrow">Equipe</span>
                        <h3>Usuarios cadastrados</h3>
                    </div>
                </header>
                <div class="settings-user-list">
                    <?php foreach ($adminUsers as $adminUser): ?>
                        <form class="settings-user-row" data-settings-form>
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="user_id" value="<?= (int) $adminUser['id']; ?>">
                            <div>
                                <strong><?= e($adminUser['name']); ?></strong>
                                <span><?= e($adminUser['email']); ?></span>
                            </div>
                            <input class="input" name="name" value="<?= e($adminUser['name']); ?>" aria-label="Nome">
                            <input class="input" name="email" value="<?= e($adminUser['email']); ?>" aria-label="E-mail">
                            <input class="input" name="phone" value="<?= e($adminUser['phone']); ?>" aria-label="Telefone">
                            <?= renderSelect('role', $roles, $adminUser['role']); ?>
                            <?= renderSelect('status', $statuses, $adminUser['status']); ?>
                            <button class="btn btn--ghost" type="submit">Salvar</button>
                        </form>
                        <form class="settings-password-row" data-settings-form>
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?= (int) $adminUser['id']; ?>">
                            <input class="input" type="password" name="password" minlength="10" placeholder="Nova senha segura">
                            <button class="btn btn--ghost" type="submit">Redefinir senha</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-panel" data-settings-panel="ai">
        <form class="card settings-card" data-settings-form>
            <input type="hidden" name="action" value="save_settings">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">Inteligencia artificial</span>
                    <h3>Uso interno, cooldown e imagem</h3>
                </div>
                <button class="btn btn--primary" type="submit">Salvar IA</button>
            </header>
            <div class="settings-grid">
                <label class="field"><span>Limite diario IA textual</span><input class="input" type="number" min="0" name="settings[ai.text_daily_limit]" value="<?= e($setting('ai.text_daily_limit')); ?>"></label>
                <label class="field"><span>Limite diario IA visual</span><input class="input" type="number" min="0" name="settings[ai.image_daily_limit]" value="<?= e($setting('ai.image_daily_limit')); ?>"></label>
                <label class="field"><span>Cooldown invisivel (segundos)</span><input class="input" type="number" min="0" name="settings[ai.cooldown_seconds]" value="<?= e($setting('ai.cooldown_seconds')); ?>"></label>
                <label class="field"><span>Qualidade da imagem</span><?= renderSelect('settings[ai.image_quality]', ['low' => 'Baixa', 'standard' => 'Padrao', 'high' => 'Alta'], $setting('ai.image_quality') ?: 'standard'); ?></label>
                <label class="field field--wide"><span>Watermark</span><input class="input" name="settings[ai.watermark_text]" value="<?= e($setting('ai.watermark_text')); ?>"></label>
            </div>
        </form>
    </section>

    <section class="settings-panel" data-settings-panel="system">
        <form class="card settings-card" data-settings-form>
            <input type="hidden" name="action" value="save_settings">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">Operacao</span>
                    <h3>Configuracoes do sistema</h3>
                </div>
                <button class="btn btn--primary" type="submit">Salvar sistema</button>
            </header>
            <div class="settings-grid">
                <label class="field"><span>Timezone</span><input class="input" name="settings[system.timezone]" value="<?= e($setting('system.timezone')); ?>"></label>
                <label class="field"><span>E-mail remetente</span><input class="input" type="email" name="settings[system.email_from]" value="<?= e($setting('system.email_from')); ?>"></label>
                <label class="field field--wide"><span>Nome da empresa</span><input class="input" name="settings[system.company_name]" value="<?= e($setting('system.company_name')); ?>"></label>
            </div>
        </form>
    </section>

    <section class="settings-panel" data-settings-panel="logs">
        <div class="card settings-card">
            <header class="settings-card__header">
                <div>
                    <span class="eyebrow">Auditoria</span>
                    <h3>Logs administrativos</h3>
                </div>
            </header>
            <div class="settings-log-list">
                <?php foreach ($loginHistory as $login): ?>
                    <article class="settings-log-row">
                        <span class="badge badge--gold"><?= e($login['status']); ?></span>
                        <div>
                            <strong><?= e($login['name'] ?: ($login['email'] ?: 'Acesso externo')); ?></strong>
                            <p><?= e(trim('Auth ' . ($login['failure_reason'] ? '- ' . $login['failure_reason'] : ''))); ?></p>
                        </div>
                        <small><?= e($login['created_at']); ?></small>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($adminLogs as $log): ?>
                    <article class="settings-log-row">
                        <span class="badge badge--gold"><?= e($log['level']); ?></span>
                        <div>
                            <strong><?= e($log['action'] ?: $log['channel']); ?></strong>
                            <p><?= e($log['message']); ?></p>
                        </div>
                        <small><?= e($log['created_at']); ?></small>
                    </article>
                <?php endforeach; ?>
                <?php if (!$adminLogs): ?>
                    <p class="muted">Nenhum log administrativo registrado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php
function renderSelect(string $name, array $options, string $selected): string
{
    $html = '<select class="select" name="' . e($name) . '">';

    foreach ($options as $value => $label) {
        $isSelected = (string) $value === (string) $selected ? ' selected' : '';
        $html .= '<option value="' . e((string) $value) . '"' . $isSelected . '>' . e((string) $label) . '</option>';
    }

    return $html . '</select>';
}
?>
