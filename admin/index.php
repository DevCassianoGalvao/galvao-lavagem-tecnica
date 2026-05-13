<?php
require_once __DIR__ . '/_bootstrap.php';
mvp_require_login();

$service = mvp_service();
$page = clean_text($_GET['page'] ?? 'dashboard');
$allowedPages = ['dashboard', 'leads', 'lead', 'agenda', 'metricas', 'config'];
$page = in_array($page, $allowedPages, true) ? $page : 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validate($_POST['_csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Token inválido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'lead') {
    $service->updateLead((int) ($_POST['id'] ?? 0), (string) ($_POST['status'] ?? 'novo'), (string) ($_POST['internal_notes'] ?? ''));
    header('Location: index.php?page=lead&id=' . (int) ($_POST['id'] ?? 0));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'agenda') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete') {
        $service->deleteAppointment((int) ($_POST['id'] ?? 0));
    } else {
        $service->saveAppointment($_POST);
    }
    header('Location: index.php?page=agenda');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'config') {
    $service->saveSettings($_POST['settings'] ?? []);
    header('Location: index.php?page=config&saved=1');
    exit;
}

function renderBars(array $items): void
{
    $max = max(1, ...array_map(fn($item) => (int) $item['total'], $items ?: [['total' => 1]]));
    echo '<div class="bars">';
    foreach ($items as $item) {
        $width = ((int) $item['total'] / $max) * 100;
        echo '<div class="bar"><span>' . mvp_e($item['label']) . '</span><div class="bar-track"><div class="bar-fill" style="width:' . $width . '%"></div></div><strong>' . (int) $item['total'] . '</strong></div>';
    }
    if (!$items) {
        echo '<p class="muted">Sem dados ainda.</p>';
    }
    echo '</div>';
}

