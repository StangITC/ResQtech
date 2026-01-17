<?php

require_once __DIR__ . '/includes/init.php';
requireLogin();

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#0066ff">
    <title><?= htmlspecialchars(t('page_diagnostics_title')) ?> - ResQTech</title>

    <link rel="icon" type="image/svg+xml" href="icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/neo-brutalism.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/monitoring-ui.css') ?>">
</head>
<body>
    <?php renderNavigation('diagnostics', 'page_diagnostics_title', 'page_diagnostics_subtitle'); ?>

    <main class="main">
        <section class="nb-card panel">
            <div class="status-line">
                <div class="left">
                    <span class="badge blue" id="liveBadge"><?= htmlspecialchars(t('common_live')) ?></span>
                    <span class="badge" id="lastUpdated"><?= htmlspecialchars(t('common_last')) ?>: -</span>
                </div>
                <div class="controls">
                    <button id="refreshBtn" class="nb-btn nb-btn-primary">🔄 <?= htmlspecialchars(t('common_refresh')) ?></button>
                    <a class="nb-btn nb-btn-outline" href="api/connection-diagnostics.php" target="_blank" rel="noopener">🧾 <?= htmlspecialchars(t('common_raw_json')) ?></a>
                </div>
            </div>
            <div style="height: 12px;"></div>
            <div class="hint"><?= htmlspecialchars(t('diagnostics_hint')) ?></div>
        </section>

        <section class="row" id="cards"></section>

        <section class="nb-card panel">
            <div class="badge gray"><?= htmlspecialchars(t('diagnostics_hints_title')) ?></div>
            <div style="height: 12px;"></div>
            <div class="list" id="hints"></div>
        </section>
    </main>

    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <script>
        const I18N = <?= json_encode([
            'live' => t('common_live'),
            'error' => t('common_error'),
            'last' => t('common_last'),
            'refresh' => t('common_refresh'),
            'line_dns' => t('diagnostics_line_dns'),
            'line_tls' => t('diagnostics_line_tls'),
            'config' => t('diagnostics_config'),
            'filesystem' => t('diagnostics_filesystem'),
            'php_openssl' => t('diagnostics_php_openssl'),
            'ok' => t('diagnostics_ok'),
            'warn' => t('diagnostics_warn'),
            'fail' => t('diagnostics_fail'),
            'ready' => t('diagnostics_ready'),
            'missing' => t('diagnostics_missing'),
            'writable' => t('diagnostics_writable'),
            'not_writable' => t('diagnostics_not_writable'),
            'hint_dns' => t('diagnostics_hint_dns'),
            'hint_tls' => t('diagnostics_hint_tls'),
            'hint_token' => t('diagnostics_hint_token'),
            'hint_user' => t('diagnostics_hint_user'),
            'hint_esp_key' => t('diagnostics_hint_esp_key'),
            'hint_logs' => t('diagnostics_hint_logs'),
            'hint_all_good' => t('diagnostics_hint_all_good'),
            'fetch_failed' => t('common_fetch_failed')
        ], JSON_UNESCAPED_UNICODE) ?>;

        const elCards = document.getElementById('cards');
        const elHints = document.getElementById('hints');
        const elRefresh = document.getElementById('refreshBtn');
        const elLast = document.getElementById('lastUpdated');
        const elLive = document.getElementById('liveBadge');

        function badge(text, cls) {
            const b = document.createElement('span');
            b.className = 'badge ' + (cls || '');
            b.textContent = text;
            return b;
        }

        function card(title, color, main, subLines) {
            const section = document.createElement('section');
            section.className = 'nb-card panel';
            const sub = (subLines || []).map(s => `<div class="mono">${s}</div>`).join('');
            section.innerHTML = `
                <div class="badge ${color}">${title}</div>
                <div style="height: 10px;"></div>
                <div class="kv">
                    <div class="v">${main}</div>
                    <div class="mono" style="color: var(--text-secondary);">${sub}</div>
                </div>
            `;
            return section;
        }

        function addHint(text, tone) {
            const div = document.createElement('div');
            div.className = 'item';
            const top = document.createElement('div');
            top.className = 'top';
            top.appendChild(badge(tone, tone === I18N.ok ? 'lime' : tone === I18N.warn ? '' : 'red'));
            const t = document.createElement('div');
            t.className = 'mono';
            t.textContent = text;
            top.appendChild(t);
            div.appendChild(top);
            elHints.appendChild(div);
        }

        async function fetchDiag() {
            elRefresh.disabled = true;
            elLive.textContent = I18N.live;
            elLive.className = 'badge blue';
            elHints.innerHTML = '';
            try {
                const res = await fetch('api/connection-diagnostics.php?_t=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' });
                const data = await res.json();
                elLast.textContent = I18N.last + ': ' + new Date().toLocaleTimeString();

                const net = data.network || {};
                const cfg = data.config || {};
                const fs = data.filesystem || {};
                const php = data.php || {};

                const dnsOk = !!net.dns_ok;
                const tlsOk = !!net.tls_ok;
                const connectMs = typeof net.connect_ms === 'number' ? net.connect_ms : null;
                const logWritable = !!fs.log_dir_writable;

                const tokenSet = !!cfg.line_channel_access_token_set;
                const userSet = !!cfg.line_user_id_set;
                const espKeySet = !!cfg.esp32_api_key_set;
                const sslVerify = cfg.line_ssl_verify !== undefined ? !!cfg.line_ssl_verify : true;

                elCards.innerHTML = '';
                elCards.appendChild(card(I18N.line_dns, dnsOk ? 'lime' : 'red', dnsOk ? I18N.ok : I18N.fail, [
                    'host=' + (net.line_host || '-'),
                    'ip=' + (net.resolved_ip || '-')
                ]));

                elCards.appendChild(card(I18N.line_tls, tlsOk ? 'lime' : 'red', tlsOk ? I18N.ok : I18N.fail, [
                    'connect_ms=' + (connectMs === null ? '-' : String(connectMs)),
                    'ssl_verify=' + String(sslVerify),
                    'errno=' + String(net.errno || 0)
                ]));

                elCards.appendChild(card(I18N.config, (tokenSet && userSet && espKeySet) ? 'lime' : 'red', (tokenSet && userSet && espKeySet) ? I18N.ready : I18N.missing, [
                    'esp32_api_key_set=' + String(espKeySet),
                    'line_user_id_set=' + String(userSet),
                    'line_token_set=' + String(tokenSet)
                ]));

                elCards.appendChild(card(I18N.filesystem, logWritable ? 'lime' : 'red', logWritable ? I18N.writable : I18N.not_writable, [
                    'log_dir=' + (fs.log_dir || '-'),
                    'exists=' + String(!!fs.log_dir_exists)
                ]));

                elCards.appendChild(card(I18N.php_openssl, 'blue', (php.php_version || '-'), [
                    String(php.openssl || '-')
                ]));

                if (!dnsOk) addHint(I18N.hint_dns, I18N.fail);
                if (dnsOk && !tlsOk) addHint(I18N.hint_tls, I18N.fail);
                if (!tokenSet) addHint(I18N.hint_token, I18N.warn);
                if (!userSet) addHint(I18N.hint_user, I18N.warn);
                if (!espKeySet) addHint(I18N.hint_esp_key, I18N.warn);
                if (!logWritable) addHint(I18N.hint_logs, I18N.fail);
                if (dnsOk && tlsOk && tokenSet && userSet && espKeySet && logWritable) addHint(I18N.hint_all_good, I18N.ok);

                if (!res.ok || data.status !== 'success') {
                    elLive.textContent = I18N.error;
                    elLive.className = 'badge red';
                }
            } catch (e) {
                elCards.innerHTML = '';
                elLive.textContent = I18N.error;
                elLive.className = 'badge red';
                addHint(I18N.fetch_failed, I18N.fail);
            } finally {
                elRefresh.disabled = false;
            }
        }

        elRefresh.addEventListener('click', fetchDiag);
        fetchDiag();
    </script>
</body>
</html>

