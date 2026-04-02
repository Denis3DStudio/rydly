<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-user-circle op-2"></i> Utente</h1>
        </div>
    </div>

    <form id="accountForm" class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">

                            <a class="nav-item nav-link text-dark active" id="tabMainData-tab" data-bs-toggle="tab"
                                href="#tabMainData" role="tab" aria-controls="tabMainData" aria-selected="true">
                                <i class="fa fa-fw fa-paperclip"></i> Main Data
                            </a>

                        </div>
                    </nav>

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
                                                <label>Username</label>
                                                <input class="form-control" type="text" name="Username" mandatory />
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
                <div class="card-body" id="role_select_container">
                    <div class="row">
                        <div class="col-12" id="role_select_container" style="display: none;">
                            <div class="form-group">
                                <label>Ruolo</label>
                                <select class="selectpicker" name="IdRole" data-title="Seleziona..." data-width="100%" mandatory trigger_change>
                                    <?php
                                    // Show only the roles that the logged user can create
                                    foreach (Base_Account::ROLE_THAT_CAN_CREATE[$this->Logged->IdRole] as $account) {
                                    ?>
                                        <option value="<?= $account ?>"><?= Base_Account::NAMES[$account] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12" id="organization_select_container" style="display: none;">
                            <div class="form-group">
                                <label>Organizzatore</label>
                                <select class="selectpicker" name="IdOrganization" data-title="Seleziona..." data-width="100%" mandatory>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col" id="deleteBtnContainer">
                            <button type="button" id="deleteBtn" class="btn btn-sm btn-link text-danger" onclick="simpleDelete(<?= Base_Simple_Delete::ACCOUNT ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-success" onclick="saveAccount()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>