function statusLabel(string $status): string
{
    return [
        'novo' => 'Novo',
        'contato_realizado' => 'Contato realizado',
        'orcamento_enviado' => 'Orçamento enviado',
        'fechado' => 'Fechado',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function formatAddress(array $lead): string
{
    $parts = array_filter([
        $lead['street'] ?? '',
        $lead['address_number'] ?? '',
        $lead['address_complement'] ?? '',
        $lead['neighborhood'] ?? '',
        $lead['city'] ?? '',
        $lead['cep'] ?? '',
    ]);

    return $parts ? implode(', ', $parts) : (string) ($lead['address'] ?? '');
}

if ($page === 'dashboard') {
    $data = $service->dashboard();
    mvp_header('Dashboard', 'dashboard');
    ?>
    <section class="grid stats">
      <div class="card"><p class="stat-label">Total de leads</p><p class="stat-value"><?= (int) $data['total_leads']; ?></p></div>
      <div class="card"><p class="stat-label">Bairros ativos</p><p class="stat-value"><?= count($data['neighborhoods']); ?></p></div>
      <div class="card"><p class="stat-label">Superfícies</p><p class="stat-value"><?= count($data['surfaces']); ?></p></div>
      <div class="card"><p class="stat-label">Agendamentos</p><p class="stat-value"><?= count($data['appointments']); ?></p></div>
    </section>
    <section class="grid split" style="margin-top:18px">
      <div class="card">
        <h2 class="section-title">Leads recentes</h2>
        <?php renderLeadTable(array_slice($data['recent_leads'], 0, 8)); ?>
      </div>
      <div class="card">
        <h2 class="section-title">Próximos agendamentos</h2>
        <?php foreach ($data['appointments'] as $event): ?>
          <p><strong><?= mvp_e($event['title']); ?></strong><br><span class="muted"><?= mvp_e(date('d/m/Y H:i', strtotime($event['scheduled_at']))); ?> · <?= mvp_e($event['event_type']); ?></span></p>
        <?php endforeach; ?>
        <?php if (!$data['appointments']): ?><p class="muted">Nenhum agendamento cadastrado.</p><?php endif; ?>
      </div>
    </section>
    <section class="grid stats" style="margin-top:18px">
      <div class="card" style="grid-column:span 2"><h2 class="section-title">Bairros mais frequentes</h2><?php renderBars($data['neighborhoods']); ?></div>
      <div class="card" style="grid-column:span 2"><h2 class="section-title">Superfícies mais solicitadas</h2><?php renderBars($data['surfaces']); ?></div>
    </section>
    <?php
    mvp_footer();
    exit;
}

if ($page === 'leads') {
    $filters = [
        'neighborhood' => $_GET['neighborhood'] ?? '',
        'surface' => $_GET['surface'] ?? '',
        'dirt' => $_GET['dirt'] ?? '',
        'status' => $_GET['status'] ?? '',
        'date' => $_GET['date'] ?? '',
    ];
    $leads = $service->leads($filters);
    mvp_header('Leads/Contatos', 'leads');
    ?>
    <form class="filters" method="get">
      <input type="hidden" name="page" value="leads">
      <select name="neighborhood"><option value="">Bairro</option><?php foreach (MvpService::neighborhoods() as $bairro): ?><option value="<?= mvp_e($bairro); ?>" <?= ($filters['neighborhood'] === $bairro) ? 'selected' : ''; ?>><?= mvp_e($bairro); ?></option><?php endforeach; ?></select>
      <input name="surface" placeholder="Superfície" value="<?= mvp_e($filters['surface']); ?>">
      <input name="dirt" placeholder="Sujeira" value="<?= mvp_e($filters['dirt']); ?>">
      <select name="status"><option value="">Status</option><?php foreach (MvpService::statuses() as $status): ?><option value="<?= mvp_e($status); ?>" <?= ($filters['status'] === $status) ? 'selected' : ''; ?>><?= mvp_e(statusLabel($status)); ?></option><?php endforeach; ?></select>
      <input type="date" name="date" value="<?= mvp_e($filters['date']); ?>">
      <button class="btn" type="submit">Filtrar</button>
      <a class="btn ghost" href="export.php">Exportar contatos</a>
    </form>
    <div class="card"><?php renderLeadTable($leads); ?></div>
    <?php
    mvp_footer();
    exit;
}

if ($page === 'lead') {
    $lead = $service->lead((int) ($_GET['id'] ?? 0));
    if (!$lead) {
        http_response_code(404);
        exit('Lead não encontrado.');
    }
    mvp_header('Detalhes do lead', 'leads');
    ?>
    <section class="split">
      <div class="card">
        <div class="lead-head">
          <div>
            <h2 class="section-title"><?= mvp_e($lead['name']); ?></h2>
            <p class="muted"><?= mvp_e($lead['phone']); ?> · <?= mvp_e($lead['neighborhood']); ?></p>
          </div>
          <a class="btn" target="_blank" href="<?= mvp_e(mvp_whatsapp($lead['phone'])); ?>">Chamar no WhatsApp</a>
        </div>
        <hr style="border:0;border-top:1px solid rgba(255,255,255,.08);margin:18px 0">
        <p><strong>Rua:</strong> <?= mvp_e($lead['street'] ?: $lead['address']); ?></p>
        <p><strong>Número:</strong> <?= mvp_e($lead['address_number'] ?: 'Não informado'); ?></p>
        <p><strong>Complemento:</strong> <?= mvp_e($lead['address_complement'] ?: 'Não informado'); ?></p>
        <p><strong>Bairro:</strong> <?= mvp_e($lead['neighborhood']); ?></p>
        <p><strong>Cidade:</strong> <?= mvp_e($lead['city'] ?: 'Nova Friburgo'); ?></p>
        <p><strong>CEP:</strong> <?= mvp_e($lead['cep'] ?: 'Não informado'); ?></p>
        <p><strong>Endereço completo:</strong> <?= mvp_e(formatAddress($lead)); ?></p>
        <p><strong>Tipo de imóvel:</strong> <?= mvp_e($lead['property_type']); ?></p>
        <p><strong>Metragem:</strong> <?= mvp_e($lead['square_meters'] ?: $lead['area_size']); ?></p>
        <p><strong>Altura:</strong> <?= mvp_e(trim(($lead['height_type'] ?? '') . ' · ' . ($lead['height_approx'] ?? ''), ' ·')); ?></p>
        <p><strong>Frequência:</strong> <?= mvp_e($lead['cleaning_frequency']); ?></p>
        <p><strong>Observações:</strong><br><?= nl2br(mvp_e($lead['notes'])); ?></p>
        <div class="chips"><?php foreach ($lead['surfaces'] as $item): ?><span class="chip"><?= mvp_e($item); ?></span><?php endforeach; ?></div>
        <br>
        <div class="chips"><?php foreach ($lead['dirt'] as $item): ?><span class="chip"><?= mvp_e($item); ?></span><?php endforeach; ?></div>
      </div>
      <div class="card">
        <h2 class="section-title">Controle interno</h2>
        <form method="post">
          <input type="hidden" name="_csrf_token" value="<?= mvp_e(csrf_token()); ?>">
          <input type="hidden" name="id" value="<?= (int) $lead['id']; ?>">
          <label class="field"><span>Status</span><select name="status"><?php foreach (MvpService::statuses() as $status): ?><option value="<?= mvp_e($status); ?>" <?= $lead['status'] === $status ? 'selected' : ''; ?>><?= mvp_e(statusLabel($status)); ?></option><?php endforeach; ?></select></label>
          <label class="field"><span>Observação manual</span><textarea name="internal_notes"><?= mvp_e($lead['internal_notes'] ?? ''); ?></textarea></label>
          <button class="btn" type="submit">Salvar</button>
        </form>
      </div>
    </section>
    <section class="card" style="margin-top:18px">
      <h2 class="section-title">Imagens enviadas</h2>
      <div class="gallery" data-gallery>
      <?php foreach ($lead['images'] as $image): ?>
        <img src="api/image.php?id=<?= (int) $image['id']; ?>" alt="<?= mvp_e($image['original_name']); ?>">
      <?php endforeach; ?>
      </div>
      <?php if (!$lead['images']): ?><p class="muted">Nenhuma imagem enviada.</p><?php endif; ?>
    </section>
    <?php
    mvp_footer();
    exit;
}

if ($page === 'agenda') {
    $leads = $service->leads();
    $appointments = $service->appointments();
    $editAppointment = isset($_GET['edit']) ? $service->appointment((int) $_GET['edit']) : null;
    $eventsByDay = [];
    foreach ($appointments as $event) {
        $key = date('Y-m-d', strtotime($event['scheduled_at']));
        $eventsByDay[$key][] = $event;
    }
    $selectedMonth = max(1, min(12, (int) ($_GET['month'] ?? date('n'))));
    $selectedYear = max(2024, min(2100, (int) ($_GET['year'] ?? date('Y'))));
    $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $selectedYear, $selectedMonth));
    $daysInMonth = (int) $monthStart->format('t');
    $firstWeekday = (int) $monthStart->format('N');
    $previousMonth = $monthStart->modify('-1 month');
    $nextMonth = $monthStart->modify('+1 month');
    $monthNames = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
    mvp_header('Agenda', 'agenda');
    ?>
    <section class="split">
      <form class="card" method="post">
        <h2 class="section-title"><?= $editAppointment ? 'Editar agendamento' : 'Adicionar agendamento'; ?></h2>
        <input type="hidden" name="_csrf_token" value="<?= mvp_e(csrf_token()); ?>">
        <?php if ($editAppointment): ?><input type="hidden" name="id" value="<?= (int) $editAppointment['id']; ?>"><?php endif; ?>
        <label class="field"><span>Título</span><input name="title" required placeholder="Visita técnica, serviço ou retorno" value="<?= mvp_e($editAppointment['title'] ?? ''); ?>"></label>
        <label class="field"><span>Lead</span><select name="lead_id"><option value="">Sem lead vinculado</option><?php foreach ($leads as $lead): ?><option value="<?= (int) $lead['id']; ?>" <?= ((int) ($editAppointment['lead_id'] ?? 0) === (int) $lead['id']) ? 'selected' : ''; ?>><?= mvp_e($lead['name']); ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Tipo</span><select name="event_type"><?php foreach (['visita', 'serviço', 'retorno'] as $type): ?><option <?= (($editAppointment['event_type'] ?? 'visita') === $type) ? 'selected' : ''; ?>><?= mvp_e($type); ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Data e hora</span><input type="datetime-local" name="scheduled_at" required value="<?= $editAppointment ? mvp_e(date('Y-m-d\TH:i', strtotime($editAppointment['scheduled_at']))) : ''; ?>"></label>
        <label class="field"><span>Notas</span><textarea name="notes"><?= mvp_e($editAppointment['notes'] ?? ''); ?></textarea></label>
        <div class="actions">
          <button class="btn" type="submit"><?= $editAppointment ? 'Atualizar agendamento' : 'Salvar agendamento'; ?></button>
          <?php if ($editAppointment): ?><a class="btn ghost" href="index.php?page=agenda">Cancelar edição</a><?php endif; ?>
        </div>
      </form>
      <div class="card">
        <h2 class="section-title">Próximos eventos</h2>
        <?php foreach ($appointments as $event): ?>
          <form method="post" class="event-row">
            <input type="hidden" name="_csrf_token" value="<?= mvp_e(csrf_token()); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $event['id']; ?>">
            <p><strong><?= mvp_e($event['title']); ?></strong><br><span class="muted"><?= mvp_e(date('d/m/Y H:i', strtotime($event['scheduled_at']))); ?> · <?= mvp_e($event['lead_name'] ?? ''); ?></span></p>
            <div class="actions">
              <a class="btn ghost" href="index.php?page=agenda&edit=<?= (int) $event['id']; ?>">Editar</a>
              <button class="btn ghost" data-confirm="Remover este agendamento?" type="submit">Remover</button>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="card calendar-card" style="margin-top:18px" data-calendar-card>
      <div class="calendar-head">
        <div>
          <p class="eyebrow">Agenda mensal</p>
          <h2 class="section-title"><?= mvp_e($monthNames[(int) $monthStart->format('n')]); ?> de <?= mvp_e($monthStart->format('Y')); ?></h2>
        </div>
        <div class="actions">
          <a class="btn ghost" data-calendar-link href="index.php?page=agenda&month=<?= (int) $previousMonth->format('n'); ?>&year=<?= (int) $previousMonth->format('Y'); ?>">Anterior</a>
          <a class="btn ghost" data-calendar-link href="index.php?page=agenda&month=<?= (int) date('n'); ?>&year=<?= (int) date('Y'); ?>">Hoje</a>
          <a class="btn ghost" data-calendar-link href="index.php?page=agenda&month=<?= (int) $nextMonth->format('n'); ?>&year=<?= (int) $nextMonth->format('Y'); ?>">Próximo</a>
        </div>
      </div>
      <div class="calendar">
        <?php foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $weekday): ?><div class="weekday"><?= mvp_e($weekday); ?></div><?php endforeach; ?>
        <?php for ($blank = 1; $blank < $firstWeekday; $blank++): ?><div class="day muted"></div><?php endfor; ?>
        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
          <?php $dateKey = $monthStart->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), $day)->format('Y-m-d'); ?>
          <div class="day">
            <strong><?= $day; ?></strong>
            <?php foreach ($eventsByDay[$dateKey] ?? [] as $event): ?>
              <div class="event"><?= mvp_e(date('H:i', strtotime($event['scheduled_at']))); ?> · <?= mvp_e($event['title']); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      </div>
    </section>
    <?php
    mvp_footer();
    exit;
}

