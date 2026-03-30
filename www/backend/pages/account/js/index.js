$(document).ready(function () {

    if (Logged.IdRole == ENUM.BASE_ACCOUNT.USER)
        location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/account/${Logged.IdAccount}`;
    else
        renderTable();
});

// Get
function renderTable() {

    kT = new KTable("#dtAccounts", {
        ajax: {
            url: BACKEND.ACCOUNT.ALL,
        },
        sort: {
            1: "ASC"
        },
        columns: [
            {
                title: 'Organizzatore',
                filterable: true,
                render: function (data) {
                    return data.Organization ? `<a class="underline" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/organization/${data.Organization.IdOrganization}" target="_blank">${data.Organization.Name}</a>` : '-';
                }
            },
            {
                title: 'Nome Cognome',
                render: function (data) {
                    return `${data.FullName}`;
                }
            },
            {
                title: 'Username',
                render: function (data) {
                    return data.Username;
                }
            },
            {
                title: 'Ruolo',
                render: function (data) {
                    return `<span class="badge text-bg-${ENUM.BASE_ACCOUNT.COLORS[data.IdRole]}">${ENUM.BASE_ACCOUNT.NAMES[data.IdRole]}</span>`;
                }
            },
            {
                title: 'Azioni',
                render: function (data) {

                    var impersonate_disabled = '';
                    var dltBtnAction = `simpleDelete(${ENUM.BASE_SIMPLE_DELETE.ACCOUNT}, ${data.IdAccount})`;
                    var dltBtnDisabled = '';

                    if (data.IdAccount == Logged.IdAccount) {
                        impersonate_disabled = 'disabled';
                        dltBtnAction = ``;
                        dltBtnDisabled = 'disabled';
                    }

                    var impersonate = data.IdRole != ENUM.BASE_ACCOUNT.SUPERADMIN ? "" :
                        `<button type="button" class="btn btn-outline-secondary" onclick="impersonateAccount(${data.IdAccount})" data-bs-toggle="tooltip" data-placement="top" title="Impersona" ${impersonate_disabled}>
                            <i class="fa fa-fw fa-person-walking-arrow-right"></i>
                        </button>`;

                    return `
                            ${impersonate}
                            <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/account/${data.IdAccount}">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-link text-danger" onclick="${dltBtnAction}" ${dltBtnDisabled}>
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
function createAccount() {

    showLoader();

    post_call(
        BACKEND.ACCOUNT.INDEX,
        null,
        function (response) {

            hideLoader();

            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/account/${response}`;
        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");

        }
    )

}
function impersonateAccount(idAccount) {

    showLoader();

    post_call(
        BACKEND.ACCOUNT.IMPERSONATE,
        {
            IdAccount: idAccount
        },
        function (response) {

            // Add to local storage
            localStorage.setItem(ENUM.BASE_KEYS.JWT, response.JWT);

            // Remove JWT
            delete response.JWT;

            // Add to local storage account info
            localStorage.setItem(ENUM.BASE_KEYS.ACCOUNT, JSON.stringify(response));

            // Redirect to dashboard
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/dashboard/`;

        },
        function () {
            hideLoader();

            notificationError("Qualcosa è andato storto...");
        }
    )
}