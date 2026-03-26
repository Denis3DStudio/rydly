<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-location-dot op-2"></i> Modifica Categoria Luogo</h1>
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

    <form class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link text-dark" id="tabImages-tab" data-bs-toggle="tab" href="#tabImages" role="tab" aria-controls="tabImages" aria-selected="true">
                                <i class="fa fa-fw fa-images"></i> Immagini
                            </a>
                        </div>
                    </nav>
                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade" id="tabImages" role="tabpanel" aria-labelledby="tabImages-tab">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <div class="d-flex">
                                                <div img_preview="img_preview" class="flex-shrink-0" style="display: none">
                                                    <img style="width: 112px; max-width: 112px; height: 112px" class="object-fit-contain border bg-white me-3" />
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label>Immagine</label>
                                                    <div class="input-group mb-3">
                                                        <input name="Image" type="file" class="form-control" />
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" type="button" onclick="uploadImages()">
                                                            <i class="fa fa-fw fa-upload"></i> Carica
                                                        </button>
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
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">

                <div class="card-body" id="common_container">
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
                        <div class="col-6" id="deleteBtnContainer">
                            <button type="button" id="deleteBtn" class="btn btn-sm btn-link text-danger" onclick="deleteCategory()">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-success" onclick="save()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script type="text/html" id="language_tab_template">
    <form class="tab-pane fade" id="tabLang-{{Language}}" name="tabLang" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" language="{{Language}}" tabToTranslate>
        <div class="row">
            <div class="form-group col-12">
                <label>Titolo</label>
                <input type="text" name="Title" class="form-control" mandatory deepl_to_translate deepl_text_format="<?= Base_Text_Format::HTML ?>">
            </div>
            <div class="col-12" style="display:none;">
                <div class="form-group">
                    <label>Descrizione</label>
                    <textarea name="Description" class="form-control" rows="5"></textarea>
                </div>
            </div>
        </div>
    </form>
</script>