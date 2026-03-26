<div class="container-fluid" id="container">
    <div class="row page-head">
        <div class="col-6 p-0">
            <h1><i class="fa fa-fw fa-language"></i> Modifica Traduzione</h1>
        </div>
        <div class="col-6 text-end p-0">
            <?php
            // Check if the deepl key is set
            if (!Base_Functions::IsNullOrEmpty(DEEPL_AUTH_KEY)) {
            ?>
                <div id="deepl_container" style="display: none;">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target=".translation-modal-lg">
                        <i class="fas fa-fw fa-wand-magic-sparkles"></i> Traduci con DeepL
                    </button>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>Sezione</label>
                        <input type="text" name="Section" class="form-control" mandatory>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Pagina</label>
                        <input type="text" name="Page" class="form-control" mandatory>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Etichetta</label>
                        <input type="text" name="Label" class="form-control" mandatory>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist"></div>
                    </nav>

                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent" deepl_container></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card mb-3 shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <span><b>Creato da:</b> <i name="CreatorName"></i></span><br />
                                <span><b>Ultima modifica:</b> <i name="LastModifierName"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label>Note</label>
                                <textarea class="form-control" name="Note" id="Note" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="FormatText" id="<?= Base_Text_Format::NORMAL ?>" checked>
                                <label class="form-check-label" for="<?= Base_Text_Format::NORMAL ?>">
                                    Plain text
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="FormatText" id="<?= Base_Text_Format::HTML ?>">
                                <label class="form-check-label" for="<?= Base_Text_Format::HTML ?>">
                                    HTML
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="FormatText" id="<?= Base_Text_Format::URL ?>">
                                <label class="form-check-label" for="<?= Base_Text_Format::URL ?>" tooltip="La lingua di partenza deve essere già un url">
                                    URL
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-end">

                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-link text-danger me-auto" onclick="simpleDelete(<?= Base_Simple_Delete::TRANSLATION ?>)">
                            <i class="fas fa-trash"></i> Elimina
                        </button>

                        <div class="btn-group me-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('${row.Page}.${row.Label}')" tooltip="Copia negli Appunti"><i class="fa fa-fw fa-copy"></i></button>
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" onclick="copyPageLabel()">P.L.</a></li>
                                <li><a class="dropdown-item" onclick="copyPageLabel(<?= Base_Programming_Language::PHP ?>)">PHP</a></li>
                                <li><a class="dropdown-item" onclick="copyPageLabel(<?= Base_Programming_Language::JS ?>)">JS</a></li>
                            </ul>
                        </div>

                        <button type="button" class="btn btn-success" onclick="saveTranslation()">
                            <i class="fas fa-fw fa-save"></i> Salva
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-3 shadow" id="dtPlaceholderWordCard">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <label>
                                Placeholders:
                            </label>
                            <div id="placeholderWordContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="text/html" id="language_tab_template">
    <div class="tab-pane fade" id="tabLang-{{Language}}" name="tabLang" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" language="{{Language}}" tabToTranslate>
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label>Contenuto *</label>
                    <textarea name="Translation" id="Translation-{{Language}}" class="form-control js-editor summernote" rows="18" deepl_to_translate deepl_text_format="<?= Base_Text_Format::HTML ?>"></textarea>
                </div>
            </div>
        </div>
    </div>
</script>