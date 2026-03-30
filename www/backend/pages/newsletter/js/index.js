$(document).ready(function () {

    renderTable();
});

// Get
function renderTable() {

    new KTable("#dtNewsletter", {
        ajax: {
            url: BACKEND.CUSTOMER.NEWSLETTER
        },
        sort: {
            0: "ASC"
        },
        buttons: "",
        export: ["CSV", "XLS"],
        columns: [
            {
                title: 'Email',
                render: function (data) {
                    return data.Email;
                }
            },
            {
                title: 'Azioni',
                render: function (data) {

                    // Initialize
                    var btns = "";

                    // View disable if no customer
                    var isNewsletter = "IdCustomer" in data;

                    // View
                    if (isNewsletter)
                        btns += `<a class="btn btn-outline-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/customer/${data.IdCustomer}" target="_blank">
                                    <i class="fa fa-fw fa-eye"></i>
                                </a>`;
                    else
                        btns += `<button type="button" class="btn btn-outline-secondary" disabled>
                                    <i class="fa fa-fw fa-eye"></i>
                                </button>`;

                    return btns;
                }
            },
        ],
        events: {
            completed(data) {
                hideLoader();
            }
        }
    });

}