if ($page === 'metricas') {
    $data = $service->dashboard();
    mvp_header('Métricas', 'metricas');
    ?>
    <section class="grid stats">
      <div class="card" style="grid-column:span 2"><h2 class="section-title">Bairros com mais leads</h2><?php renderBars($data['neighborhoods']); ?></div>
      <div class="card" style="grid-column:span 2"><h2 class="section-title">Sujeiras mais comuns</h2><?php renderBars($data['dirt']); ?></div>
      <div class="card" style="grid-column:span 4"><h2 class="section-title">Superfícies mais solicitadas</h2><?php renderBars($data['surfaces']); ?></div>
    </section>
    <?php
    mvp_footer();
    exit;
}

if ($page === 'config') {
    $settings = $service->settings();
    mvp_header('Configurações', 'config');
    ?>
    <?php if (isset($_GET['saved'])): ?><p class="alert ok">Configurações salvas.</p><?php endif; ?>
    <form class="card" method="post">
      <input type="hidden" name="_csrf_token" value="<?= mvp_e(csrf_token()); ?>">
      <label class="field"><span>Meta Pixel</span><textarea name="settings[meta_pixel]"><?= mvp_e($settings['meta_pixel']); ?></textarea></label>
      <label class="field"><span>Google Analytics</span><textarea name="settings[google_analytics]"><?= mvp_e($settings['google_analytics']); ?></textarea></label>
      <label class="field"><span>Google Tag Manager</span><textarea name="settings[gtm]"><?= mvp_e($settings['gtm']); ?></textarea></label>
      <label class="field"><span>Scripts personalizados no head</span><textarea name="settings[custom_head]"><?= mvp_e($settings['custom_head']); ?></textarea></label>
      <label class="field"><span>Scripts personalizados antes de fechar body</span><textarea name="settings[custom_body]"><?= mvp_e($settings['custom_body']); ?></textarea></label>
      <button class="btn" type="submit">Salvar scripts</button>
    </form>
    <?php
    mvp_footer();
    exit;
}

