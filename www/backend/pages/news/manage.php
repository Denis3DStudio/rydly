<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 p-0">
            <h1><i class="fa fa-fw fa-newspaper op-2"></i> Gestione Blog</h1>
        </div>
        <?php
        // Check if the deepl key is set
        if (!Base_Functions::IsNullOrEmpty(DEEPL_AUTH_KEY)) {
        ?>
            <div class="col-6 text-end p-0">
                <div id="deepl_container" style="display: none;">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target=".translation-modal-lg">
                        <i class="fas fa-fw fa-wand-magic-sparkles"></i> Traduci con DeepL
                    </button>
                </div>
            </div>
        <?php
        }
        ?>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">

                            <a class="nav-item nav-link text-dark" id="tabImages-tab" data-bs-toggle="tab"
                                href="#tabImages" role="tab" aria-controls="tabImages" aria-selected="true"
                                fileRender="dtContents"
                                tableId="dtContents"
                                macro="<?= Base_Files::NEWS ?>"
                                idType="<?= Base_Files_Types::IMAGE ?>">
                                <i class="fa fa-fw fa-images"></i> Immagini
                            </a>

                        </div>
                    </nav>

                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade" id="tabImages" role="tabpanel"
                                    aria-labelledby="tabImages-tab">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label>Contenuto</label>
                                                        <div class="form-inline">
                                                            <input class="form-control custom-render" type="file" name="Images[]" accept="image/*" multiple fileUpload fileUploadType="<?= Base_Files_Upload_Type::ON_CHANGE ?>" fileMacro="<?= Base_Files::NEWS ?>" fileType="<?= Base_Files_Types::IMAGE ?>" onclick="uploadManager('Images[]')" callback="getFilesManager('dtContents', <?= Base_Files::NEWS ?>, <?= Base_Files_Types::IMAGE ?>)" />
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-8">
                                            <div class="form-group">
                                                <label>
                                                    Tabella contenuti
                                                    <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-title="Trascina per modificare l'ordinamento"></i>
                                                </label>
                                                <div class="table-responsive">
                                                    <table class="table k-table k-table-hover k-table-responsive-cards has--actions" id="dtContents" style="width: 100%;"
                                                        fileMacro="<?= Base_Files::NEWS ?>"
                                                        fileType="<?= Base_Files_Types::IMAGE ?>"
                                                        callback="getFilesManager('dtContents', <?= Base_Files::NEWS ?>, <?= Base_Files_Types::IMAGE ?>)">
                                                    </table>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card mb-3 shadow">
                <div class="card-body" id="common_container">
                    <div class="form-group">
                        <label for="newsAuthor">Autore</label>
                        <input type="text" class="form-control" name="Author" mandatory>
                    </div>
                    <div class="form-group">
                        <label for="newsDate">Data</label>
                        <input type="date" class="form-control" name="Date" mandatory>
                    </div>
                    <div class="form-group">
                        <label for="newsCategory">Categoria</label>
                        <select id="categorySelect" name="Category" class="selectpicker" data-width="100%"
                            data-live-search="true" data-size="8" title="Seleziona categoria..." mandatory></select>
                    </div>
                    <div class="form-group">
                        <label for="newsStatus">Stato</label>
                        <select id="StatusSelect" name="Status" class="selectpicker" data-width="100%"
                            mandatory>
                            <option value="0">Bozza</option>
                            <option value="1">Pubblicato</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Luoghi</label>
                        <select id="placesSelect" name="Place" class="selectpicker" data-width="100%"
                            data-live-search="true" data-size="8" title="Seleziona luoghi..."
                            multiple></select>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-link text-danger" onclick="simpleDelete(<?= Base_Simple_Delete::NEWS ?>)">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>
                        <div class="col-6 text-end">
                            <button type="button" class="btn btn-success" onclick="saveNews()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>


