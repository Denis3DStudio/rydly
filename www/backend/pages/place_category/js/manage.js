$(document).ready(function () {

    getLanguagesTabs();
    get();

    // Check if there is deepl container to render the button
    if ($("#deepl_container").length > 0)
        renderDeepl();
});

// Get
function get() {

    get_call(
        BACKEND.CATEGORY_PLACE.INDEX,
        {
            IdCategory: Url.Params.IdCategory
        },
        function (response) {

            // Set the translations
            response.Translations.forEach(translation => {

                // Fill the form
                if (!isEmpty(translation.Title)) {

                    // Remove the opacity
                    $(`#tabLang-${translation.IdLanguage}-tab`).removeClass("op-5");
                    // Insert the content
                    fillContentByNames(`#tabLang-${translation.IdLanguage}`, translation);
                }
            });

            // Fill the general data
            fillContentByNames("#common_container", response.General);

            if (response.IsDeletable == 0)
                $("#deleteBtnContainer").remove();

            hideLoader();
        }
    );

}

// Put
function save() {

    // Check the languages tabs
    var translations = checkLanguagesTabs();

    if (translations.validity == true) {

        // Get the data
        var params = {
            IdCategory: Url.Params.IdCategory,
            Languages: translations.Languages
        };
        // Create the final params
        var final_params = { ...params, ...getContentData("#common_container", true) };

        put_call(
            BACKEND.CATEGORY_PLACE.INDEX,
            final_params,
            function (response, message) {

                location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/place_category?st=ok&m=${message}`
            },
            function (response, message) {

                notificationError(message);
            }
        )
    }
}

//#region images

$(document).one("click", "#tabImages-tab", function () {
    getImages();
});

// Get
function getImages() {

    get_call(
        BACKEND.CATEGORY_PLACE.IMAGE,
        {
            IdCategory: Url.Params.IdCategory
        },
        function (response) {

            // Hide all images
            $("[img_preview]").hide();
            $("[delete_button]").hide();
            $("[delete_button] button").attr("onclick", "");

            // Get the container id
            var image_container = "[img_preview='img_preview']";

            // Set the image
            $(`${image_container} img`).attr("src", response.FullPath);
            // Show the container
            $(image_container).show();

            hideLoader();
        },
        function () {
            hideLoader();
        }
    );
}

// Post
function uploadImages() {

    input = '[name="Image"]';

    // Check if the input has a file
    if ($(input).prop('files').length > 0) {

        showLoader();

        file_call(
            BACKEND.CATEGORY_PLACE.IMAGE,
            {
                IdCategory: Url.Params.IdCategory
            },
            input,
            function () {

                // Get the images
                getImages();
                hideLoader();
                notificationSuccess("Immagine caricata con successo!");
            },
            function () {

                hideLoader();
                notificationError("Qualcosa è andato storto, riprova!");
            }
        );
    }
    else
        addErrorClass(input);
}

//#endregion