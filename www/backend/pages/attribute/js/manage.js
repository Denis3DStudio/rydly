$(document).ready(function () {

    getLanguagesTabs();
    getAttribute();
})

// Get
function getAttribute() {

    showLoader();

    get_call(
        BACKEND.ATTRIBUTE.INDEX,
        {
            IdAttribute: Url.Params.IdAttribute
        },
        function (response) {

            // Check the canDelete value
            if (response.CanDelete == true)
                $("#deleteButton").removeAttr("disabled");

            // Set the type data
            $("#Type").val(response.Type);

            // Set languages
            response.Languages.forEach(attribute_language => {

                // Check that the attribute_language is valid and the title is not null
                if (attribute_language.IsValid = 1) {

                    // Remove the opacity class from the nav flas
                    $(`#tabLang-${attribute_language.IdLanguage}-tab`).removeClass("op-5");

                    // Insert the data in the inputs
                    fillContentByNames(`#tabLang-${attribute_language.IdLanguage}`, attribute_language);
                }
            });

            // Fill the modifier data
            fillContentByNames("#modifier_data", response, false);

            hideLoader();
        },
        function () {

            notificationError("Qualcosa è andato storto!");

            hideLoader();
        }
    )
}

// Put
function saveAttribute() {

    // Get the data of the languages
    var languages = checkLanguagesTabs();
    // Check the validity
    if (languages.validity == true) {

        put_call(
            BACKEND.ATTRIBUTE.INDEX,
            {
                IdAttribute: Url.Params.IdAttribute,
                Type: $("#Type").val(),
                Languages: languages.Languages
            },
            function () {
                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute?st=ok&m=Attributo salvato correttamente!`;
            },
            function () {
                notificationError("Qualcosa è andato storto!");
            }
        )
    }
}