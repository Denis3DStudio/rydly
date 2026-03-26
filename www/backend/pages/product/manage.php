<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-archive op-2"></i> Gestione Prodotto</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">

                            <a class="nav-item nav-link text-dark" id="tabVariant-tab" data-bs-toggle="tab"
                                href="#tabVariant" role="tab" aria-controls="tabVariant" aria-selected="true" has_variants="<?= Base_Product_Variant_Type::WITH_VARIANTS ?>" style="display: none">
                                <i class="fa fa-fw fa-sitemap"></i> Varianti
                            </a>
                            <a class="nav-item nav-link text-dark" id="tabPrices-tab" data-bs-toggle="tab"
                                href="#tabPrices" role="tab" aria-controls="tabPrices" aria-selected="true" has_variants="<?= Base_Product_Variant_Type::NO_VARIANTS ?>" style="display: none" mandatory_tab_content>
                                <i class="fa fa-fw fa-coins"></i> Prezzi
                            </a>
                            <a class="nav-item nav-link text-dark" id="tabImages-tab" data-bs-toggle="tab"
                                href="#tabImages" role="tab" aria-controls="tabImages" aria-selected="true">
                                <i class="fa fa-fw fa-images"></i> Immagini
                            </a>

                        </div>
                    </nav>

                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade" id="tabVariant" role="tabpanel"
                                    aria-labelledby="tabVariant-tab">
                                    <div class="row align-items-start">
                                        <div class="col-6">
                                            <p>
                                                Premi il pulsante "Gestione Prezzi" per modificare prezzi e quantità di
                                                più varianti contemporaneamente.
                                            </p>
                                        </div>

                                        <div class="col-6 text-end align-top ps-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAttributes">
                                                <i class="fa fa-fw fa-shapes"></i> Gestione attributi
                                            </button>

                                        </div>
                                    </div>

                                    <div class="row mt-2" id="variant_table_container">

                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tabPrices" role="tabpanel"
                                    aria-labelledby="tabPrices-tab">
                                    <div class="row" id="price_container">
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Prezzo</label>
                                                <input type="number" step="0.01" min="1" class="form-control"
                                                    name="Price" mandatory />
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Prezzo Scontato</label>
                                                <input type="number" step="0.01" min="0.01" class="form-control"
                                                    name="PriceDiscount" />
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Quantità</label>
                                                <input type="number" step="1" class="form-control" name="Quantity"
                                                    mandatory />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tabImages" role="tabpanel"
                                    aria-labelledby="tabImages-tab">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label>Contenuto</label>
                                                        <div class="form-inline">
                                                            <input class="form-control custom-render" type="file"
                                                                name="GenericImages[]" accept="image/*" multiple
                                                                onclick="uploadImages()" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12 text-center">
                                                    <p>oppure</p>
                                                </div>

                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" placeholder="Codice YouTube"
                                                        id="videoUrl" aria-label="Codice YouTube"
                                                        aria-describedby="videoUrl">
                                                    <button class="btn btn-outline-success" type="button" id="videoUrl"
                                                        onclick="insertVideo()">
                                                        <i class="fa fa-fw fa-plus"></i>
                                                    </button>
                                                </div>

                                                <p class="text-helper">
                                                    <i class="fa fa-fw fa-info-circle op-5 ms-0"></i> Dove lo trovo?<br>
                                                    <small><span
                                                            class="text-muted">https://www.youtube.com/watch?v=</span><b>8gKbraVbGyQ</b></small>
                                                    <small><span
                                                            class="text-muted">https://youtu.be/</span><b>8gKbraVbGyQ</b></small>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-8">
                                            <div class="form-group">
                                                <label>
                                                    Tabella contenuti
                                                    <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        title="Trascina per modificare l'ordinamento"></i>
                                                </label>
                                                <div class="table-responsive">
                                                    <table class="table table-hover dt-responsive has--actions"
                                                        name="dtContents" id="dtContents" style="width: 100%;"></table>
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
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Codice Prodotto</label>
                                <input type="text" class="form-control" name="Code" mandatory />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="newsCategory">Categoria</label>
                                <select id="categorySelect" name="IdsCategories" class="selectpicker" data-width="100%"
                                    data-live-search="true" data-size="8" title="Seleziona categoria..." mandatory>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="productsRelated">Prodotti correlati</label>
                                <select id="categorySelect" name="IdsProductsRelated" class="selectpicker" data-width="100%"
                                    data-live-search="true" data-size="8" title="Seleziona prodotti..." multiple>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="newsCategory">Tipologia</label>
                                <select id="HasVariants" name="HasVariants" class="selectpicker" data-width="100%" mandatory title="Seleziona..." trigger_change>
                                    <?php
                                    foreach (Base_Product_Variant_Type::ALL as $key) {
                                    ?>
                                        <option value="<?= $key ?>"><?= Base_Product_Variant_Type::NAMES[$key] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mb-2" style="display: none" mandatory_for_variant_container>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="ShowIfSoldOut" name="ShowIfSoldOut" />
                                <label class="form-check-label" for="ShowIfSoldOut">
                                    Mostra il prodotto anche se esaurito
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="ShowPriceRange" name="ShowPriceRange" trigger_change />
                                <label class="form-check-label" for="ShowPriceRange">
                                    Prezzo da-a <i class="fa-solid fa-circle-info" tooltip="Mostra il range di prezzo dal minore al maggiore"></i>
                                </label>
                            </div>
                        </div>
                        <div class="col-12" style="display: none" mandatory_for_variant_container>
                            <div class="form-group">
                                <label>Prodotto Default</label>
                                <select class="form-control selectpicker" id="IdProductVariantDefault" name="IdProductVariantDefault"
                                    data-title="Seleziona..." data-size="8" data-width="100%" data-live-search="true" mandatory_for_variant mandatory>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mb-0" style="display: none" mandatory_for_variant_container>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="FirstVariantContents" name="FirstVariantContents" />
                                <label class="form-check-label" for="FirstVariantContents">
                                    Mostrare immagini variante prime <i class="fa-solid fa-circle-info" tooltip="Mostra le immagini delle varianti per prime"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-link text-danger" onclick="deleteProduct()">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>
                        <div class="col-6 text-end">
                            <button type="button" class="btn btn-success" onclick="saveProduct()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Template Language Tab -->
<script type="text/html" id="language_tab_template">
    <div class="tab-pane fade" language="{{Language}}" name="tabLang" id="tabLang-{{Language}}" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab">

        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Titolo</label>
                        <input type="text" class="form-control" name="Title" mandatory />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Contenuto</label>
                        <textarea id="Description-{{Language}}" name="Description" class="form-control" rows="18" mandatory></textarea>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col">
                <div id="accordion-attachment" class="accordion-arrows">
                    <div class="card mb-3">
                        <div class="card-header collapsed p-2" id="a-panelAttachment{{Language}}-head" data-bs-toggle="collapse" data-bs-target="#a-panelAttachment{{Language}}" aria-expanded="true" aria-controls="a-panelAttachment{{Language}}">
                            <button class="btn btn-link text-dark" type="button"><i class="fas fa-fw fa-paperclip"></i>Allegati</button>
                        </div>
                        <div id="a-panelAttachment{{Language}}" class="collapse" aria-labelledby="a-panelAttachment{{Language}}-head" data-parent="#accordion-attachment">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label>Allegati</label>
                                            <div class="form-inline">
                                                <input class="form-control custom-render" type="file" name="Attachments{{Language}}" accept="application/pdf" onclick="uploadAttachments('{{Language}}')" multiple />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-8">
                                        <div class="form-group">
                                            <label>
                                                Tabella allegati
                                                <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    title="Trascina per modificare l'ordinamento"></i>
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table table-hover dt-responsive has--actions"
                                                    id="dtAttachments{{Language}}" style="width: 100%;"></table>
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
                        <div class="card-header collapsed p-2" id="a-panelLink{{Language}}-head" data-bs-toggle="collapse" data-bs-target="#a-panelLink{{Language}}" aria-expanded="true" aria-controls="a-panelLink{{Language}}">
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
                                                    data-bs-html="true"
                                                    title="Trascina per modificare l'ordinamento"></i>
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table table-hover dt-responsive has--actions"
                                                    id="dtLinks{{Language}}" style="width: 100%;"></table>
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

<!-- Template Attribute -->
<script type="text/html" id="attribute_container_template">
    <div class="col-12" id="accordion-attribute-container-{{IdAttribute}}">
        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <label id="attribute_select_label_{{IdAttribute}}">{{Text}}</label>
                    <select class="form-control selectpicker" attributes_select id="attributes_select_{{IdAttribute}}" name="attributes" data-title="Seleziona..." data-size="5" data-width="100%" multiple>
                    </select>
                </div>
            </div>

            <div class="col-8">
                <label>&nbsp;</label>
                <div class="form-group">
                    <button type="button" class="btn btn-outline-success" tooltip="Aggiungi" onclick="createAttributeValue({{IdAttribute}})">
                        &nbsp;<i class="fa fa-fw fa-plus"></i>&nbsp;
                    </button>
                    <button type="button" class="btn btn-outline-secondary" tooltip="Aggiorna" onclick="getAttributeValues({{IdAttribute}})">
                        &nbsp;<i class="fa fa-fw fa-sync"></i>&nbsp;
                    </button>
                </div>
            </div>
        </div>
</script>

<!-- Template Variant Table -->
<script type="text/html" id="variant_table_template">
    <div class="col-12">
        <div class="table-responsive">
            <table class="table k-table k-table-hover k-table-responsive-cards has--actions" id="dtVariants" name="dtVariants"></table>
        </div>
    </div>
</script>

<!-- Modal Manage Variant -->
<div class="modal fade" id="modalAttributes" tabindex="-1" role="dialog" aria-labelledby="modalAttributesLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="modalCaptionLabel">Gestione attributi</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="modal-body" id="modalAttributesBody">

                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label>Attributi</label>
                            <select class="form-control selectpicker" id="attributes"
                                name="attributes" data-title="Seleziona..." data-size="5" data-width="100%" multiple>
                            </select>
                        </div>
                    </div>
                    <div class="col-4"></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p>
                            Seleziona gli attributi desiderati dalle select. Infine, clicca sul pulsante
                            "Genera Varianti" per creare le diverse versioni del prodotto.
                        </p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">

                        <div class="form-group">

                            <div class="row" id="attributes_accordions_container">

                            </div>
                        </div>
                    </div>
                </div>

            </form>
            <div class="modal-footer">
                <button class="btn btn-light me-auto" type="button" data-bs-dismiss="modal"
                    aria-label="Close">Annulla</button>
                <button type="button" class="btn btn-outline-success" id="generate_variants_container" onclick="generateVariants()">
                    <i class="fa fa-fw fa-play"></i> Genera Varianti
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalVariant" tabindex="-1" role="dialog" aria-labelledby="modalVariantLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="modalCaptionLabel">Gestione variante</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalVariantBody">

                <div class="row">
                    <div class="col-12">
                        <div class="form-group" id="id_product_variant_container">
                            <label>Varianti</label>
                            <select class="form-control selectpicker" id="IdProductVariant" name="IdProductVariant"
                                data-title="Seleziona..." data-size="8" data-width="100%" data-live-search="true" multiple>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Prezzo</label>
                            <input type="number" step="0.01" min="1" class="form-control" name="Price" mandatory />
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Prezzo Scontato</label>
                            <input type="number" step="0.01" class="form-control" name="PriceDiscount" />
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Quantità</label>
                            <input type="number" step="1" class="form-control" name="Quantity" mandatory />
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light me-auto" type="button" data-bs-dismiss="modal"
                    aria-label="Close">Annulla</button>
                <button class="btn btn-success" type="button" onclick="saveVariant()"><i class="fa fa-fw fa-save"></i>
                    Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Images Variants -->
<div class="modal fade" id="modalVariantImages" tabindex="-1" role="dialog" aria-labelledby="modalVariantImagesLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="modalCaptionLabel">Gestione immagini variante</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalVariantImagesBody">

                <input type="hidden" id="IdProductVariant" />
                <div class="row">
                    <div class="col-4">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Contenuto</label>
                                    <div class="form-inline">
                                        <input class="form-control custom-render" type="file"
                                            name="VariantImages[]" accept="image/*" multiple
                                            onclick="uploadImages(false)" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 text-center">
                                <p>oppure</p>
                            </div>

                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Codice YouTube"
                                    id="videoUrlVariant" aria-label="Codice YouTube"
                                    aria-describedby="videoUrlVariant">
                                <button class="btn btn-outline-success" type="button" id="videoUrlVariant"
                                    onclick="insertVideo(false)">
                                    <i class="fa fa-fw fa-plus"></i>
                                </button>
                            </div>

                            <p class="text-helper">
                                <i class="fa fa-fw fa-info-circle op-5 ms-0"></i> Dove lo trovo?<br>
                                <small><span
                                        class="text-muted">https://www.youtube.com/watch?v=</span><b>8gKbraVbGyQ</b></small>
                                <small><span
                                        class="text-muted">https://youtu.be/</span><b>8gKbraVbGyQ</b></small>
                            </p>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="form-group">
                            <label>
                                Tabella contenuti
                                <i class="far fa-fw fa-question-circle" data-bs-toggle="tooltip"
                                    data-bs-html="true"
                                    title="Trascina per modificare l'ordinamento"></i>
                            </label>
                            <div class="table-responsive">
                                <table class="table table-hover dt-responsive has--actions"
                                    name="dtVariantContents" id="dtVariantContents" style="width: 100%;"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>