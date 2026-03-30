<div class="container-fluid">
    <div class="row page-head">
        <div class="col p-0">
            <h1><i class="fa fa-fw fa-tags op-2"></i> Codice Sconto </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 mb-3">
                <div class="card-body">
                    <div class="row" id="discountData">
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Codice Sconto * </label>
                                <input class="form-control" name="Code" type="text" mandatory />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Tipo *</label>
                                <select name="Type" class="selectpicker form-control" title="Seleziona...">
                                    <?php
                                    foreach (Base_Coupon_Type::ALL as $type) {
                                    ?>
                                        <option value="<?= $type ?>"> <?= Base_Coupon_Type::NAMES[$type] ?> </option>;
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Valore *</label>
                                <input class="form-control" name="Value" type="number" min="0" mandatory disabled />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Importo minimo (facoltativo)</label>
                                <input class="form-control" name="MinOrder" type="number" />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Utilizzi totali *</label>
                                <input class="form-control" name="TotalUses" type="number" min="0" mandatory />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Utilizzi per utente (facoltativo)</label>
                                <input class="form-control" name="TotalUserUses" type="number" min="0" />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Inizio validità codice sconto *</label>
                                <input class="form-control" name="StartDate" type="date" mandatory />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Fine validità codice sconto *</label>
                                <input class="form-control" name="EndDate" type="date" mandatory />
                            </div>
                        </div>
                        <div class="col-12 col-sm-6" style="display: none;" id="products_container">
                            <div class="form-group">
                                <label>Prodotti</label>
                                <select name="IdsProducts" class="selectpicker form-control" title="Tutti" multiple data-actions-box="true" data-live-search="true">
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>Clienti</label>
                                <select name="IdsCustomers" class="selectpicker form-control" title="Tutti" multiple data-actions-box="true" data-live-search="true">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-link text-danger" id="deleteButton" onclick="deleteCoupon()">
                                <i class="fas fa-trash"></i> Elimina
                            </button>
                        </div>
                        <div class="col-6 text-end">
                            <button type="button" class="btn btn-success" onclick="saveCoupon()">
                                <i class="fas fa-fw fa-save"></i> Salva
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>