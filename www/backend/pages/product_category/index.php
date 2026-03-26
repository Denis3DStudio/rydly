<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 p-0">
            <h1><i class="fa fa-fw fa-archive op-2"></i> Prodotti &bull; Categorie</h1>
        </div>
        <div class="col-6 p-0 text-end">
            <button type="button" class="btn btn-success" onclick="createCategory()">
                <i class="fa fa-fw fa-plus"></i> Aggiungi
            </button>
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

    <input type="hidden" name="openTree" id="openTree" value="<?=isset($_GET['openTree']) ? $_GET['openTree'] : ''?>">

    <div class="row">
        <div class="col-12">
            <div class="list-sortable is-tree no-hover">
                <ol class="sortable" id="categoriesTree"></ol>
            </div>
        </div>
    </div>
</div>

<script id="li_category_template" type="text/template">

    <li data-id="{{IdCategory}}" parent-id="{{IdCategoryParent}}" has-childs="{{HasChilds}}">
        <div class="item-block">
            <div class="row">
                <div class="col-12 col-sm-8">
                    <div class="item__openclose">
                        <i id="collapseIcon{{IdCategory}}" class="js-openCloseIcon fa fa-fw"></i>
                    </div>
                    <div class="item__title admin__title__hover">
                       {{CategoryName}}
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="item__actions">
                        
                        <button onclick="createCategory({{IdCategory}})" type="button" class="btn btn-outline-success" style="display: {{CreateButton}}" >
                            <i class="fa fa-fw fa-plus"></i>
                        </button>

                        <a href="/backend/product_category/{{IdCategory}}/" class="btn btn-secondary">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>

                        <button onclick="{{DeleteAction}}" type="button" class="btn btn-link text-danger mr-2" {{DeleteDisabled}}>
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </li>

</script>