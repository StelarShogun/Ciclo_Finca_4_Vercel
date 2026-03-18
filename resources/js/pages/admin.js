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

