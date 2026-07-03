<div id="weekly-report-modal"
    class="wr-modal-overlay"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="wr-modal-title"
    data-action-url="{{ route('admin.orders.settings.weekly-report.update') }}">
    <div class="wr-modal-panel">
        <div class="wr-modal-header">
            <div class="wr-modal-header__icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div>
                <h3 class="wr-modal-header__title" id="wr-modal-title">Reporte semanal automático</h3>
                <p class="wr-modal-header__sub">Configure el envío periódico de KPIs del dashboard</p>
            </div>
            <button type="button" class="wr-modal-close" id="btn-close-weekly-report-modal" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="wr-modal-body">
            <form id="weekly-report-settings-form" novalidate>
                @csrf
                @method('PUT')

                <div class="wr-section">
                    <div class="wr-section__label">
                        <i class="fas fa-calendar-alt"></i>
                        Programación del envío
                    </div>

                    <div class="wr-fields-row">
                        <div class="wr-field">
                            <label class="wr-label" for="weekly_report_day">Día</label>
                            <select class="wr-select" id="weekly_report_day" name="weekly_report_day">
                                @foreach ([
                                    0 => 'Domingo',
                                    1 => 'Lunes',
                                    2 => 'Martes',
                                    3 => 'Miércoles',
                                    4 => 'Jueves',
                                    5 => 'Viernes',
                                    6 => 'Sábado',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(($weeklyReportDay ?? 1) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="wr-field wr-field--sm">
                            <label class="wr-label" for="weekly_report_hour">Hora</label>
                            <div class="wr-time-input">
                                <input class="wr-input" type="number" id="weekly_report_hour" name="weekly_report_hour"
                                    min="0" max="23" step="1" placeholder="HH"
                                    value="{{ old('weekly_report_hour', $weeklyReportHour ?? 8) }}">
                                <span class="wr-time-sep">:</span>
                                <input class="wr-input" type="number" id="weekly_report_minute" name="weekly_report_minute"
                                    min="0" max="59" step="1" placeholder="MM"
                                    value="{{ old('weekly_report_minute', $weeklyReportMinute ?? 0) }}">
                            </div>
                            <p id="weekly-report-hour-error" class="wr-field-error" role="alert"></p>
                        </div>
                    </div>

                    <p class="wr-hint">
                        <i class="fas fa-info-circle"></i>
                        El reporte se enviará automáticamente cada semana en el día y hora indicados.
                    </p>
                </div>

                <div class="wr-section">
                    <div class="wr-section__label">
                        <i class="fas fa-users"></i>
                        Destinatarios
                    </div>

                    @php
                        $recipients = $weeklyReportRecipients ?? [];
                        if ($recipients === []) {
                            $recipients = [''];
                        }
                    @endphp

                    <div id="wr-recipients-list" class="wr-recipients-list">
                        @foreach ($recipients as $email)
                            <div class="wr-recipient-row">
                                <div class="wr-recipient-input-wrap">
                                    <i class="fas fa-envelope wr-recipient-icon"></i>
                                    <input class="wr-input wr-recipient-input" type="email"
                                        name="weekly_report_recipients[]" placeholder="correo@ejemplo.com"
                                        value="{{ $email }}" autocomplete="email">
                                </div>
                                <button type="button" class="wr-recipient-remove" aria-label="Eliminar destinatario"
                                    title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <p id="weekly-report-recipients-error" class="wr-field-error" role="alert"></p>

                    <button type="button" id="wr-add-recipient" class="wr-add-btn">
                        <i class="fas fa-plus-circle"></i>
                        Añadir destinatario
                    </button>
                </div>

                <p id="weekly-report-form-error" class="wr-form-error" role="alert"></p>

                <div class="wr-modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-weekly-report-modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="weekly-report-submit">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
