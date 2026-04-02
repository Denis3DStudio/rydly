<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 col-md-9 p-0">
            <h1><i class="fa fa-fw fa-user-circle op-2"></i> Utenti</h1>
        </div>
        <?php
        if (!Base_Functions::IsNullOrEmpty(Base_Account::ROLE_THAT_CAN_CREATE[$this->Logged->IdRole])) {
        ?>
            <div class="col-6 col-md-3 p-0 text-end">
                <button type="button" class="btn btn-success" onclick="createAccount()">
                    <i class="fa fa-fw fa-plus"></i> Aggiungi
                </button>
            </div>
        <?php
        }
        ?>
    </div>

    <div class="row">
        <div class="col">
            <div class="table-responsive">
                <table class="table k-table k-table-hover k-table-responsive-cards has--actions" id="dtAccounts" width="100%" cellspacing="0"></table>
            </div>
        </div>
    </div>
</div>