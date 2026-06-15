@php
    $exportQuery = \App\Services\Admin\AdminInventoryExportQuery::queryStringFromRequest(request());
@endphp

<div class="inventory-catalog-io">
    <div class="inventory-export-dropdown">
        <button type="button" class="btn btn-secondary" id="inventory-export-toggle" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-file-export" aria-hidden="true"></i> Exportar
        </button>
        <div class="inventory-export-menu" id="inventory-export-menu" role="menu" aria-label="Opciones de exportación" hidden>
            <a href="{{ route('products.export', ['format' => 'bundle']).$exportQuery.($exportQuery ? '&' : '?').'scope=all' }}" class="inventory-export-menu__item inventory-export-menu__item--primary" role="menuitem">
                <i class="fas fa-file-archive" aria-hidden="true"></i> Catálogo completo (ZIP + imágenes)
            </a>
            <a href="{{ route('products.export', ['format' => 'bundle']).$exportQuery }}" class="inventory-export-menu__item" role="menuitem">
                <i class="fas fa-filter" aria-hidden="true"></i> ZIP con filtros actuales
            </a>
            <a href="{{ route('products.export', ['format' => 'json']).$exportQuery }}" class="inventory-export-menu__item" role="menuitem">
                <i class="fas fa-file-code" aria-hidden="true"></i> JSON (datos)
            </a>
            <a href="{{ route('products.export', ['format' => 'xml']).$exportQuery }}" class="inventory-export-menu__item" role="menuitem">
                <i class="fas fa-file-code" aria-hidden="true"></i> XML
            </a>
            <a href="{{ route('products.export', ['format' => 'excel']).$exportQuery }}" class="inventory-export-menu__item" role="menuitem">
                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
            </a>
            <a href="{{ route('products.export', ['format' => 'pdf']).$exportQuery }}" class="inventory-export-menu__item" role="menuitem">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
            </a>
        </div>
    </div>
    <button type="button" class="btn btn-secondary" id="open-import-modal">
        <i class="fas fa-file-import" aria-hidden="true"></i> Importar
    </button>
</div>

<div class="edit-modal" id="import-modal"
    data-active-url="{{ route('products.import.active') }}"
    data-progress-url="{{ route('products.import.progress', ['importId' => '__ID__']) }}"
    data-dismiss-url="{{ route('products.import.dismiss') }}">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-auto-size">
        <div class="modal-header">
            <h3>Importar productos</h3>
            <button type="button" class="modal-close" id="close-import-modal" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="import-form" method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <p class="import-modal-intro">
                    Acepta archivos de proveedor en <strong>CSV</strong>, <strong>XML</strong> o <strong>JSON</strong>
                    con columnas en cualquier orden (nombre, precio, stock, categoría, marca, etc.).
                    Para migrar el catálogo completo con fotos, use el <strong>ZIP</strong> exportado desde aquí.
                </p>
                <div class="import-format-hints" aria-hidden="true">
                    <span class="import-format-chip import-format-chip--xml"><i class="fas fa-file-code"></i> XML</span>
                    <span class="import-format-chip import-format-chip--csv"><i class="fas fa-file-csv"></i> CSV</span>
                    <span class="import-format-chip"><i class="fas fa-file-archive"></i> ZIP</span>
                    <span class="import-format-chip import-format-chip--json"><i class="fas fa-file-alt"></i> JSON</span>
                </div>
                <x-shared.file-upload
                    id="import_file"
                    name="import_file"
                    accept=".zip,.xml,.csv,.txt,.json"
                    icon="fa-cloud-upload-alt"
                    hint="ZIP, XML, CSV o JSON — máx. 100 MB"
                    variant="compact"
                >
                    Haz clic o arrastra un archivo aquí
                </x-shared.file-upload>
                <div id="import-file-summary" class="import-file-summary hidden" aria-live="polite"></div>
                <p class="import-modal-requirements">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Los productos existentes se actualizan por ID, SKU o nombre+categoría. Los campos faltantes usan valores por defecto.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-import">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirm-import" disabled>Importar</button>
            </div>
        </form>

        <div id="import-queue" class="import-progress import-queue" hidden aria-live="polite">
            <div class="modal-body">
                <div class="import-progress__head">
                    <span class="import-progress__icon" data-state="running">
                        <i class="fas fa-list-check" aria-hidden="true"></i>
                    </span>
                    <div class="import-progress__headings">
                        <h4 class="import-progress__title">Cola de importaciones</h4>
                        <p class="import-progress__file">Procesos recientes de importación de productos.</p>
                    </div>
                </div>

                <div class="import-queue__list" id="import-queue-list"></div>

                <p class="import-progress__hint">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Seleccioná <strong>Ver</strong> para consultar el avance de una importación sin iniciar otra carga.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="import-queue-close">Cerrar</button>
                <button type="button" class="btn btn-primary" id="import-queue-new">Nueva importación</button>
            </div>
        </div>

        <div id="import-progress" class="import-progress" hidden aria-live="polite">
            <div class="modal-body">
                <div class="import-progress__head">
                    <span class="import-progress__icon" id="import-progress-icon" data-state="running">
                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                    </span>
                    <div class="import-progress__headings">
                        <h4 class="import-progress__title" id="import-progress-title">Importando productos…</h4>
                        <p class="import-progress__file" id="import-progress-file"></p>
                    </div>
                </div>

                <div class="import-progress__bar" id="import-progress-bar" role="progressbar"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="import-progress__fill" id="import-progress-fill" style="width: 0%"></div>
                </div>

                <p class="import-progress__message" id="import-progress-message">En cola, iniciando…</p>

                <div class="import-progress__stats">
                    <span class="import-progress__stat"><strong id="import-stat-created">0</strong> creados</span>
                    <span class="import-progress__stat"><strong id="import-stat-updated">0</strong> actualizados</span>
                    <span class="import-progress__stat"><strong id="import-stat-skipped">0</strong> omitidos</span>
                    <span class="import-progress__stat import-progress__stat--errors"><strong id="import-stat-errors">0</strong> errores</span>
                </div>

                <p class="import-progress__hint" id="import-progress-hint">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    Podés cerrar esta ventana y seguir trabajando; la importación continúa.
                    Volvé a <strong>Importar</strong> para ver el avance.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="import-progress-close">Cerrar</button>
                <button type="button" class="btn btn-primary" id="import-progress-done" hidden>Listo</button>
            </div>
        </div>
    </div>
</div>
