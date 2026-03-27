<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 col-md-9 p-0">
            <h1><i class="fa fa-fw fa-list op-2"></i> Categorie</h1>
        </div>
        <div class="col-6 col-md-3 p-0 text-end">
            <button type="button" class="btn btn-success" onclick="create()">
                <i class="fa fa-fw fa-plus"></i> Aggiungi
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="table-responsive">
                <table class="table bg-white table-bordered dt-responsive has--actions" id="dtItems" width="100%"
                    cellspacing="0"></table>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="IdCategoryType" value="<?= $this->__route->__base->IdCategoryType ?? '' ?>" />