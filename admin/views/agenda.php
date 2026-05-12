<?php
$calendarStart = new DateTimeImmutable('2026-05-01');
$firstGridDay = $calendarStart->modify('last sunday');
$today = '2026-05-07';
$eventsByDate = [];

foreach ($calendarEvents as $event) {
    $eventsByDate[$event['date']][] = $event;
}
?>
<div class="crm-page calendar-page" data-calendar-page>
    <section class="crm-toolbar card">
        <div>
            <span class="eyebrow">Calendario operacional</span>
            <h2>Agenda tecnica premium</h2>
        </div>
        <div class="crm-toolbar__actions">
            <div class="calendar-view-switch" role="tablist" aria-label="Visualizacao do calendario">
                <button class="is-active" type="button" data-calendar-view="month">Mensal</button>
                <button type="button" data-calendar-view="week">Semanal</button>
                <button type="button" data-calendar-view="day">Diario</button>
            </div>
            <button class="btn btn--primary" type="button" data-event-create>Criar evento</button>
        </div>
    </section>

    <section class="calendar-shell">
        <aside class="card calendar-sidebar">
            <div>
                <span class="eyebrow">Maio 2026</span>
                <h2>Operacao</h2>
                <p class="muted">Visitas, servicos, follow-ups e retornos preventivos em uma visao unica.</p>
            </div>

            <div class="calendar-mini-card">
                <strong>Hoje</strong>
                <span>7 de maio de 2026</span>
            </div>

            <div class="calendar-category-list">
                <?php foreach ($calendarCategories as $key => $category): ?>
                    <span style="--event-color: <?= e($category['color']); ?>">
                        <i></i><?= e($category['label']); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <div class="calendar-google-note">
                <strong>Google Calendar API</strong>
                <p>Estrutura pronta para sincronizar eventos, participantes, lembretes e status externos.</p>
            </div>
        </aside>

        <div class="calendar-main card">
            <div class="calendar-main__header">
                <div>
                    <span class="eyebrow">Planejamento</span>
                    <h2 data-calendar-heading>Maio 2026</h2>
                </div>
                <div class="calendar-nav">
                    <button class="btn btn--ghost" type="button">Anterior</button>
                    <button class="btn btn--ghost" type="button">Hoje</button>
                    <button class="btn btn--ghost" type="button">Proximo</button>
                </div>
            </div>

            <div class="calendar-month is-active" data-calendar-panel="month">
                <div class="calendar-weekdays">
                    <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'] as $weekday): ?>
                        <span><?= e($weekday); ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="calendar-grid">
                    <?php for ($i = 0; $i < 42; $i++): ?>
                        <?php
                        $day = $firstGridDay->modify("+{$i} days");
                        $dateKey = $day->format('Y-m-d');
                        $isMuted = $day->format('m') !== '05';
                        ?>
                        <article class="calendar-day <?= $isMuted ? 'is-muted' : ''; ?> <?= $dateKey === $today ? 'is-today' : ''; ?>" data-calendar-day="<?= e($dateKey); ?>">
                            <header>
                                <span><?= e($day->format('j')); ?></span>
                            </header>
                            <div class="calendar-day__events" data-calendar-dropzone>
                                <?php foreach ($eventsByDate[$dateKey] ?? [] as $event): ?>
                                    <?php $category = $calendarCategories[$event['category']]; ?>
                                    <button class="calendar-event" type="button" draggable="true" data-calendar-event data-event-id="<?= e((string) $event['id']); ?>" data-event-date="<?= e($event['date']); ?>" data-event-title="<?= e($event['title']); ?>" data-event-time="<?= e($event['time']); ?>" data-event-client="<?= e($event['client']); ?>" data-event-location="<?= e($event['location']); ?>" data-event-category="<?= e($event['category']); ?>" style="--event-color: <?= e($category['color']); ?>">
                                        <strong><?= e($event['time']); ?></strong>
                                        <span><?= e($event['title']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="calendar-week" data-calendar-panel="week">
                <?php foreach (['2026-05-07', '2026-05-08', '2026-05-09', '2026-05-10', '2026-05-11', '2026-05-12', '2026-05-13'] as $date): ?>
                    <article class="calendar-agenda-day" data-calendar-day="<?= e($date); ?>">
                        <h3><?= e((new DateTimeImmutable($date))->format('d/m')); ?></h3>
                        <div data-calendar-dropzone>
                            <?php foreach ($eventsByDate[$date] ?? [] as $event): ?>
                                <?php $category = $calendarCategories[$event['category']]; ?>
                                <button class="calendar-event calendar-event--agenda" type="button" draggable="true" data-calendar-event data-event-id="<?= e((string) $event['id']); ?>" data-event-date="<?= e($event['date']); ?>" data-event-title="<?= e($event['title']); ?>" data-event-time="<?= e($event['time']); ?>" data-event-client="<?= e($event['client']); ?>" data-event-location="<?= e($event['location']); ?>" data-event-category="<?= e($event['category']); ?>" style="--event-color: <?= e($category['color']); ?>">
                                    <strong><?= e($event['time']); ?></strong>
                                    <span><?= e($event['title']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="calendar-day-view" data-calendar-panel="day">
                <div class="day-timeline">
                    <?php foreach (['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'] as $hour): ?>
                        <div class="day-timeline__slot" data-calendar-day="<?= e($today); ?>">
                            <span><?= e($hour); ?></span>
                            <div data-calendar-dropzone>
                                <?php foreach ($eventsByDate[$today] ?? [] as $event): ?>
                                    <?php if ($event['time'] !== $hour) { continue; } ?>
                                    <?php $category = $calendarCategories[$event['category']]; ?>
                                    <button class="calendar-event calendar-event--agenda" type="button" draggable="true" data-calendar-event data-event-id="<?= e((string) $event['id']); ?>" data-event-date="<?= e($event['date']); ?>" data-event-title="<?= e($event['title']); ?>" data-event-time="<?= e($event['time']); ?>" data-event-client="<?= e($event['client']); ?>" data-event-location="<?= e($event['location']); ?>" data-event-category="<?= e($event['category']); ?>" style="--event-color: <?= e($category['color']); ?>">
                                        <strong><?= e($event['time']); ?></strong>
                                        <span><?= e($event['title']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="modal calendar-modal" data-event-modal>
        <form class="modal__panel calendar-event-form" data-event-form>
            <?= csrf_field(); ?>
            <input type="hidden" name="event_id" data-event-field="id">
            <div class="modal__header">
                <h2 data-event-modal-title>Novo evento</h2>
                <button class="btn btn--ghost btn--icon" type="button" data-event-close>x</button>
            </div>
            <div class="modal__body">
                <div class="field">
                    <label for="event-title">Titulo</label>
                    <input class="input" id="event-title" name="title" data-event-field="title" required>
                </div>
                <div class="calendar-form-grid">
                    <div class="field">
                        <label for="event-date">Data</label>
                        <input class="input" id="event-date" name="date" type="date" data-event-field="date" required>
                    </div>
                    <div class="field">
                        <label for="event-time">Horario</label>
                        <input class="input" id="event-time" name="time" type="time" data-event-field="time" required>
                    </div>
                </div>
                <div class="field">
                    <label for="event-category">Categoria</label>
                    <select class="select" id="event-category" name="category" data-event-field="category">
                        <?php foreach ($calendarCategories as $key => $category): ?>
                            <option value="<?= e($key); ?>"><?= e($category['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="event-client">Cliente</label>
                    <input class="input" id="event-client" name="client" data-event-field="client">
                </div>
                <div class="field">
                    <label for="event-location">Local</label>
                    <input class="input" id="event-location" name="location" data-event-field="location">
                </div>
                <p class="calendar-integration-note">Preparado para persistir em MySQL e sincronizar com Google Calendar API.</p>
            </div>
            <div class="modal__footer">
                <button class="btn btn--primary" type="submit">Salvar evento</button>
            </div>
        </form>
    </div>

    <div class="kanban-save-status" data-calendar-status hidden>Atualizando evento...</div>
</div>
