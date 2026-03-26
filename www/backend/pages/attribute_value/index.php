<?php $attribute_text = $this->__route->Languages[0]->Text; ?>

<div class="container-fluid">
    <div class="row page-head">
        <div class="col-sm-7 col-md-9 p-0">
            <div class="row">
                <div class="col-12">
                    <h1><i class="fa fa-fw fa-shapes op-2"></i> Attributo <b>"<?= $attribute_text ?>"</b> <span class="op-2">•</span> Valori</h1>
                </div>
                
            </div>
        </div>
        <div class="col-sm-5 col-md-3 p-0 text-end">
            <button type="button" class="btn btn-success" onclick="createAttributeValue()">
                <i class="fa fa-fw fa-plus"></i> Aggiungi
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="/backend/attribute" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-fw fa-angle-left"></i> Torna agli attributi
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="table-responsive">
                <table class="table bg-white table-bordered dt-responsive has--actions" id="dtCategories" width="100%"
                    cellspacing="0"></table>
            </div>
        </div>
    </div>
</div>