<script type="text/html" id="language_tab_template">
    <div class="tab-pane fade" language="{{Language}}" name="tabLang" id="tabLang-{{Language}}" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" tabToTranslate>

        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Titolo</label>
                        <input type="text" class="form-control" name="Title" mandatory deepl_to_translate deepl_text_format="<?= Base_Text_Format::NORMAL ?>" />
                    </div>
                </div>
                <!-- <div class="col-12">
                    <div class="form-group">
                        <label>Testo Breve (Anteprima)</label>
                        <input type="text" class="form-control" name="Subtitle" deepl_to_translate deepl_text_format="<?= Base_Text_Format::NORMAL ?>" />
                    </div>
                </div> -->
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>Contenuto</label>
                        <textarea id="Description-{{Language}}" name="Description" class="form-control" rows="18" mandatory deepl_to_translate deepl_text_format="<?= Base_Text_Format::HTML ?>"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col">
                <div id="accordion-attachment" class="accordion-arrows">
                    <div class="card mb-3">
                        <div class="card-header collapsed p-2" id="a-panelAttachment{{Language}}-head" data-bs-toggle="collapse" data-bs-target="#a-panelAttachment{{Language}}" aria-expanded="true" aria-controls="a-panelAttachment{{Language}}" fileRender="dtAttachments{{Language}}">
                            <button class="btn btn-link text-dark" type="button"><i class="fas fa-fw fa-paperclip"></i>Allegati</button>
                        </div>
                        <div id="a-panelAttachment{{Language}}" class="collapse" aria-labelledby="a-panelAttachment{{Language}}-head" data-parent="#accordion-attachment">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Allegati</label>
                                            <div class="form-inline">
                                                <input class="form-control custom-render" type="file" name="Attachments{{Language}}" accept="*/*"
                                                    multiple
                                                    fileUpload
                                                    fileUploadType="<?= Base_Files_Upload_Type::ON_CHANGE ?>"
                                                    fileMacro="<?= Base_Files::NEWS ?>"
                                                    fileType="<?= Base_Files_Types::ATTACHMENT ?>"
                                                    hasFileExtra
                                                    fileExtra-Id-Language="{{Language}}"
                                                    renderTableId="dtAttachments{{Language}}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>
                                                Tabella allegati
                                                <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    data-bs-title="Trascina per modificare l'ordinamento"></i>
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table k-table k-table-hover k-table-responsive-cards has--actions" id="dtAttachments{{Language}}" style="width: 100%;"
                                                    fileMacro="<?= Base_Files::NEWS ?>"
                                                    fileType="<?= Base_Files_Types::ATTACHMENT ?>"
                                                    hasFileExtra
                                                    fileExtra-Id-Language="{{Language}}"
                                                    hasCustomButtons="<?= Base_Files_Captions_Types::BUTTONS[Base_Files_Captions_Types::MONO_LANG] ?>"></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div id="accordion-link" class="accordion-arrows">
                    <div class="card mb-3">
                        <div class="card-header collapsed p-2" id="a-panelLink{{Language}}-head" data-bs-toggle="collapse" data-bs-target="#a-panelLink{{Language}}" aria-expanded="true" aria-controls="a-panelLink{{Language}}" renderLinks="{{Language}}">
                            <button class="btn btn-link text-dark" type="button"><i class="fas fa-fw fa-link"></i>Link</button>
                        </div>
                        <div id="a-panelLink{{Language}}" class="collapse" aria-labelledby="a-panelLink{{Language}}-head" data-parent="#accordion-link">
                            <div class="card-body">

                                <div class="row" id="link_container_{{Language}}">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Link</label>
                                            <div class="input-group mb-3">
                                                <input type="text" id="link-{{Language}}" class="form-control" mandatory>
                                                <button type="button" class="btn btn-block btn-outline-success" onclick="insertLink('{{Language}}')"><i class="fa fa-fw fa-plus"></i> Aggiungi</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>
                                                Tabella Link
                                                <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                                    data-bs-title="Trascina per modificare l'ordinamento"></i>
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table k-table k-table-hover k-table-responsive-cards has--actions" id="dtLinks{{Language}}" style="width: 100%;"></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</script>