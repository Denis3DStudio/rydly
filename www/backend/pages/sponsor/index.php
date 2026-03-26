<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 col-md-9 p-0">
            <h1><i class="fa fa-fw fa-ticket op-2"></i> Sponsor</h1>
        </div>
        <div class="col-6 col-md-3 p-0 text-end">
            <button
                type="button"
                class="btn btn-success"
                onclick="createSponsor()">
                <i class="fa fa-fw fa-plus"></i> Aggiungi
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-4 col-lg-3">
            <div class="form-group">
                <label>Categorie</label>
                <select
                    class="form-control selectpicker"
                    data-size="6"
                    id="categorySelect"
                    name="categorySelect"
                    multiple
                    data-live-search="true"
                    data-actions-box="true"></select>
            </div>
        </div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table bg-white table-bordered dt-responsive has--actions" id="dtSponsors" width="100%" cellspacing="0"></table>
            </div>
        </div>
    </div>
</div>