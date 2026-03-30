$(document).ready(function () {

    // Check the role
    if (Logged.IdRole == ENUM.BASE_ACCOUNT.USER)
        $("#role_select_container").hide();

    // Get organizations for the select
    getOrganizations(
        // Callback
        function () {
            getAccount();
        }
    );
})

// Get
function getAccount() {

    get_call(
        BACKEND.ACCOUNT.INDEX,
        {
            IdAccount: Url.Params.IdAccount
        },
        function (response) {

            // Check if is new or not
            if (response.IsValid == 0) {

                $('[name="Password"]').attr("mandatory", true);
                $('#RepeatPassword').attr("mandatory", true);
            }

            // Check if current
            if (Logged.IdAccount == Url.Params.IdAccount)
                $('#deleteBtnContainer').remove();

            // Set main data
            fillContentByNames("", response);

            // Reload role
            initSelectpicker("[name='IdRole']");

            hideLoader();
        }
    )

}
// Put
function saveAccount() {

    // Remove error class from repeat class
    removeErrorClass($('#RepeatPassword'));

    // Check password
    if ($('[name="Password"]').val() == $('#RepeatPassword').val()) {

        if (checkMandatory('accountForm')) {

            showLoader();

            var obj = getContentData("#accountForm");
            obj.IdAccount = Url.Params.IdAccount;

            put_call(
                BACKEND.ACCOUNT.INDEX,
                obj,
                function (response) {
                    hideLoader();

                    location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/account?st=ok&m=Utente salvato con successo!`
                },
                function () {
                    hideLoader();

                    notificationError("Utente non salvato.")
                }
            )

        }
    }
    else
        addErrorClass($('#RepeatPassword'))

    return false;
}

//#region organization

$('[name="IdRole"]').change(function () {

    // Check if role has organization
    if (ENUM.BASE_ACCOUNT.ROLES_WITH_ORGANIZATION.includes(parseInt($(this).val())) && Logged.IdRole == ENUM.BASE_ACCOUNT.SUPERADMIN) {

        // Show select
        $('#organization_select_container').show();

        // Eneble select
        $('[name="IdOrganization"]').selectpicker("refresh");

        // Add mandatory
        $('[name="IdOrganization"]').attr("mandatory", true);

    } else {

        // Hide select
        $('#organization_select_container').hide();

        // Disable select
        $('[name="IdOrganization"]').val("").selectpicker("refresh");

        // Remove mandatory
        $('[name="IdOrganization"]').attr("mandatory", false);
    }
});

function getOrganizations(callback = null) {

    get_call(
        BACKEND.ORGANIZATION.ALL,
        null,
        function (response) {

            // Init the picker
            buildPicker(response, "[name='IdOrganization']", "IdOrganization", "Name");

            // Check if callback is not null
            if (callback != null)
                callback();
        }
    );
}

//#endregion