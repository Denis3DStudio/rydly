$(document).ready(function () {

    getLanguagesTabs();
    getCategory();
})


// Get
function getCategory() {

    get_call(
        BACKEND.CATEGORY_PRODUCT.INDEX,
        {
            IdCategory: Url.Params.IdCategory,
            Verbose: 1
        },
        function (response) {

            // Check if can be deleted
            if (!response.Delete)
                $('#deleteButton').prop('disabled', false);

            // Set languages
            response.Languages.forEach(language => {

                // Fill the form
                if (!isEmpty(language.Name)) {

                    $(`#tabLang-${language.IdLanguage}-tab`).removeClass("op-5");

                    fillContentByNames(`#tabLang-${language.IdLanguage}`, language);
                }

            });

            fillContentByNames(`#modifiers`, response.Accounts, false);
            fillContentByNames(`#dates`, response.Dates, false);

            hideLoader();

        },
        function () {
            notificationError("Traduzione non trovata");
        }
    )

}

// Put
function saveCategoryAndForceUpdate() {

    saveCategory(forceUpdate());
}

function saveCategory(callback = null) {

    checkLanguagesTabs('tabLang');

    if (checkMandatory('#categoryForm')) {

        // showLoader();

        var obj = {
            IdCategory: Url.Params.IdCategory,
            Languages: []
        };

        // Get languages data
        for (let index = 0; index < $('form[id^="tabLang-"]').length; index++) {
            const tab = $('form[id^="tabLang-"]')[index];

            // Get id
            var id = $(tab).attr("id");

            // Get form data
            var lang = getContentData(`#${id}`);

            // Add id language
            lang.IdLanguage = id.replace("tabLang-", "");

            obj.Languages.push(lang);

        }
        obj.Duplicates = ['Name'];

        put_call(
            BACKEND.CATEGORY_PRODUCT.INDEX,
            obj,
            function () {

                if (!isEmpty(callback))
                    callback();

                setTimeout(() => {
                    location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product_category?st=ok&m=Categoria salvata con successo!`
                }, 500);
            },
            function (response) {

                hideLoader();

                showErrorsInputs(response, 'tabLang');

                notificationError("Categoria non salvata. Potrebbe già esistere, controlla gli errori.")
            }
        )

    }

    return false;
}