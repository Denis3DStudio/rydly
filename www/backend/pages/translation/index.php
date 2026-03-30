<div class="container-fluid">
    <div class="row page-head">
        <div class="col-12 col-md-3 p-0">
            <h1><i class="fa fa-fw fa-language op-2"></i> Traduzioni</h1>
        </div>
        <div class="col-12 col-md-9 pe-0 text-end">
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-fw fa-cogs"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fa fa-fw fa-file-import op-5"></i> Importa
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" onclick="exportTranslations()">
                            <i class="fa fa-fw fa-file-export op-5"></i> Esporta
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                            <i class="fa fa-fw fa-clone op-5"></i> Duplica
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fa fa-fw fa-file-export op-5"></i> Esporta custom
                        </button>
                    </li>

                    <?php if (!Base_Functions::IsNullOrEmpty(DEEPL_AUTH_KEY)): ?>

                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li id="deepl_container">
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target=".translation-modal-lg">
                                <i class="fas fa-fw fa-wand-magic-sparkles op-5"></i> Traduci tutto con DeepL
                            </button>
                        </li>

                    <?php endif ?>

                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" onclick="deleteCache()">
                            <i class="fa fa-fw fa-refresh op-5"></i> Pulisci cache
                        </button>
                    </li>
                </ul>
            </div>
            <button type="button" class="btn btn-success ms-4" onclick="createTranslation()">
                <i class="fa fa-fw fa-plus"></i> Aggiungi
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table k-table k-table-hover k-table-responsive-cards has--actions has-4-actions" id="dtTranslation" width="100%" cellspacing="0"></table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="importModalLabel">Importa traduzioni</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label>JSON File</label>
                <input type="file" id="importTranslationsFile" class="form-control" accept="application/json">

                <div class="form-check mt-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        value=""
                        id="overwriteImportCheckbox"
                        name="overwriteImportCheckbox" />
                    <label class="form-check-label" for="overwriteImportCheckbox">
                        Sovrascrivi traduzioni diverse
                    </label>
                </div>

                <div class="mt-4" id="importChangedTranslationsContainer" style="display:none">
                    <h5>Traduzioni diverse</h5>
                    <span id="importChangedTranslations"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" onclick="importTranslations()">Importa</button>
            </div>
        </div>
    </div>
</div>

<!-- Help -->
<div class="modal fade" id="modalTranslationHelp" tabindex="-1" role="dialog" aria-labelledby="modalTranslationHelpLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="modalTranslationHelpLabel">Come funziona?</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Puoi inserire le seguenti querystring sul sito da tradurre:</p>

                <p class="mb-0">
                    <strong><code>?show_marked_translation</code></strong>
                </p>
                <p>
                    Mostra in rosso le traduzioni che sono presenti sul
                    translator
                </p>

                <p class="mb-0">
                    <strong><code>?show_label_not_translation</code></strong>
                </p>
                <p>
                    Non mostra le traduzioni ma le label del translator
                </p>

            </div>
        </div>
    </div>
</div>

<!-- Duplicate Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1" role="dialog" aria-labelledby="duplicateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="duplicateModalLabel">Duplica traduzioni avanzata</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="duplicate_container">

                <div class="row mb-4">
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                    Istruzioni
                                </button>
                            </h2>
                            <div id="collapseOne" class="collapse" aria-labelledby="collapseOne" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <p>Seleziona le traduzioni che vuoi duplicare e inserisci la pagina di destinazione a cui appartengono.</p>
                                    <p>Seleziona l'opzione <strong>"Traduci tutte, anche se già presenti"</strong> per duplicare tutte le traduzioni, anche se già presenti.</p>
                                    <p>Se la traduzione esiste verrà creata con SECTION.NEW_PAGE.LABEL<strong>_IdTranslation</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Traduzioni</label>
                            <select id="IdsTranslations" name="IdsTranslations" class="selectpicker" data-width="100%" data-live-search="true" data-actions-box="true" data-size="8" title="Seleziona traduzioni..." mandatory multiple>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Pagina</label>
                            <input type="text" name="Page" class="form-control" mandatory>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="SaveAlways" name="SaveAlways" />
                            <label class="form-check-label" for="SaveAlways">
                                Traduci tutte, anche se già presenti
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="duplicate_errors" style="display: none;">

                <div class="row">
                    <div class="col-12 duplicate_errors" id="errors_container" style="display: none;">
                        <h1 class="modal-title fs-5" id="duplicateModalLabel">Traduzioni duplicate con LABEL diversa</h1>
                        <div class="mt-2 mb-2" id="duplicatesErrors"></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="duplicateTranslations" onclick="duplicateTranslations()">Duplica</button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="exportModalLabel">Esporta traduzioni avanzata</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="export_container">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Traduzioni</label>
                            <select id="IdsTranslations" name="IdsTranslations" class="selectpicker" data-width="100%" data-live-search="true" data-actions-box="true" data-size="8" title="Seleziona traduzioni..." mandatory multiple>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="exportTranslations" onclick="exportTranslations(false)">Esporta</button>
            </div>
        </div>
    </div>
</div>