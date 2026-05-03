<dialog id="cf4-export-modal" class="cf4-export-modal" aria-labelledby="cf4-export-modal-title">
    <form method="dialog" class="cf4-export-modal__inner">
        <header class="cf4-export-modal__header">
            <div>
                <h3 id="cf4-export-modal-title" class="cf4-export-modal__title">Exportar</h3>
                <p id="cf4-export-modal-subtitle" class="cf4-export-modal__subtitle"></p>
            </div>
            <button type="button" class="cf4-export-modal__close" data-export-modal-close aria-label="Cerrar">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <div class="cf4-export-modal__body">
            <fieldset class="cf4-export-modal__scope" aria-label="Alcance de la exportación">
                <label class="cf4-export-modal__radio">
                    <input type="radio" name="scope" value="all" checked>
                    <span>Todo</span>
                </label>
                <label class="cf4-export-modal__radio">
                    <input type="radio" name="scope" value="filtered">
                    <span>Con filtros</span>
                </label>
            </fieldset>

            <div class="cf4-export-modal__filters" data-export-modal-filters></div>
        </div>

        <footer class="cf4-export-modal__footer">
            <button type="button" class="cf4-export-modal__btn cf4-export-modal__btn--ghost" data-export-modal-close>Cancelar</button>
            <button type="button" class="cf4-export-modal__btn cf4-export-modal__btn--primary" data-export-modal-submit>
                Exportar
            </button>
        </footer>
    </form>
</dialog>

