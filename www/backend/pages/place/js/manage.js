var checkSelect = false;
var categories_list = [];
var category_main_selected = null;
var selects = {
    Blogs: [],
    Categories: [],
    Answers: []
}
var showFirstLanguageTab = false;

$(document).ready(function () {

    getLanguagesTabs();

    //#region Show the first tab only and move the languages tabs after the first one

    // Move the tabs of languages after the General tab
    $("#tabLang-1-tab").before($("#tabGeneral-tab"));

    //#endregion

    // Get the place data
    getPlace();

    // Init google autocomplete - TODO
    initAutocomplete();

    // Check if there is at least one summernote
    if ($(".js-editor.summernote").length > 0)
        // Init summernote
        initSummernote(".js-editor.summernote");

    // Check if there is the deepl container to render the button
    if ($("#deepl_container").length > 0)
        renderDeepl();

});

//#region Place

// Get
function getPlace() {

    get_call(
        BACKEND.PLACE.INDEX,
        {
            IdPlace: Url.Params.IdPlace
        },
        function (response) {

            // Set main data
            fillContentByNames("#common_container", response);

            // Set the translations
            response.Languages.forEach(translation => {
                // Fill the form
                if (!isEmpty(translation.Description)) {
                    // Remove the opacity
                    $(`#tabLang-${translation.IdLanguage}-tab`).removeClass("op-5");
                    // Insert the content
                    fillContentByNames(`#tabLang-${translation.IdLanguage}`, translation);
                }
            });

            category_main_selected = response.MainCategory;

            selects.Blogs = response.News;
            selects.Categories = response.Categories;
            selects.Answers = response.IdSurveyQuestionAnswers;

            getBlogs();
        },
        function () {
            notificationError("Errore durante il caricamento del luogo!");
        }
    )

}

// Put
function savePlace() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs();

    // Check the validity of the languages tab and the common container data
    if (tabs_data.validity && checkMandatory("#common_container")) {

        // Create the params
        var params = {
            IdPlace: Url.Params.IdPlace,
            Languages: tabs_data.Languages
        };

        // Create the final params
        var final_params = { ...params, ...getContentData("#common_container", true) };

        put_call(
            BACKEND.PLACE.INDEX,
            final_params,
            function () {

                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/place?st=ok&m=Luogo salvato correttamente!`;
            }
        );
    }
    else {
        notificationError("Compila tutti i campi obbligatori!");
    }
}

//#endregion

//#region Blogs

function getBlogs() {

    // Set blog
    get_call(
        BACKEND.PLACE.ALLNEWS,
        null,
        function (news) {

            initSelectpicker('#newsSelect');
            buildPicker(news, '#newsSelect', 'IdNews', 'Title', selects.Blogs, '');
            getCategories()
        }
    );
}
//#endregion

//#region Categories

function getCategories() {

    // Set category
    get_call(
        BACKEND.CATEGORY_PLACE.ALL,
        null,
        function (categories) {

            // Save in the global variable
            categories_list = categories;

            initSelectpicker('#categorySelect');
            buildPicker(categories, '#categorySelect', 'IdCategory', 'Title', selects.Categories, '');

            checkMainCategory();
            getTravelerPath();
        }
    );
}

//#endregion

//#region Traveler path

function getTravelerPath() {

    get_call(
        BACKEND.SURVEY.ALLFORSELECT,
        null,
        function (response) {

            if (response.length > 0) {

                initSelectpicker('#travelerPathSelect');
                response.forEach(question => {

                    // Create the option group for the question
                    var option_group = `<optgroup label="${question.Question}">`;

                    // Add the options for the question
                    question.Answers.forEach(answer => {
                        option_group += `<option value="${answer.IdSurveyQuestionAnswer}" ${selects.Answers.includes(answer.IdSurveyQuestionAnswer) ? 'selected' : ''}>${answer.Answer}</option>`;
                    });
                    option_group += "</optgroup>";

                    // Append the option group to the select
                    $('#travelerPathSelect').append(option_group);
                });

                initSelectpicker('#travelerPathSelect');
            }

            hideLoader();
        }
    );
}

//#endregion

function checkMainCategory() {

    // Get the values selected in the category select
    var selected_values = $("#categorySelect").val();
    // Get also the main category select
    var main_category_select = $('#mainCategory').val() || category_main_selected; // if the select is empty get the value from the global variable (used on page load)

    if (!isEmpty(category_main_selected))
        category_main_selected = null;

    if (selected_values.length > 0) {

        var main_categories = categories_list.filter(function (category) {
            return selected_values.includes(category.IdCategory.toString());
        });

        // Init the main category select 
        buildPicker(main_categories, '#mainCategory', 'IdCategory', 'Title', main_category_select);
        // Set as mandatory
        $('#mainCategory').attr('mandatory', 'true');

        // Show the main category container
        $('#main_category_container').show();
    }
    // Empty selection
    else {

        // Empty the main category select
        buildPicker([], '#mainCategory', 'IdCategory', 'Title');
        // Remove mandatory
        $('#mainCategory').removeAttr('mandatory');

        // Hide the main category container
        $('#main_category_container').hide();
    }
}

$(document).on("change", "#categorySelect", checkMainCategory);

// #region Google Autocomplete

// Google autocomplete
let autocomplete;

function initAutocomplete() {
    const input = document.getElementById("Address");

    autocomplete = new google.maps.places.Autocomplete(input, {
        // Se vuoi includere attività e indirizzi:
        // types: [""], /
        // In alternativa: togli proprio "types" per suggerimenti più “misti”
        componentRestrictions: { country: "it" },
        fields: ["geometry", "name", "formatted_address", "address_components"] // importante: riduce costi e dati
    });

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();

        if (!place.geometry) {
            notificationError("Luogo/indirizzo non valido");
            return;
        }

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();
        $("#Latitude").val(lat);
        $("#Longitude").val(lng);

        // Se vuoi mostrare nel campo l’indirizzo completo (utile per attività)
        if (place.formatted_address) {
            $("#Address").val(place.formatted_address);
        } else if (place.name) {
            $("#Address").val(place.name);
        }

        // Estrai la città in modo robusto (non con split!)
        const city = getAddressComponent(place.address_components, "locality")
            || getAddressComponent(place.address_components, "administrative_area_level_3")
            || getAddressComponent(place.address_components, "administrative_area_level_2");

        if (city) $("#City").val(city);
    });
}
function getAddressComponent(components, type) {
    if (!components) return "";
    const comp = components.find(c => c.types && c.types.includes(type));
    return comp ? comp.long_name : "";
}

//#endregion