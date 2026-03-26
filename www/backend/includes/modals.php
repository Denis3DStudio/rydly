<!-- Modal Caption -->
<div class="modal fade" id="modalCaption" tabindex="-1" role="dialog" aria-labelledby="modalCaptionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5">Gestione didascalia</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="modal-body mb-0" id="modalCaptionBody" style="background-color: var(--bg-main);">

                <input type="hidden" name="ContentRefId">
                <input type="hidden" name="ContentType">
                <div class="row">
                    <div class="col">
                        <nav>
                            <div class="nav nav-tabs" id="caption-image-nav-tab" role="tablist">
                            </div>
                        </nav>
                        <div class="card mb-3 border-0">
                            <div class="card-body">
                                <div class="tab-content" id="caption-image-nav-tabContent">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button class="btn btn-light me-auto" type="button" data-bs-dismiss="modal"
                    aria-label="Close">Annulla</button>
                <button class="btn btn-success" type="button" onclick="saveContentCaption()"><i class="fa fa-fw fa-save"></i> Salva</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalCaptionFileManager" tabindex="-1" role="dialog" aria-labelledby="modalCaptionFileManagerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestione didascaliaaaaaaa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="modal-body mb-0" id="modalCaptionFileManagerBody">

                <div id="modalCaptionFileManagerGeneric">
                    <input type="hidden" name="IdFile">
                    <input type="hidden" name="Macro">
                    <input type="hidden" name="Type">
                    <input type="hidden" name="IdRow">
                    <input type="hidden" name="Format">
                </div>

                <div id="modalCaptionFileManagerFields">

                </div>
            </form>
            <div class="modal-footer">
                <button class="btn btn-light me-auto" type="button" data-bs-dismiss="modal" aria-label="Close">Annulla</button>
                <button class="btn btn-success" type="button" onclick="saveFilesManagerCaption()"><i class="fa fa-fw fa-save"></i> Salva</button>
            </div>
        </div>
    </div>
</div>

<?php
// Check if the deepl key is set
if (!Base_Functions::IsNullOrEmpty(DEEPL_AUTH_KEY)) {
?>
    <div class="modal fade translation-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="modalConfirmTranslations" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h1 class="modal-title fs-5" id="modalConfirmGenericText">
                        Traduci con DeepL
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="select_translations_container">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="">Campi da tradurre: </label>
                                <select id="ToTranslate" name="ToTranslate" class="selectpicker" data-width="100%" data-live-search="true" data-size="8" title="Seleziona gli input da tradurre..." multiple>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="languages_container">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="LanguageFrom">Lingua di partenza: *</label>
                                <select id="LanguageFrom" name="LanguageFrom" class="selectpicker" data-width="100%" data-live-search="true" data-size="8" title="Seleziona lingue...">
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="LanguagesTo">Lingue da tradurre: *</label>
                                <select id="LanguagesTo" name="LanguagesTo" class="selectpicker" data-width="100%" data-live-search="true" data-size="8" title="Seleziona lingue..." multiple>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light me-auto" type="button" id="cancelGenericBtn" data-bs-dismiss="modal">Annulla</button>
                    <button class="btn btn-outline-secondary" type="button" id="btnConfirmDeepl" onclick="getTranslationDeepl(false)">Traduci</button>
                    <button class="btn btn-success ms-3" type="button" id="btnConfirmDeeplSave" onclick="getTranslationDeepl(true)"><i class="fa fa-fw fa-save"></i> Traduci e Salva</button>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>

<!-- Modal Edit File Name -->
<div class="modal fade" id="editFileModal" tabindex="-1" role="dialog" aria-labelledby="editFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modifica File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="row" id="editFileContinerRender">
                <input type="hidden" name="TableId">
            </div>
            <div class="row" id="editFileContinerGeneric">
                <input type="hidden" name="IdRow">
                <input type="hidden" name="Macro">
                <input type="hidden" name="Type">
            </div>
            <div class="modal-body" id="editFileContiner">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>File Name</label>
                            <input type="text" class="form-control" name="FileName" mandatory />
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" onclick="updateFileManager()">Salva</button>
            </div>
        </div>
    </div>
</div>