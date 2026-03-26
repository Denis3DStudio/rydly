<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-users op-2"></i> Cliente</h1>
        </div>
    </div>

    <form id="customerForm" class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <div class="card mb-3 border-0">
                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">

                                <div class="tab-pane fade active show" id="tabMainData" role="tabpanel"
                                    aria-labelledby="tabMainData-tab">

                                    <div class="row">
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Nome</label>
                                                <input class="form-control" type="text" name="Name" mandatory />
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Cognome</label>
                                                <input class="form-control" type="text" name="Surname" mandatory />
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input class="form-control" type="text" name="Email" mandatory />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input class="form-control" type="password" name="Password"
                                                    autocomplete="new-password" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label>Ripeti Password</label>
                                                <input class="form-control" type="password" id="RepeatPassword" />
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
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Stato</label>
                                <select class="selectpicker form-control" name="IsActive" data-title="Seleziona..." data-width="100%" mandatory>
                                    <option value="1">Attivo</option>
                                    <option value="0">Disattivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col" id="deleteBtnContainer">
                            <button type="button" id="deleteBtn" class="btn btn-sm btn-link text-danger" onclick="simpleDelete(<?= Base_Simple_Delete::CUSTOMER ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-success" onclick="saveCustomer()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>