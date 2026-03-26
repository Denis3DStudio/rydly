<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-list op-2"></i> Gestione Categoria</h1>
        </div>
    </div>

    <form class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        </div>
                    </nav>
                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body" id="common_container">
                    <div class="form-group">
                        <label class="w-100" for="color">Colore Categoria</label>
                        <input type="color" name="Color" value="#ffffff" mandatory/>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-6" id="deleteBtnContainer">
                            <button type="button" id="deleteBtn" class="btn btn-sm btn-link text-danger" onclick="deleteProject()">
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
        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="form-group col-12">
                    <label>Titolo</label>
                    <input type="text" name="Title" class="form-control" mandatory>
                </div>
                <!-- <div class="col-12">
                    <div class="form-group">
                        <label>Descrizione</label>
                        <textarea name="Description" class="form-control" rows="5" mandatory></textarea>
                    </div>
                </div> -->
            </div>
        </div>
    </form>
</script>