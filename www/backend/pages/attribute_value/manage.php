<?php
$attribute_text = $this->__route->Languages[0]->Text;
?>

<input type="hidden" name="from_page" value="<?= $this->__route->From ?>">

<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-shapes op-2"></i> Attributo <b>"<?= $attribute_text ?>"</b> <span class="op-2">•</span> Valore</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">

            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <?php
                            if ($this->__route->Type == Base_Attribute_Type::COLOR) {
                            ?>
                                <a class="nav-item nav-link text-dark" id="tabColors-tab" data-bs-toggle="tab"
                                    href="#tabColors" role="tab" aria-controls="tabColors" aria-selected="true" has_variants="<?= Base_Product_Variant_Type::WITH_VARIANTS ?>">
                                    <i class="fa fa-fw fa-eye-dropper"></i> Gestione Colori
                                </a>
                            <?php
                            }
                            ?>
                        </div>
                    </nav>

                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                                <?php
                                if ($this->__route->Type == Base_Attribute_Type::COLOR) {
                                ?>
                                    <div class="tab-pane fade" id="tabColors" role="tabpanel" aria-labelledby="tabColors-tab">
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="row" id="insert_color_container">
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label>Seleziona il colore e aggiungilo</label>
                                                            <input id="Color" name="Color" type="color" class="form-control" mandatory />
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="button" class="btn btn-outline-success w-100" onclick="insertColor()">
                                                            <i class="fa fa-fw fa-droplet"></i> Inserisci Colore
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-8">
                                                <div class="table-responsive">
                                                    <table class="table bg-white table-bordered dt-responsive is--minimal has--actions" id="dtColors" width="100%" cellspacing="0"></table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card mb-3 shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col" id="modifier_data">
                            <div class="form-group">
                                <span><b>Creato da:</b> <i name="Creator"></i></span><br />
                                <span><b>Ultima modifica:</b> <i name="Modifier"></i></span>
                            </div>
                            <div class="form-group">
                                <span><b>Creato il:</b> <i name="InsertDate"></i></span><br />
                                <span><b>Ultima modifica:</b> <i name="UpdateDate"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-link text-danger" id="deleteButton"
                                onclick="deleteAttributeValue()" disabled>
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>

                        <div class="col-6 text-end">
                            <button type="button" class="btn btn-success" onclick="saveAttributeValue()">
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
    <form class="tab-pane fade" id="tabLang-{{Language}}" name="tabLang" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" language="{{Language}}">

        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="form-group col">
                    <label>Valore *</label>
                    <input type="text" name="Text" id="Text-{{Language}}" class="form-control check" mandatory>
                </div>
            </div>
        </div>

    </form>
</script>