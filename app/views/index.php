<div class="hero-section mb-5 px-3">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="hero-title">
                    Todos os seus podcasts,<br>
                    <span>sincronizados em qualquer lugar</span>
                </h1>
                <p class="hero-subtitle">
                    <?php echo TITLE; ?> é um servidor de sincronização baseado no protocolo gPodder. Mantenha
                    assinaturas, episódios e histórico de reprodução sempre atualizados em todos os seus dispositivos.
                </p>
                <div class="hero-cta d-flex flex-wrap gap-3">
                    <a href="/register" class="btn-primary-custom">
                        <i class="bi bi-person-plus me-2"></i>Registrar
                    </a>
                    <a href="/login" class="btn-outline-custom">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                    </a>
                </div>
            </div>
            <div class="col-lg-5 offset-lg-1 d-none d-lg-block">
                <div class="hero-visual">
                    <div class="device-card">
                        <div class="device-card-header">
                            <div class="device-dot" style="background:#f87171"></div>
                            <div class="device-dot" style="background:#fbbf24"></div>
                            <div class="device-dot" style="background:#34d399"></div>
                            <span style="color:#64748b;font-size:0.78rem;margin-left:0.5rem;"><?php echo TITLE; ?> —
                                Sincronizando</span>
                        </div>
                        <?php
                        $syncItems = [
                            ['icon' => 'bi-phone', 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.15)', 'label' => 'AntennaPod (Android)', 'status' => 'Sincronizado', 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-laptop', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.15)', 'label' => 'gPodder (Desktop)', 'status' => 'Sincronizado', 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-display', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.15)', 'label' => 'Cardo (Windows)', 'status' => 'Sincronizado', 'statusClass' => 'text-success', 'statusBg' => 'rgba(52,211,153,0.12)'],
                            ['icon' => 'bi-tablet', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.15)', 'label' => 'Kasts (Linux)', 'status' => 'Sincronizando...', 'statusClass' => 'text-warning', 'statusBg' => 'rgba(251,191,36,0.12)'],
                        ];
                        foreach ($syncItems as $item): ?>
                            <div class="sync-item">
                                <div class="sync-icon" style="background:<?php echo $item['bg']; ?>">
                                    <i class="bi <?php echo $item['icon']; ?>"
                                        style="color:<?php echo $item['color']; ?>"></i>
                                </div>
                                <span class="sync-label"><?php echo $item['label']; ?></span>
                                <span class="sync-status <?php echo $item['statusClass']; ?>"
                                    style="background:<?php echo $item['statusBg']; ?>">
                                    <?php echo $item['status']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 p-3 rounded-3"
                        style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                        <div class="d-flex align-items-center justify-content-between">
                            <span style="color:#64748b;font-size:0.78rem;"><i class="bi bi-clock me-1"></i>Última
                                sincronização</span>
                            <span style="color:#34d399;font-size:0.78rem;font-weight:600;">Agora mesmo</span>
                        </div>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span
                                style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;">3
                                assinaturas</span>
                            <span
                                style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;">47
                                episódios</span>
                            <span
                                style="font-size:0.72rem;color:#94a3b8;background:rgba(255,255,255,0.05);padding:0.2rem 0.6rem;border-radius:6px;">4
                                dispositivos</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="mb-5">
        <div class="text-center mb-4">
            <h2 class="section-title">Por que usar o <?php echo TITLE; ?>?</h2>
        </div>
        <div class="row g-3">
            <?php
            $features = [
                ['icon' => 'bi-arrow-repeat', 'color' => '#6366f1', 'bg' => '#eef2ff', 'title' => 'Sincronização em tempo real', 'desc' => 'Assinaturas, posição de reprodução e histórico sincronizados instantaneamente entre todos os seus dispositivos.'],
                ['icon' => 'bi-shield-check', 'color' => '#10b981', 'bg' => '#f0fdf4', 'title' => 'Privacidade total', 'desc' => 'Seus dados ficam no seu servidor. Sem rastreamento, sem analytics externos, sem compartilhamento com terceiros.'],
                ['icon' => 'bi-plug', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'title' => 'Compatível com o protocolo gPodder', 'desc' => 'Funciona com qualquer app que suporte o protocolo gPodder, incluindo AntennaPod, gPodder Desktop e Nextcloud Gpoddersync.'],
                ['icon' => 'bi-phone', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'title' => 'Multi-dispositivo', 'desc' => 'Android, Windows, macOS, Linux, BSD. Adicione quantos dispositivos quiser e mantenha tudo sincronizado.'],
                ['icon' => 'bi-graph-up', 'color' => '#ef4444', 'bg' => '#fef2f2', 'title' => 'Histórico completo', 'desc' => 'Acompanhe o que você ouviu, baixou ou deletou. Histórico completo de atividade por dispositivo.'],
                ['icon' => 'bi-code-slash', 'color' => '#0ea5e9', 'bg' => '#f0f9ff', 'title' => '100% Open Source', 'desc' => 'Código aberto, auditável e extensível. Contribua, personalize ou hospede sua própria instância.'],
            ];
            foreach ($features as $f): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background:<?php echo $f['bg']; ?>">
                            <i class="bi <?php echo $f['icon']; ?>" style="color:<?php echo $f['color']; ?>"></i>
                        </div>
                        <div class="feature-title"><?php echo $f['title']; ?></div>
                        <p class="feature-desc"><?php echo $f['desc']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-5 py-4 px-4 rounded-4"
        style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;">
        <div class="row align-items-center g-4">
            <div class="col-lg-4">
                <div class="section-label">Como funciona</div>
                <h2 class="section-title">Configure em 3 passos</h2>
                <p class="section-subtitle" style="font-size:0.92rem;">Sem instalações. Sem configurações. Comece a sincronizar em minutos.</p>
            </div>
            <div class="col-lg-8">
                <div class="d-flex flex-column gap-3">
                    <?php
                    $steps = [
                        ['n' => '1', 'title' => 'Crie sua conta', 'desc' => 'Registre-se com usuário e senha.'],
                        ['n' => '2', 'title' => 'Configure o app de podcast', 'desc' => 'Adicione a URL do servidor gPodder no seu app favorito (AntennaPod, gPodder, Cardo, Kasts...).'],
                        ['n' => '3', 'title' => 'Comece a sincronizar', 'desc' => 'Assine podcasts, ouça episódios, tudo fica sincronizado automaticamente em todos os dispositivos.'],
                    ];
                    foreach ($steps as $s): ?>
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 border">
                            <div class="step-number flex-shrink-0"><?php echo $s['n']; ?></div>
                            <div>
                                <div style="font-weight:700;color:#1e293b;font-size:0.95rem;"><?php echo $s['title']; ?>
                                </div>
                                <div style="font-size:0.85rem;color:#64748b;margin-top:0.2rem;"><?php echo $s['desc']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPATIBLE CLIENTS -->
    <div class="mb-5">
        <div class="text-center mb-4">
            <h2 class="section-title">Apps Compatíveis</h2>
        </div>
        <div class="row g-3">
            <?php
            $clients = [
                [
                    'name' => 'AntennaPod',
                    'version' => '3.5.0',
                    'url' => 'https://github.com/AntennaPod/AntennaPod',
                    'icon' => 'bi-phone-fill',
                    'color' => '#f59e0b',
                    'bg' => '#fffbeb',
                    'platforms' => [['icon' => 'bi-android2', 'label' => 'Android']]
                ],
                [
                    'name' => 'gPodder',
                    'version' => '3.11.4',
                    'url' => 'https://gpodder.github.io/',
                    'icon' => 'bi-laptop-fill',
                    'color' => '#6366f1',
                    'bg' => '#eef2ff',
                    'platforms' => [['icon' => 'bi-windows', 'label' => 'Windows'], ['icon' => 'bi-apple', 'label' => 'macOS'], ['icon' => 'bi-ubuntu', 'label' => 'Linux']]
                ],
                [
                    'name' => 'Cardo',
                    'version' => '1.90',
                    'url' => 'https://cardo-podcast.github.io/',
                    'icon' => 'bi-display-fill',
                    'color' => '#10b981',
                    'bg' => '#f0fdf4',
                    'platforms' => [['icon' => 'bi-windows', 'label' => 'Windows'], ['icon' => 'bi-apple', 'label' => 'macOS'], ['icon' => 'bi-ubuntu', 'label' => 'Linux']]
                ],
                [
                    'name' => 'Kasts',
                    'version' => '21.88',
                    'url' => 'https://invent.kde.org/multimedia/kasts',
                    'icon' => 'bi-collection-play-fill',
                    'color' => '#8b5cf6',
                    'bg' => '#f5f3ff',
                    'platforms' => [['icon' => 'bi-windows', 'label' => 'Windows'], ['icon' => 'bi-android2', 'label' => 'Android'], ['icon' => 'bi-ubuntu', 'label' => 'Linux']]
                ],
            ];
            foreach ($clients as $c): ?>
                <div class="col-sm-6 col-lg-3">
                    <a href="<?php echo $c['url']; ?>" target="_blank" class="text-decoration-none">
                        <div class="client-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="client-icon" style="background:<?php echo $c['bg']; ?>">
                                    <i class="bi <?php echo $c['icon']; ?>" style="color:<?php echo $c['color']; ?>"></i>
                                </div>
                                <div>
                                    <div class="client-name"><?php echo $c['name']; ?></div>
                                    <div class="client-version">v<?php echo $c['version']; ?></div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($c['platforms'] as $p): ?>
                                    <span class="platform-badge"><i
                                            class="bi <?php echo $p['icon']; ?>"></i><?php echo $p['label']; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>