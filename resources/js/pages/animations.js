/**
 * ===== ANIMATIONS.JS =====
 * Funciones de animación para la interfaz de usuario
 */

/**
 * Animación de fade-in para elementos
 */
function fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.transition = `opacity ${duration}ms ease`;
    
    setTimeout(() => {
        element.style.opacity = '1';
    }, 10);
}

/**
 * Animación de fade-out para elementos
 */
function fadeOut(element, duration = 300) {
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

/**
 * Animación de slide-up para elementos
 */
function slideUp(element, duration = 300) {
    element.style.maxHeight = element.scrollHeight + 'px';
    element.style.transition = `max-height ${duration}ms ease`;
    
    setTimeout(() => {
        element.style.maxHeight = '0';
    }, 10);
}

/**
 * Animación de slide-down para elementos
 */
function slideDown(element, duration = 300) {
    element.style.maxHeight = '0';
    element.style.transition = `max-height ${duration}ms ease`;
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.maxHeight = element.scrollHeight + 'px';
    }, 10);
}

/**
 * Animación de números incrementales
 */
function animateNumber(element, start, end, duration = 1000) {
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        element.textContent = current.toLocaleString();
        
        if (current === end) {
            clearInterval(timer);
        }
    }, stepTime);
}

/**
 * Animación de carga para elementos
 */
function showLoading(element) {
    element.style.position = 'relative';
    element.innerHTML += '<div class="loading-spinner" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>';
    
    // Agregar keyframe si no existe
    if (!document.getElementById('spin-animation')) {
        const style = document.createElement('style');
        style.id = 'spin-animation';
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }
}

function hideLoading(element) {
    const spinner = element.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Animación de shake para elementos (útil para errores)
 */
function shake(element) {
    element.style.animation = 'shake 0.5s';
    
    // Agregar keyframe si no existe
    if (!document.getElementById('shake-animation')) {
        const style = document.createElement('style');
        style.id = 'shake-animation';
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
                20%, 40%, 60%, 80% { transform: translateX(10px); }
            }
        `;
        document.head.appendChild(style);
    }
    
    setTimeout(() => {
        element.style.animation = '';
    }, 500);
}

/**
 * Animación de pulse para elementos
 */
function pulse(element, times = 3, duration = 1000) {
    let count = 0;
    const originalOpacity = element.style.opacity;
    
    const pulseInterval = setInterval(() => {
        element.style.opacity = element.style.opacity === '0.5' ? '1' : '0.5';
        count++;
        
        if (count >= times * 2) {
            clearInterval(pulseInterval);
            element.style.opacity = originalOpacity;
        }
    }, duration / (times * 2));
}

/**
 * Animar elementos al cargar la página usando Intersection Observer
 */
function animateOnLoad() {
    const animatedElements = document.querySelectorAll('.animate-on-load');
    
    if (animatedElements.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Inicializar elementos con estilo de inicio
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });
}

/**
 * Animación de scroll suave
 */
function smoothScrollTo(target, duration = 500) {
    const targetElement = typeof target === 'string' ? document.querySelector(target) : target;
    
    if (!targetElement) return;
    
    const targetPosition = targetElement.offsetTop;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;
    
    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);
        
        window.scrollTo(0, startPosition + distance * progress);
        
        if (progress < 1) {
            requestAnimationFrame(animation);
        }
    }
    
    requestAnimationFrame(animation);
}

/**
 * Agregar clase a elementos al hacer scroll
 */
function addClassOnScroll(selector, className, offset = 100) {
    const elements = document.querySelectorAll(selector);
    
    window.addEventListener('scroll', () => {
        const scrollPosition = window.pageYOffset;
        
        elements.forEach(element => {
            if (scrollPosition > element.offsetTop - offset) {
                element.classList.add(className);
            }
        });
    });
}

/**
 * Función para animar tooltips
 */
function showTooltip(element, message, duration = 3000) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-message';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        position: fixed;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    setTimeout(() => {
        tooltip.style.opacity = '1';
    }, 10);
    
    setTimeout(() => {
        tooltip.style.opacity = '0';
        setTimeout(() => tooltip.remove(), 300);
    }, duration);
}

// Ejecutar animaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Animar elementos al cargar
    animateOnLoad();
    
    // Agregar event listeners para botones comunes
    document.querySelectorAll('.btn-animate').forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    // Animar modales
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                fadeOut(this, 200);
            }
        });
    });
});

// Exportar funciones para uso global
window.Animations = {
    fadeIn,
    fadeOut,
    slideUp,
    slideDown,
    animateNumber,
    showLoading,
    hideLoading,
    shake,
    pulse,
    animateOnLoad,
    smoothScrollTo,
    addClassOnScroll,
    showTooltip
};