function renderLeadTable(array $leads): void
{
    ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Nome</th><th>Contato</th><th>Bairro</th><th>Superfícies</th><th>Sujeira</th><th>Status</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($leads as $lead): ?>
          <tr>
            <td data-label="Nome"><strong><?= mvp_e($lead['name']); ?></strong></td>
            <td data-label="Contato"><?= mvp_e($lead['phone']); ?></td>
            <td data-label="Bairro"><?= mvp_e($lead['neighborhood']); ?></td>
            <td data-label="Superfícies"><div class="chips"><?php foreach ($lead['surfaces'] as $item): ?><span class="chip"><?= mvp_e($item); ?></span><?php endforeach; ?></div></td>
            <td data-label="Sujeira"><div class="chips"><?php foreach ($lead['dirt'] as $item): ?><span class="chip"><?= mvp_e($item); ?></span><?php endforeach; ?></div></td>
            <td data-label="Status"><span class="status"><?= mvp_e(statusLabel($lead['status'])); ?></span></td>
            <td data-label="Data"><?= mvp_e(date('d/m/Y', strtotime($lead['created_at']))); ?></td>
            <td data-label="Ação"><a class="pill" href="index.php?page=lead&id=<?= (int) $lead['id']; ?>">Abrir</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$leads): ?><tr><td colspan="8" class="muted">Nenhum lead encontrado.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}
