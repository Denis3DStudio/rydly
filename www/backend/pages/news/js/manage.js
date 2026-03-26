var checkSelect = false;
var table_links_rendered = [];

$(document).ready(function () {

    hideLoader();

    getLanguagesTabs();
    // Get the news data
    getNews();

    if ($("#deepl_container").length > 0)
        renderDeepl();

});

//#region News

// Get
function getNews() {

    get_call(
        BACKEND.NEWS.INDEX,
        {
            IdNews: Url.Params.IdNews
        },
        function (response) {

            // Set main data
            fillContentByNames("", response);

            // Set languages
            response.Languages.forEach(news_language => {

                // Check that the news_language is valid and the title is not null
                if (news_language.IsValid == 1 && !isEmpty(news_language.Title)) {

                    // Remove the opacity class from the nav flas
                    $(`#tabLang-${news_language.IdLanguage}-tab`).removeClass("op-5");

                    // Insert the data in the inputs
                    fillContentByNames(`#tabLang-${news_language.IdLanguage}`, news_language);
                }
            });

            // Init the summer note
            initSummernote('[name^="Description"');

            // Init the picker
            get_call(
                BACKEND.CATEGORY.ALL,
                null,
                function (categories) {

                    initSelectpicker('#categorySelect');
                    buildPicker(categories, '#categorySelect', 'IdCategory', 'Title', response.Categories, 'Nessuna');
                }
            )

            // get places and set picker
            getPlaces(response.Places);

            hideLoader();
        },
        function () {
            notificationError("Errore durante il caricamento del blog!");
        }
    )

}

