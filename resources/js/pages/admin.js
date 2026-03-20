import Swal from 'sweetalert2';

// admin.js - pequeñas ayudas generales
document.addEventListener("DOMContentLoaded", ()=>{
  const toggle = document.querySelector(".toggle-sidebar");
  const aside = document.querySelector(".admin-sidebar");
  if(toggle && aside){
    toggle.addEventListener("click", ()=> aside.classList.toggle("collapsed"));
  }
});

// Funciones para el modal de perfil de usuario
function mostrarPerfil() {
  const modal = document.getElementById('modalPerfil');
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  } else {
    console.error('Modal de perfil no encontrado. Asegúrate de que el modal esté en el DOM.');
  }
}

function cerrarModalPerfil() {
  const modal = document.getElementById('modalPerfil');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Asegurar que las funciones estén disponibles globalmente
window.mostrarPerfil = mostrarPerfil;
window.cerrarModalPerfil = cerrarModalPerfil;

// Cerrar modal con tecla ESC (Principio de Nielsen: Flexibilidad y eficiencia)
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    cerrarModalPerfil();
  }
});

// Mostrar error de autenticación con SweetAlert2
document.addEventListener('DOMContentLoaded', function () {
    const errorEl = document.getElementById('authError');
    if (errorEl) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: errorEl.dataset.message,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#e53e3e',
        });
    }
});

// Toggle para ver/ocultar contraseña en formularios de login/registro
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#loginPassword');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye-slash');
        });
    }
});

// ============================================================
// CLIENTS TABLE — Ban / Unban
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const banBtn   = e.target.closest('.btn-ban');
        const unbanBtn = e.target.closest('.btn-unban');

        if (!banBtn && !unbanBtn) return;

        const btn    = banBtn || unbanBtn;
        const action = btn.dataset.action; // 'ban' | 'unban'
        const id     = btn.dataset.id;
        const name   = btn.dataset.name;
        const email  = btn.dataset.email;

        const isBan = action === 'ban';

        Swal.fire({
            icon: isBan ? 'warning' : 'question',
            title: isBan ? '¿Banear usuario?' : '¿Activar usuario?',
            html: isBan
                ? `¿Seguro que desea banear al usuario <strong>${name}</strong> (${email})?`
                : `¿Seguro que desea activar al usuario <strong>${name}</strong> (${email})?`,
            showCancelButton: true,
            confirmButtonText: isBan ? 'Sí, banear' : 'Sí, activar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: isBan ? '#ef4444' : '#10b981',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (!result.isConfirmed) return;

            const url  = isBan
                ? `/clientes/${id}/ban`
                : `/clientes/${id}/unban`;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Error del servidor');

                const row    = document.getElementById(`client-row-${id}`);
                const badge  = row.querySelector('.status-badge');
                const td     = row.querySelector('td:last-child');

                if (isBan) {
                    badge.textContent = 'Baneado';
                    badge.className   = 'status-badge status-banned';
                    td.innerHTML = `<button class="btn-unban"
                        data-id="${id}" data-name="${name}" data-email="${email}" data-action="unban">
                        <i class="fas fa-check-circle"></i> Activar
                    </button>`;
                } else {
                    badge.textContent = 'Activo';
                    badge.className   = 'status-badge status-active';
                    td.innerHTML = `<button class="btn-ban"
                        data-id="${id}" data-name="${name}" data-email="${email}" data-action="ban">
                        <i class="fas fa-ban"></i> Banear
                    </button>`;
                }

                Swal.fire({
                    icon: 'success',
                    title: isBan ? 'Usuario baneado' : 'Usuario activado',
                    timer: 1800,
                    showConfirmButton: false,
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo completar la operación. Intenta nuevamente.',
                });
            });
        });
    });
});

