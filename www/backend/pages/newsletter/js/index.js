$(document).ready(function () {

    renderTable();
});

// Get
function renderTable() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.CUSTOMER.NEWSLETTER)
        .setIdTable("dtNewsletter")
        .setOptions({
            dom: 'Bfrtip',
            order: [[0, 'asc']],
            buttons: ['excel'],
            columnDefs: [
                {
                    title: 'Email',
                    render: function (data, type, row) {
                        return row.Email;
                    }
                },
                {
                    title: 'Azioni',
                    render: function (data, type, row) {

                        // Initialize
                        var btns = "";

                        if ("IdCustomer" in row)
                            btns += `<a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/customer/${row.IdCustomer}" target="_blank">
                                    <i class="fa fa-fw fa-eye"></i>
                                </a>`;

                        return btns;
                    }
                },
            ],
            initComplete: function (settings, json) {
                hideLoader();
            }
        })
        .initDatatable();

}