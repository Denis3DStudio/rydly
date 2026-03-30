<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 p-0">
            <h1><i class="fa fa-fw fa-user-tie op-2"></i> Gestione Organizzatore</h1>
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

                            <a class="nav-item nav-link text-dark active" id="tabGeneral-tab" data-bs-toggle="tab" href="#tabGeneral" role="tab" aria-controls="tabGeneral" aria-selected="true">
                                <i class="fa fa-fw fa-gears"></i> Generali
                            </a>
                            <a class="nav-item nav-link text-dark" id="tabImages-tab" data-bs-toggle="tab" href="#tabImages" role="tab" aria-controls="tabImages" fileRender="dtContents">
                                <i class="fa fa-fw fa-images"></i> Immagini
                            </a>

                        </div>
                    </nav>

                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <!-- Container tab -->
                            <div class="tab-content" id="nav-tabContent">
                                <!-- Tab General -->
                                <div class="tab-pane fade show active" id="tabGeneral" role="tabpanel" aria-labelledby="tabGeneral-tab">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label for="Latitude">Nome</label>
                                                <input type="text" class="form-control" id="Name" name="Name" mandatory>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="Address">Indirizzo completo</label>
                                                <input type="text" class="form-control" id="Address" name="Address">
                                            </div>
                                        </div>
                                        <div class="col-lg-4" to_hide>
                                            <div class="form-group">
                                                <label for="City">Città</label>
                                                <input type="text" class="form-control" id="City" name="City" mandatory>
                                            </div>
                                        </div>
                                        <div class="col-lg-4" to_hide>
                                            <div class="form-group">
                                                <label for="Latitude">Latitudine Google Maps</label>
                                                <input type="text" class="form-control" id="Latitude" name="Latitude" mandatory>
                                            </div>
                                        </div>
                                        <div class="col-lg-4" to_hide>
                                            <div class="form-group">
                                                <label for="Longitude">Longitudine Google Maps</label>
                                                <input type="text" class="form-control" id="Longitude" name="Longitude" mandatory>
                                            </div>
                                        </div>
                                        <div class="col-lg-4" to_hide>
                                            <div class="form-group">
                                                <label for="Phone">Telefono</label>
                                                <input type="text" class="form-control" id="Phone" name="Phone">
                                            </div>
                                        </div>

                                        <div class="col-lg-4" to_hide>
                                            <label for="">&nbsp;</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="UseOnlyCoordinates" name="UseOnlyCoordinates" trigger_change />
                                                <label class="form-check-label" for="UseOnlyCoordinates">
                                                    Utilizza solo le coordinate per le indicazioni stradali
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Note</label>
                                                <textarea name="Notes" class="form-control" rows="5"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Images -->
                                <div class="tab-pane" id="tabImages" role="tabpanel" aria-labelledby="tabImages-tab" fileRender="dtContents">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label>Contenuto</label>
                                                        <div class="form-inline">
                                                            <input class="form-control custom-render" type="file" name="Images[]" accept="image/*" multiple fileUpload fileUploadType="<?= Base_Files_Upload_Type::ON_CHANGE ?>" fileMacro="<?= Base_Files::ORGANIZER ?>" fileType="<?= Base_Files_Types::IMAGE ?>" onclick="uploadManager('Images[]')" callback="getFilesManager('dtContents', <?= Base_Files::ORGANIZER ?>, <?= Base_Files_Types::IMAGE ?>)" />
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
                                                        fileMacro="<?= Base_Files::ORGANIZER ?>"
                                                        fileType="<?= Base_Files_Types::IMAGE ?>"
                                                        callback="getFilesManager('dtContents', <?= Base_Files::ORGANIZER ?>, <?= Base_Files_Types::IMAGE ?>)">
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
                        <label for="Categories">Categorie</label>
                        <select id="Categories" name="Categories" class="selectpicker" data-width="100%" multiple data-live-search="true" data-size="8" title="Seleziona..." mandatory trigger_change>
                        </select>
                    </div>

                    <div class="form-group" id="main_category_container" style="display: none;">
                        <label for="MainCategory">Categoria Principale</label>
                        <select id="MainCategory" name="MainCategory" class="selectpicker" data-width="100%" data-live-search="true" data-size="8" title="Seleziona..."></select>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="IsActive">Visualizzazione</label><br>
                                <select class="selectpicker form-control" name="IsActive" id="IsActive" data-title="Seleziona..." data-width="100%" mandatory>
                                    <option value="1">Pubblicata</option>
                                    <option value="0">Bozza</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-link text-danger" onclick="simpleDelete(<?= Base_Simple_Delete::ORGANIZER ?>)">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>
                        <div class="col-6 text-end">
                            <button type="button" class="btn btn-success" onclick="saveOrganizer()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
    <input type="hidden" id="showFirstLanguageTab" value="0" />

</div>

<script type="text/html" id="language_tab_template">
    <div class="tab-pane fade" language="{{Language}}" name="tabLang" id="tabLang-{{Language}}" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" tabToTranslate>
        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Descrizione Breve (Anteprima)</label>
                        <textarea name="SmallDescription" class="form-control" rows="2" deepl_to_translate deepl_text_format="<?= Base_Text_Format::NORMAL ?>" mandatory></textarea>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label>Descrizione</label>
                        <textarea name="Description" class="form-control js-editor summernote" rows="5" deepl_to_translate deepl_text_format="<?= Base_Text_Format::NORMAL ?>" mandatory></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>