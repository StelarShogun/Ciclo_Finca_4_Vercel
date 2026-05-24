/**
 * Styled file upload zone (label trigger + hidden input + meta preview).
 */

function formatFileSize(bytes) {
    if (!bytes) return '';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${Math.round((bytes / 1024 ** i) * 100) / 100} ${sizes[i]}`;
}

export function initFileUploadZone({
    inputId,
    metaId,
    triggerId,
    imagePreview = false,
    onChange,
}) {
    const input = document.getElementById(inputId);
    const meta = metaId ? document.getElementById(metaId) : null;
    const trigger = triggerId
        ? document.getElementById(triggerId)
        : document.querySelector(`label[for="${inputId}"]`);

    if (!input) return null;

    function hideMeta() {
        if (!meta) return;
        meta.classList.add('hidden');
        meta.innerHTML = '';
    }

    function showMeta({ name, size, previewUrl, onRemove }) {
        if (!meta) return;
        meta.classList.remove('hidden');
        const thumb = previewUrl && imagePreview
            ? `<img src="${previewUrl}" alt="" class="cf-file-upload-meta__thumb">`
            : `<i class="fas fa-file cf-file-upload-meta__thumb" style="width:56px;text-align:center;line-height:56px;font-size:1.5rem;color:var(--color-primary,#235347);"></i>`;
        meta.innerHTML = `
            ${thumb}
            <div class="cf-file-upload-meta__body">
                <div class="cf-file-upload-meta__name">${name}</div>
                <div class="cf-file-upload-meta__size">${size}</div>
            </div>
            <button type="button" class="cf-file-upload-meta__remove">Quitar</button>
        `;
        meta.querySelector('.cf-file-upload-meta__remove')?.addEventListener('click', () => {
            input.value = '';
            hideMeta();
            if (trigger) trigger.style.display = '';
            if (typeof onRemove === 'function') onRemove();
            if (typeof onChange === 'function') onChange(null);
        });
    }

    function handleFiles(fileList) {
        const file = fileList?.[0];
        if (!file) {
            hideMeta();
            if (trigger) trigger.style.display = '';
            if (typeof onChange === 'function') onChange(null);
            return;
        }

        if (imagePreview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = () => {
                showMeta({
                    name: file.name,
                    size: formatFileSize(file.size),
                    previewUrl: reader.result,
                });
                if (trigger) trigger.style.display = 'none';
                if (typeof onChange === 'function') onChange(file);
            };
            reader.readAsDataURL(file);
            return;
        }

        showMeta({
            name: file.name,
            size: formatFileSize(file.size),
        });
        if (trigger) trigger.style.display = 'none';
        if (typeof onChange === 'function') onChange(file);
    }

    function showGallerySummary(fileList) {
        if (!meta || !fileList?.length) {
            hideMeta();
            return;
        }
        const images = Array.from(fileList).filter((f) => f.type?.startsWith('image/'));
        const names = images.slice(0, 5).map((f) => f.name).join(', ');
        const more = images.length > 5 ? ` y ${images.length - 5} más` : '';
        meta.classList.remove('hidden');
        meta.innerHTML = `
            <div class="cf-file-upload-meta__body" style="width:100%;">
                <div class="cf-file-upload-meta__name">${images.length} imagen${images.length === 1 ? '' : 'es'} seleccionada${images.length === 1 ? '' : 's'}</div>
                <div class="cf-file-upload-meta__size">${names}${more}</div>
            </div>
            <button type="button" class="cf-file-upload-meta__remove">Quitar</button>
        `;
        meta.querySelector('.cf-file-upload-meta__remove')?.addEventListener('click', () => {
            input.value = '';
            hideMeta();
            if (typeof onChange === 'function') onChange(null);
        });
        if (typeof onChange === 'function') onChange(images);
    }

    input.addEventListener('change', () => {
        if (input.multiple) {
            showGallerySummary(input.files);
        } else {
            handleFiles(input.files);
        }
    });

    if (trigger) {
        const opensInputNatively =
            trigger.tagName === 'LABEL' &&
            trigger.getAttribute('for') === input.id;

        if (!opensInputNatively) {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                input.click();
            });
        }

        ['dragenter', 'dragover'].forEach((ev) => {
            trigger.addEventListener(ev, (e) => {
                e.preventDefault();
                trigger.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach((ev) => {
            trigger.addEventListener(ev, (e) => {
                e.preventDefault();
                trigger.classList.remove('is-dragover');
            });
        });
        trigger.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (!files?.length) return;
            input.files = files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    return {
        reset() {
            input.value = '';
            hideMeta();
            if (trigger) trigger.style.display = '';
        },
        showGallerySummary,
    };
}
