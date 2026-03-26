let lastChecked = null;

$(document).ready(function () {

    getLanguagesTabs();
    getTranslation();

    // Check that the deepl container is present
    if ($("#deepl_container").length > 0)
        renderDeepl(true);
})

// Get
function getTranslation() {

    get_call(
        BACKEND.TRANSLATION.INDEX,
        {
            IdTranslation: Url.Params.IdTranslation,
        },
        function (response) {

            // Set main data
            fillContentByNames("", response);
            // Set text data
            fillContentByNames("", response, false);

            // Set languages
            response.Languages.forEach(language => {

                // Fill the form
                if (!isEmpty(language.Translation)) {

                    $(`#tabLang-${language.IdLanguage}-tab`).removeClass("op-5");

                    fillContentByNames(`#tabLang-${language.IdLanguage}`, language);
                }

            });
            
            // Check if the has html is checked
            if (response.TextFormat == 0)
                $("#HasHtml").prop("checked", true);
            else if (response.IsUrl == 1)
                $("#IsUrl").prop("checked", true);

            // Fill the FormatText
            $('input[name="FormatText"][id="' + response.TextFormat + '"]').prop('checked', true);

            getPlaceholders();

            getSections();

            hideLoader();

        },
        function () {
            notificationError("Traduzione non trovata");
        }
    )

}

// Put
function saveTranslation(callback = null, translate = false) {
    if (checkMandatory("#container")) {

        showLoader();

        var obj = {
            IdTranslation: Url.Params.IdTranslation,
            Section: $('[name="Section"]').val(),
            Page: $('[name="Page"]').val(),
            Label: $('[name="Label"]').val(),
            Note: $('[name="Note"]').val(),
            Languages: [],
            TextFormat: $('input[name="FormatText"]:checked').attr('id') || 0
        };

        // Get languages data
        global.Languages.forEach(language => {

            var lang = getContentData(`#tabLang-${language.Language}`);

            // Add id language
            lang.IdLanguage = language.Language;

            obj.Languages.push(lang);

        });

        put_call(
            BACKEND.TRANSLATION.INDEX,
            obj,
            function () {

                if (!isEmpty(callback))
                    callback();

                // Build url for the respose
                var url = '';
                if (translate)
                    url = `/${ENUM.BASE_KEYS.BACKEND_PATH}/translation/${Url.Params.IdTranslation}?st=ok&m=Il testo è stato tradotto correttamente!`;
                else
                    url = `/${ENUM.BASE_KEYS.BACKEND_PATH}/translation/?st=ok&m=Traduzione salvata con successo!`;

                setTimeout(() => {
                    location.href = url;
                }, 500);
            },
            function () {
                hideLoader();

                notificationError("Traduzione non salvata. Potrebbe già esistere.")
            }
        )

    }

    return false;
}

//#region Sections Labels Pages

function getSections() {

    get_call(
        BACKEND.TRANSLATION.SECTION,
        null,
        function (data) {

            $("[name='Section']").autocomplete({
                source: function (request, response) {
                    var results = $.ui.autocomplete.filter(data, request.term);

                    response(results.slice(0, 10));
                },
                classes: {
                    "ui-autocomplete": "list-unstyled"
                }
            });

            getPages();
        }
    )

}
function getPages() {

    get_call(
        BACKEND.TRANSLATION.PAGE,
        null,
        function (data) {

            $("[name='Page']").autocomplete({
                source: function (request, response) {
                    var results = $.ui.autocomplete.filter(data, request.term);

                    response(results.slice(0, 10));
                },
                classes: {
                    "ui-autocomplete": "list-unstyled"
                }
            });

            getLabels();
        }
    )

}
function getLabels() {

    get_call(
        BACKEND.TRANSLATION.LABEL,
        null,
        function (data) {

            $("[name='Label']").autocomplete({
                source: function (request, response) {
                    var results = $.ui.autocomplete.filter(data, request.term);

                    response(results.slice(0, 10));
                },
                classes: {
                    "ui-autocomplete": "list-unstyled"
                }
            });

        }
    )

}

function copyPageLabel(type) {

    // Get the section
    var section = $('[name="Section"]').val();

    // If the section is not WEBSITE or BACKEND add the section to the text
    if (section !== "WEBSITE" && section !== "BACKEND")
        section += ".";
    else
        section = "";

    switch (type) {

        case ENUM.BASE_PROGRAMMING_LANGUAGE.JS:

            copyToClipboard('__t("' + section + $('[name="Page"]').val().toUpperCase() + "." + $('[name="Label"]').val().toUpperCase() + '")');
            break;

        case ENUM.BASE_PROGRAMMING_LANGUAGE.PHP:
            copyToClipboard('<?= __t("' + section + $('[name="Page"]').val().toUpperCase() + "." + $('[name="Label"]').val().toUpperCase() + '") ?>');
            break;

        default:
            copyToClipboard(section + $('[name="Page"]').val().toUpperCase() + "." + $('[name="Label"]').val().toUpperCase());
            break;
    }
}

//#endregion

$(document).on("click", ".nav-item.nav-link.text-dark", function () {
    getPlaceholders();
});
$(document).on("click", "[name=FormatText]", function () {

    // Check if the last was this
    if (lastChecked == this) {
        this.checked = false;
        lastChecked = null;
    } else {
        lastChecked = this;
    }
});

//#region Templates

function getPlaceholders() {

    // Get the id of the active tab
    var tab_id = $("#nav-tab .nav-item.active").attr("aria-controls");
    // Get the abbrevation of the language of the tab active
    var abbrevation = $(`#${tab_id}`).attr("language");
    // Get the value of the translation of the language
    var translation = $(`#Translation-${abbrevation}`).val();

    // Init the placeholder array
    var placeholder = [];
    // Init the index value
    var index = 0;

    while (index >= 0) {

        // Get the in the of the first placeholder chars
        index = translation.indexOf("@@", index);

        // Check that index is not -1
        if (index >= 0) {
            // Sum 2 to index
            index += 2;
            // Get the index of the placeholder finish
            var len = translation.indexOf("@@", index) - index;

            // Check that len is not -1
            if (len >= 0) {
                // Get the sub string beetwen the placeholder chars
                var sub = translation.substr(index, len);
                // check that sub is not false and that is not included in the array
                if (sub != false && !placeholder.includes(sub))
                    placeholder.push(sub);

                // Sum len and 2 to the index value
                index += len + 2;
            }
            else
                index = -1
        }
    }

    renderPlaceholderWord(placeholder);
}
function renderPlaceholderWord(placeholder) {

    // Check if the placeholder array is not empty
    if (!isEmpty(placeholder)) {

        // Add the new p tags after the label
        $("#placeholderWordContainer").html(
            "<code class='mb-1'>" + placeholder.join("</code><br><code class='mb-1'>") + "</code>"
        );

        // Show card
        $("#dtPlaceholderWordCard").show();
    }
    // Hide card
    else
        $("#dtPlaceholderWordCard").hide();
}

//#endregion