$(document).ready(function () {

    getCustomer();
})

function getCustomer() {

    get_call(
        BACKEND.CUSTOMER.INDEX,
        {
            IdCustomer: Url.Params.IdCustomer
        },
        function (response) {

            // Check if is new or not
            if (response.IsValid == 0) {

                $('[name="Password"]').attr("mandatory", true);
                $('#RepeatPassword').attr("mandatory", true);
            }

            // Set main data
            fillContentByNames("", response);
            hideLoader();
        }
    );
}

function saveCustomer() {

    // Remove error class from repeat class
    removeErrorClass($('#RepeatPassword'));

    // Check password
    if ($('[name="Password"]').val() == $('#RepeatPassword').val()) {

        if (checkMandatory('#customerForm')) {

            showLoader();

            var obj = getContentData("#customerForm");
            obj.IdCustomer = Url.Params.IdCustomer;
            
            put_call(
                BACKEND.CUSTOMER.INDEX,
                obj,
                function (response) {
                    hideLoader();

                    location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/customer?st=ok&m=Utente salvato con successo!`
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