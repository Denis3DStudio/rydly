$(document).ready(function () {

    renderTable();
});

// Get
function renderTable() {

    kT = new KTable("#dtCustomers", {
        ajax: {
            url: BACKEND.CUSTOMER.ALL,
        },
        sort: {
            1: "ASC"
        },
        columns: [
            {
                title: 'Nome Cognome',
                render: function (data) {
                    return `${data.Name} ${data.Surname}`;
                }
            },
            {
                title: 'Email',
                render: function (data) {
                    return data.Email;
                }
            },
            {
                title: 'Stato',
                render: function (data) {
                    return data.IsActive == 1 ? '<span class="badge bg-success">Attivo</span>' : '<span class="badge bg-danger">Disattivo</span>';
                }
            },
            {
                title: 'Azioni',
                render: function (data) {
                    
                    return `
                            <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/customer/${data.IdCustomer}">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.CUSTOMER}, ${data.IdCustomer})">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `;
                }
            },
        ],
        events: {
            pageChanged() {
            },
            completed(data) {

                hideLoader();
            }
        },
    });
}

// Post
function create() {

    showLoader();

    post_call(
        BACKEND.CUSTOMER.INDEX,
        null,
        function (response) {

            hideLoader();
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/customer/${response}`;
        },
        function () {

            hideLoader();
            notificationError("Qualcosa è andato storto!");
        }
    )

}