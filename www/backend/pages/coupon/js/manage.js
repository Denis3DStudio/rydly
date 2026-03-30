var global = {
    last_products_selected: [],
    last_users_selected: []
};

$(document).ready(function () {

    setDates();
    getCustomers();
});

//#region dates

function setDates() {

    // Get today date
    var today = fDate("Y-m-d", new Date());

    // Set min to inputs
    $('[name="StartDate"]').attr("min", today);
    $('[name="EndDate"]').attr("min", today);
}
function checkDates() {

    // Init the validity to true
    var validity = true;

    // get values of dates
    var startDate = $('[name="StartDate"]').val();
    var endDate = $('[name="EndDate"]').val();

    // Check start date min
    if (startDate < $('[name="StartDate"]').attr("min")) {
        validity = false;

        addErrorClass('[name="StartDate"]');

        notificationError("La data di inizio deve essere maggiore di quella odierna");
    } else
        removeErrorClass('[name="StartDate"]');

    // Check end date
    if (endDate < $('[name="EndDate"]').attr("min")) {
        validity = false;

        addErrorClass('[name="EndDate"]');

        notificationError("La data di fine deve essere maggiore di quella odierna");
    } else
        removeErrorClass('[name="EndDate"]');

    // Check if the end date is less than the start date
    if (endDate < startDate) {
        validity = false;

        addErrorClass('[name="EndDate"]');

        notificationError("La data di fine deve essere maggiore di quella di inizio");
    } else if (validity == true)
        removeErrorClass('[name="EndDate"]');

    return validity;
}

//#endregion

// Add listener to type select
$(document).on("change", "[name='Type']", changeType);

function changeType() {

    // Remove the disabled attribute from the inputs 
    $("#discountData input").removeAttr("disabled");

    // Get the type selected
    var type = $("[name='Type']").val();

    // Check if the type selected is value
    if (type == ENUM.BASE_COUPON_TYPE.MONEY)
        // Remove the attribe of the input
        $('[name="Value"]').removeAttr("max");

    // Check if the type selected is percentage
    if (type == ENUM.BASE_COUPON_TYPE.PERCENTAGE)
        // Change the max value of the input
        $('[name="Value"]').attr("max", 100);

    // Check if the type selected is free shipping
    if (type == ENUM.BASE_COUPON_TYPE.FREE_SHIPPING) {

        // Add the disabled attribute to the input
        $('[name="Value"]').attr("disabled", true);
        // Set the value of the input to 0
        $('[name="Value"]').val(0);
    }

}

function checkTotalUses() {

    var validity = true;

    // Check that the user uses is minor than the total uses
    if ($('[name="TotalUses"]').val() < $('[name="TotalUserUses"]').val()) {

        validity = false;

        addErrorClass('[name="TotalUserUses"]');

        notificationError("Il numero di utilizzi degli utenti non può essere maggiore di quello totale");
    } else
        removeErrorClass('[name="TotalUserUses"]');

    return validity;
}

// Get
function getCoupon() {

    get_call(
        BACKEND.COUPON.INDEX,
        {
            IdCoupon: Url.Params.IdCoupon
        },
        function (data) {

            // Fill the content of the page
            fillContentByNames("#discountData", data);

            // Set the value of the select
            $(".selectpicker").selectpicker("refresh");

            changeType();

            hideLoader();
        }
    )
}

// Put
function saveCoupon() {

    // Check the mandatory fields and the dates and the total uses
    if (checkMandatory("#discountData") && checkDates() && checkTotalUses()) {

        // Get the data of the form
        var data = getContentData("#discountData");

        // Add the id of the coupon
        data.IdCoupon = Url.Params.IdCoupon;

        // Call the api to save the coupon
        put_call(
            BACKEND.COUPON.INDEX,
            data,
            function (data) {

                // Build the params for the redirect
                if ("f" in Url.Query) {
                    window.close();
                    return;
                }

                // Redirect to main page
                location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/coupon?st=ok&m=Buono sconto salvato con successo!`;
            }
        );
    }

}

//#region Customers

// Get 
function getCustomers() {

    get_call(
        BACKEND.CUSTOMER.ALL,
        null,
        function (response) {

            // Cycle all response and concat the name to the surname
            response.forEach(function (element) {
                element.Name = `${element.Name} ${element.Surname} (${element.Email})`;
            });

            // Build the picker
            buildPicker(response, "[name='IdsCustomers']", "IdCustomer", "Name");

            getCoupon();
        }
    );
}

//#endregion