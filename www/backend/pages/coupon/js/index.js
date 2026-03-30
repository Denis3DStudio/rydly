$(document).ready(function () {
    renderTable();
});

// Get
function renderTable() {
    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.COUPON.ALL)
        .setIdTable("#dtCoupons")
        .setOptions({
            ordering: false,
            columnDefs: [
                {
                    title: "Codice Sconto",
                    render: function (data, type, row) {
                        return row.Code;
                    },
                },
                {
                    title: "Sconto",
                    render: function (data, type, row) {
                        type_text = "";

                        // Check if the type is MONEY
                        if (row.Type == ENUM.BASE_COUPON_TYPE.MONEY) type_text = `${row.Value} €`;
                        else if (row.Type == ENUM.BASE_COUPON_TYPE.PERCENTAGE)
                            type_text = `${row.Value}%`;
                        else if (row.Type == ENUM.BASE_COUPON_TYPE.FREE_SHIPPING)
                            type_text = "Spedizione Gratuita";

                        return type_text;
                    },
                },
                {
                    title: "Utilizzi per utente",
                    render: function (data, type, row) {
                        return row.TotalUserUses;
                    },
                },
                {
                    title: "Utilizzi totali",
                    render: function (data, type, row) {
                        return (
                            row.TotalUses +
                            " (usati " +
                            row.CountUsed +
                            ")"
                        );
                    },
                },
                {
                    title: "Importo minimo",
                    render: function (data, type, row) {
                        return row.MinOrder + " €";
                    },
                },
                {
                    title: "Inizio validità",
                    render: function (data, type, row) {
                        return fDate("d/m/Y", row.StartDate);
                    },
                },
                {
                    title: "Fine validità",
                    orderable: false,
                    render: function (data, type, row) {
                        return fDate("d/m/Y", row.EndDate);
                    },
                },
                {
                    title: "Stato",
                    orderable: false,
                    render: function (data, type, row) {
                        var text = "";
                        var color = "";

                        // Check if the coupon is expired
                        if (fDate("Y-m-d", new Date()) > row.EndDate) {
                            text = "Scaduto";
                            color = "danger";
                        }
                        // Check if the coupon is disabled
                        else {
                            text = row.Enabled == 0 ? "Disabilitato" : "Attivo";
                            color = row.Enabled == 0 ? "warning" : "success";
                        }

                        return `<span class="badge text-bg-${color}">${text}</span>`;
                    },
                },
                {
                    title: "Azioni",
                    render: function (data, type, row) {
                        var icon = row.Enabled == 0 ? "fa-play" : "fa-pause";
                        var tooltip = row.Enabled == 0 ? "Abilita" : "Disabilita";
                        var statusBtnClass =
                            row.Enabled == 0 ? "btn-outline-success" : "btn-outline-danger";

                        // check if CountUsed > 0
                        var btn_edit_and_delete = "";

                        if (row.CountUsed == 0) {
                            btn_edit_and_delete = `<a href="/backend/coupon/${row.IdCoupon}/" class="btn btn-secondary ms-2">
                    <i class="fa fa-fw fa-edit"></i>
                </a>
                <button type="button" onclick="deleteCoupon(${row.IdCoupon})" class="btn btn-link text-danger">
                        <i class="fa fa-fw fa-trash"></i>
                </button>`;
                        }
                        var btn =
                            `<button type="button" onclick="enableDisableCoupon(${row.IdCoupon})" class="btn ${statusBtnClass}" tooltip="${tooltip}">
                    <i class="fa fa-fw ${icon}"></i>
                </button>` + btn_edit_and_delete;

                        return btn;
                    },
                },
            ],
            initComplete: function () {
                initTooltip();
            },
        })
        .initDatatable();
}

// Post
function createCoupon() {
    post_call(BACKEND.COUPON.INDEX, null, function (idCoupon) {
        // redirect to manage page
        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/coupon/${idCoupon}`;
    });
}

// Put
function enableDisableCoupon(idCoupon) {
    put_call(
        BACKEND.COUPON.ENABLEDISABLE,
        {
            IdCoupon: idCoupon,
        },
        function () {
            // reload table
            renderTable();
        },
        null
    );
}