// Put
function saveNews() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs();

    // Check the validity of the languages tab and the common container data
    if (tabs_data.validity && checkMandatory("#common_container")) {

        // Create the params
        var params = {
            IdNews: Url.Params.IdNews,
            Languages: tabs_data.Languages
        };

        // Create the final params
        var final_params = { ...params, ...getContentData("#common_container") };
        final_params.Category = [final_params.Category];

        put_call(
            BACKEND.NEWS.INDEX,
            final_params,
            function () {

                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/news?st=ok&m=Blog salvato correttamente!`;
            }
        );
    }
}

$('#categorySelect').change(function () {

    var selectedValues = $(this).val();

    if (!checkSelect) {
        if (selectedValues && selectedValues.includes('-1')) {
            // Deselect all except -1
            var optionsToKeep = ['-1'];
            $('#categorySelect').selectpicker('val', optionsToKeep);
        }
    } else {
        var valueToRemove = '-1';

        var newArray = $.grep(selectedValues, function (element) {
            return element !== valueToRemove;
        });
        checkSelect = false;

        $('#categorySelect').selectpicker('val', newArray);
    }

    var newSelectedValues = $(this).val();
    if (newSelectedValues.length == 1 && newSelectedValues.includes('-1')) {
        checkSelect = true;
    }

});

//#endregion

//#region Links

$(document).on("click", `[renderLinks]`, function () {
    if (!table_links_rendered.includes(`renderLinks${$(this).attr("renderLinks")}`))
        getLinks($(this).attr("renderLinks"));
});

// Get
function getLinks(language) {

    new KTable(`#dtLinks${language}`, {
        ajax: {
            url: BACKEND.NEWS.LINKS,
            data: {
                IdNews: Url.Params.IdNews,
                IdLanguage: language
            }
        },
        sortable: ["IdLink"],
        columns: [
            {
                title: 'Link',
                render: function (data) {

                    return `<a href="${data.Link}" target="_blank">${data.Link}</a>`;
                }
            },
            {
                title: 'Azioni',
                render: function (data) {
                    return `<button type="button" class="btn btn-sm btn-link text-danger" onclick="deleteLink(${data.IdLink}, ${data.IdLanguage})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>`;
                }
            }
        ],
        events: {
            sortableCompleted(data) {

                put_call(
                    BACKEND.NEWS.LINKSORDER,
                    {
                        IdNews: Url.Params.IdNews,
                        IdLanguage: language,
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
        },
    });

}

// Post
function insertLink(idLanguage) {

    // Check mandatory data
    if (checkMandatory("#link_container_" + idLanguage)) {

        post_call(
            BACKEND.NEWS.LINK,
            {
                IdNews: Url.Params.IdNews,
                IdLanguage: idLanguage,
                Link: $(`#link-${idLanguage}`).val()
            },
            function () {

                // Clear the input
                $(`#link-${idLanguage}`).val("");

                // Render the links table
                getLinks(idLanguage);
            }
        );
    }
}

// Delete
function deleteLink(idLink, idLanguage) {

    confirmDeleteModal(
        function () {

            delete_call(
                BACKEND.NEWS.LINK,
                {
                    IdNews: Url.Params.IdNews,
                    IdLink: idLink
                },
                function () {

                    // Render the links table
                    getLinks(idLanguage);
                }
            )
        }
    )
}

//#endregion

//#region Places

// Load all places for dropdown
function getPlaces(selectedPlaces) {

    get_call(
        BACKEND.PLACE.ALL,
        null,
        function (response) {

            // Fill the places select
            response.forEach(place => {

                $('#placesSelect').append(`<option value="${place.IdPlace}" data-subtext="${place.Address ?? '-'}">${place.Name}</option>`);
            });

            $('#placesSelect').val(selectedPlaces);

            // Refresh the selectpicker
            $('#placesSelect').selectpicker('refresh');
        }
    )
}
//#endregion

//#region Files

// $(document).one("click", "#tabImages-tab", getContents);

// Get
// function getContents() {

//     kT = new KTable("#dtContents", {
//         ajax: {
//             url: BACKEND.NEWS.CONTENTS,
//             data: {
//                 IdNews: Url.Params.IdNews
//             }
//         },
//         sortable: ["Id", "Type"],
//         columns: [
//             {
//                 title: "Preview",
//                 image: true,
//                 render: function (data) {
//                     return data.Preview ?? data.FullPath;
//                 },
//             },
//             {
//                 title: "Azioni",
//                 render: function (data) {
//                     return `<button tooltip="Didascalia" type="button" onclick="getContentCaption(${data.Id}, ${data.Type})" class="btn btn-sm btn-outline-secondary">
//                                 <i class="fa fa-fw fa-quote-left"></i>
//                             </button>
//                             <button type="button" onclick="deleteFileManager(${data.Id}, ${ENUM.BASE_FILES.NEWS}, ${data.Type}, ${function () { getContents() }})" class="btn btn-link text-danger">
//                                 <i class="fa fa-fw fa-trash"></i>
//                             </button>`
//                 },
//             },
//         ],
//         events: {
//             sortableCompleted(data) {

//                 put_call(
//                     BACKEND.NEWS.CONTENTSORDER,
//                     {
//                         IdNews: Url.Params.IdNews,
//                         Order: data,
//                     },
//                     function (response, message) {

//                         hideLoader();
//                         notificationSuccess(message);
//                     }
//                 )

//                 // Call the reorder function
//                 // reorderFiles(ENUM.BASE_FILES.NEWS, null, data);
//             },
//             completed(data) {
//                 hideLoader();
//             }
//         },
//     });
// }


//#region Captions

// Get 
function getContentCaption(contentRefId, contentType) {

    getContentCaptionsTranslations(
        BACKEND.NEWS.CAPTION,
        {
            IdNews: Url.Params.IdNews,
            ContentRefId: contentRefId,
            ContentType: contentType
        }
    );
}

// Put
function saveContentCaption() {

    // Get the params
    var params = getContentData("#modalCaptionBody");
    // Add IdNews to params
    params["IdNews"] = Url.Params.IdNews;
    delete params["Caption"];

    saveContentCaptionTranslations(
        BACKEND.NEWS.CAPTION,
        params
    );
}

//#endregion

//#endregion