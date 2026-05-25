import { escapeHtml } from '../../shared/escape-html.js';

function showCf4Toast(toast, toastIcon, toastTitle, toastMsg, type, title, msg, timerRef) {
    toast.className = `cf4-toast cf4-toast--${type}`;
    toastIcon.className = `cf4-toast__icon fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
    toastTitle.textContent = title;
    toastMsg.textContent = msg;
    void toast.offsetWidth;
    toast.classList.add('cf4-toast--visible');

    if (timerRef.current) {
        window.clearTimeout(timerRef.current);
    }
    timerRef.current = window.setTimeout(() => {
        toast.classList.remove('cf4-toast--visible');
    }, 5000);
}

function showFieldError(el, msg) {
    el.textContent = msg;
    el.classList.add('wr-field-error--visible');
}

export function initWeeklyReportModal() {
    const modal = document.getElementById('weekly-report-modal');
    const openBtn = document.getElementById('btn-open-weekly-report-modal');
    if (!modal || !openBtn) {
        return;
    }

    const closeBtn = document.getElementById('btn-close-weekly-report-modal');
    const cancelBtn = document.getElementById('btn-cancel-weekly-report-modal');
    const form = document.getElementById('weekly-report-settings-form');
    const submitBtn = document.getElementById('weekly-report-submit');
    const formError = document.getElementById('weekly-report-form-error');
    const hourError = document.getElementById('weekly-report-hour-error');
    const rcptError = document.getElementById('weekly-report-recipients-error');
    const recipientList = document.getElementById('wr-recipients-list');
    const addBtn = document.getElementById('wr-add-recipient');
    const actionUrl = modal.dataset.actionUrl || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const toast = document.getElementById('cf4-toast');
    const toastIcon = toast?.querySelector('.cf4-toast__icon');
    const toastTitle = toast?.querySelector('.cf4-toast__title');
    const toastMsg = toast?.querySelector('.cf4-toast__msg');
    const toastClose = toast?.querySelector('.cf4-toast__close');
    const toastTimer = { current: null };

    const clearErrors = () => {
        [formError, hourError, rcptError].forEach((el) => {
            if (!el) return;
            el.textContent = '';
            el.classList.remove('wr-field-error--visible');
        });
        recipientList?.querySelectorAll('.wr-recipient-input').forEach((input) => {
            input.classList.remove('wr-input--error');
        });
    };

    const openModal = () => {
        modal.classList.add('wr-modal-overlay--active');
        modal.removeAttribute('aria-hidden');
        const first = modal.querySelector('select, input');
        if (first) {
            window.setTimeout(() => first.focus(), 80);
        }
    };

    const closeModal = () => {
        modal.classList.remove('wr-modal-overlay--active');
        modal.setAttribute('aria-hidden', 'true');
    };

    const updateRemoveButtons = () => {
        const rows = recipientList?.querySelectorAll('.wr-recipient-row') || [];
        rows.forEach((row) => {
            const btn = row.querySelector('.wr-recipient-remove');
            if (!btn) return;
            btn.disabled = rows.length === 1;
            btn.style.opacity = rows.length === 1 ? '0.3' : '1';
        });
    };

    const removeRow = (row) => {
        row.classList.add('wr-recipient-row--removing');
        row.addEventListener('transitionend', () => {
            row.remove();
            updateRemoveButtons();
        }, { once: true });
    };

    const addRecipientRow = (value = '') => {
        const row = document.createElement('div');
        row.className = 'wr-recipient-row wr-recipient-row--new';
        row.innerHTML = `
            <div class="wr-recipient-input-wrap">
                <i class="fas fa-envelope wr-recipient-icon"></i>
                <input class="wr-input wr-recipient-input" type="email" name="weekly_report_recipients[]"
                    placeholder="correo@ejemplo.com" value="${escapeHtml(value)}" autocomplete="email">
            </div>
            <button type="button" class="wr-recipient-remove" aria-label="Eliminar destinatario" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
            </button>`;

        recipientList?.appendChild(row);
        requestAnimationFrame(() => row.classList.remove('wr-recipient-row--new'));
        row.querySelector('.wr-recipient-remove')?.addEventListener('click', () => removeRow(row));
        updateRemoveButtons();
        row.querySelector('input')?.focus();
    };

    openBtn.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('wr-modal-overlay--active')) {
            closeModal();
        }
    });

    recipientList?.querySelectorAll('.wr-recipient-remove').forEach((btn) => {
        btn.addEventListener('click', () => removeRow(btn.closest('.wr-recipient-row')));
    });
    updateRemoveButtons();
    addBtn?.addEventListener('click', () => addRecipientRow(''));

    toastClose?.addEventListener('click', () => {
        if (toastTimer.current) {
            window.clearTimeout(toastTimer.current);
        }
        toast?.classList.remove('cf4-toast--visible');
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        const inputs = recipientList?.querySelectorAll('.wr-recipient-input') || [];
        const validEmails = [];
        let hasInvalid = false;

        inputs.forEach((input) => {
            const value = input.value.trim();
            if (value === '') {
                return;
            }
            if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                validEmails.push(value);
            } else {
                input.classList.add('wr-input--error');
                hasInvalid = true;
            }
        });

        if (hasInvalid) {
            showFieldError(rcptError, 'Uno o más correos tienen un formato inválido.');
            return;
        }

        if (validEmails.length === 0) {
            showFieldError(rcptError, 'Ingrese al menos un correo electrónico válido.');
            recipientList?.querySelector('.wr-recipient-input')?.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

        const fd = new FormData(form);
        fd.delete('weekly_report_recipients[]');
        const unique = validEmails.filter((value, index, array) => array.indexOf(value) === index);
        unique.forEach((email) => fd.append('weekly_report_recipients[]', email));
        fd.delete('weekly_report_recipients');
        fd.append('weekly_report_recipients', unique.join(','));

        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: fd,
            });
            const json = await response.json();

            if (response.ok) {
                closeModal();
                if (toast && toastIcon && toastTitle && toastMsg) {
                    showCf4Toast(
                        toast,
                        toastIcon,
                        toastTitle,
                        toastMsg,
                        'success',
                        '¡Configuración guardada!',
                        json.message ?? 'El reporte semanal ha sido actualizado correctamente.',
                        toastTimer,
                    );
                }
                return;
            }

            const errors = json.errors ?? {};
            if (errors.weekly_report_hour || errors.weekly_report_minute) {
                showFieldError(hourError, (errors.weekly_report_hour ?? errors.weekly_report_minute)[0]);
            }
            if (errors.weekly_report_recipients) {
                showFieldError(rcptError, errors.weekly_report_recipients[0]);
            }

            const generalMsg = json.message ?? 'Error al guardar la configuración.';
            showFieldError(formError, generalMsg);
            if (toast && toastIcon && toastTitle && toastMsg) {
                showCf4Toast(toast, toastIcon, toastTitle, toastMsg, 'error', 'Error al guardar', generalMsg, toastTimer);
            }
        } catch (_error) {
            const netMsg = 'Error de red. Por favor, inténtelo de nuevo.';
            showFieldError(formError, netMsg);
            if (toast && toastIcon && toastTitle && toastMsg) {
                showCf4Toast(toast, toastIcon, toastTitle, toastMsg, 'error', 'Error de conexión', netMsg, toastTimer);
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
        }
    });
}
