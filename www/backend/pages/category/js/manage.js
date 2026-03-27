$(document).ready(function () {

    getLanguagesTabs();
    get();
    
});

// Get
function get() {

    get_call(
        BACKEND.CATEGORY.INDEX,
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
    var translations = checkLanguagesTabs(false);

    if (translations.validity == true) {

        // Get the common data
        var params = {};
        params["IdCategory"] = Url.Params.IdCategory;
        params["Languages"] = translations.Languages;

        put_call(
            BACKEND.CATEGORY.INDEX,
            params,
            function (response, message) {

                location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/${ENUM.BASE_CATEGORY_TYPE.PAGES[$("#IdCategoryType").val()]}?st=ok&m=${message}`
            },
            function (response, message) {

                notificationError(message);
            }
        )
    }
}

//#region Images

$(document).one("click", "#tabImages-tab", getImages);

// Get
function getImages() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.CATEGORY.IMAGES)
        .setAjaxData("IdCategory", Url.Params.IdCategory)
        .setIdTable(`dtImages`)
        .setOptions({
            sorting: false,
            info: false,
            searching: false,
            paging: false,
            rowReorder: {
                update: false,
            },
            columnDefs: [
                {
                    title: "Preview",
                    render: function (data, type, row) {
                        return `<div class='table-img-preview' style='background-image:url(${row.FullPath})' />`;
                    },
                },
                {
                    title: "Azioni",
                    render: function (data, type, row) {
                        return `<button type="button" onclick="deleteImage(${row.IdImage})" class="btn btn-link text-danger">
                                    <i class="fa fa-fw fa-trash"></i>
                                </button>`
                    },
                },
            ],
            fnRowCallback: function (row, data, iDisplayIndex, iDisplayIndexFull) {
                $(row).attr("id", data.IdImage);
            },
        })
        .setReorderCallback(function (data) {

            // Check that there is at least a row
            if (data.length > 0) {

                put_call(
                    BACKEND.CATEGORY.IMAGESORDER,
                    {
                        IdCategory: Url.Params.IdCategory,
                        Order: data,
                    },
                    function (response, message) {
                        notificationSuccess(message);
                    },
                    function (response, message) {
                        notificationError(message);
                    }
                );

            }
        })
        .initDatatable();

}

// Post
function uploadImages() {

    // Check if at least one image is selected
    if ("Images" in input_file_custom_render_queue && input_file_custom_render_queue["Images"].length > 0) {

        showLoader();

        chunk_call(
            BACKEND.CATEGORY.IMAGES,
            {
                IdCategory: Url.Params.IdCategory
            },
            `[name="Images"]`,
            function (response, message) {

                // Get the images
                getImages();

                hideLoader();
                notificationSuccess(message);
            },
            function (response, message) {

                hideLoader();
                notificationError(message);
            }
        );

    }
    else
        notificationError("Inserire almeno un'immagine");

}
// Delete
function deleteImage(idImage) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.CATEGORY.IMAGE,
                {
                    IdCategory: Url.Params.IdCategory,
                    IdImage: idImage,
                },
                function (response, message) {

                    // Get the images
                    getImages();

                    hideLoader();
                    notificationSuccess(message);
                },
                function (response, message) {

                    hideLoader();
                    notificationError(message);
                }
            )
        }
    )
}

//#endregion