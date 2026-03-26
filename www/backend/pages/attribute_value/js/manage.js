$(document).ready(function () {

    getLanguagesTabs();
    getAttributeValue();
})

// Get
function getAttributeValue() {

    showLoader();

    get_call(
        BACKEND.ATTRIBUTE_VALUE.INDEX,
        {
            IdAttribute: Url.Params.IdAttribute,
            IdAttributeValue: Url.Params.IdAttributeValue
        },
        function (response) {

            // Check the canDelete value
            if (response.CanDelete == true)
                $("#deleteButton").removeAttr("disabled");

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
function saveAttributeValue() {

    // Get the data of the languages
    var languages = checkLanguagesTabs();

    // Check the validity
    if (languages.validity == true) {

        put_call(
            BACKEND.ATTRIBUTE_VALUE.INDEX,
            {
                IdAttribute: Url.Params.IdAttribute,
                IdAttributeValue: Url.Params.IdAttributeValue,
                Languages: languages.Languages
            },
            function () {

                // Check if the from page value is "product"
                if ($('[name="from_page"]').val() == "product")
                    window.top.close();
                else
                    window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${Url.Params.IdAttribute}?st=ok&m=Valore dell'attributo salvato correttamente!`;
            },
            function (response, message) {

                message = isEmpty(message) ? "Qualcosa è andato storto!" : message;
                notificationError(message);
            }
        )
    }
}

//#region Colors

// Get colors
$("#tabColors-tab").one("click", getColors);

// Get
function getColors() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.ATTRIBUTE_VALUE.COLORS)
        .setAjaxData("IdAttributeValue", Url.Params.IdAttributeValue)
        .setIdTable("dtColors")
        .setOptions({
            columnDefs: [
                {
                    title: 'Colore',
                    render: function (data, type, row) {
                        return `<input type="color" value="${row.Color}" disabled/>`;
                    }
                },
                {
                    title: 'Codice Esadecimale',
                    render: function (data, type, row) {
                        return row.Color;
                    }
                },
                {
                    title: '',
                    render: function (data, type, row) {

                        return `
                            <button type="button" class="btn btn-link text-danger" onclick="deleteColor(${row.IdAttributeValueColor})">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `;
                    }
                },
            ],
            initComplete: function (settings, json) {
                hideLoader();
            }
        })
        .initDatatable();

}

// Post
function insertColor() {

    if (checkMandatory("#insert_color_container")) {

        // Get the data
        var params = getContentData("#insert_color_container")
        params.IdAttributeValue = Url.Params.IdAttributeValue;

        post_call(
            BACKEND.ATTRIBUTE_VALUE.COLOR,
            params,
            function () {
                notificationSuccess("Colore inserito correttamente!");
                getColors();
            },
            function () {
                notificationError("Qualcosa è andato storto!");
            }
        )
    }
}

// Delete
function deleteColor(idAttributeValueColor) {

    confirmDeleteModal(
        function () {

            delete_call(
                BACKEND.ATTRIBUTE_VALUE.COLOR,
                {
                    IdAttributeValueColor: idAttributeValueColor
                },
                function () {
                    notificationSuccess("Colore eliminato correttamente!");
                    getColors();
                },
                function () {
                    notificationError("Qualcosa è andato storto!");
                }
            );
        }
    )

}

//#endregion