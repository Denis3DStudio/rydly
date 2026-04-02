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

    // Get languages tabs
    getLanguagesTabs();

    //#region Show the first tab only and move the languages tabs after the first one

    // Move the tabs of languages after the General tab
    $("#tabLang-1-tab").before($("#tabGeneral-tab"));

    //#endregion

    // Get categories
    getCategories(function () {
        // Get the event data
        getEvent();
    });

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

//#region Event

// Get
function getEvent() {

    get_call(
        BACKEND.EVENT.INDEX,
        {
            IdEvent: Url.Params.IdEvent
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

            selects.Categories = response.Categories;

            console.log(response);


            // If there is a main category selected previously, set it in the select
            checkMainCategory();

            // Hide Loader
            hideLoader();

        },
        function () {
            notificationError("Errore durante il caricamento dell'evento!");
        }
    )

}

// Put
function saveEvent() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs();

    // Check the validity of the languages tab and the common container data
    if (tabs_data.validity && checkMandatory("#common_container")) {

        // Create the params
        var params = {
            IdEvent: Url.Params.IdEvent,
            Languages: tabs_data.Languages
        };

        // Create the final params
        var final_params = { ...params, ...getContentData("#common_container", true) };

        put_call(
            BACKEND.EVENT.INDEX,
            final_params,
            function () {

                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/event?st=ok&m=Evento salvato correttamente!`;
            }
        );
    }
    else {
        notificationError("Compila tutti i campi obbligatori!");
    }
}

//#endregion

//#region Categories

function getCategories(callback = null) {

    // Set category
    get_call(
        BACKEND.CATEGORY.ALL,
        {
            IdType: ENUM.BASE_CATEGORY_TYPE.EVENT
        },
        function (categories) {

            // Save in the global variable
            categories_list = categories;

            // Build the select
            buildPicker(categories, '#IdsCategories', 'IdCategory', 'Title');

            // Execute the callback if provided
            if (callback) callback();

            hideLoader();
        }
    );
}

//#endregion

function checkMainCategory() {

    // Get the values selected in the category select
    var selected_values = $("#IdsCategories").val();
    // Get also the main category select
    var main_category_select = $('#mainCategory').val() || category_main_selected; // if the select is empty get the value from the global variable (used on page load)

    // Reset the global variable
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

$(document).on("change", "#IdsCategories", checkMainCategory);

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
        const event = autocomplete.getPlace();

        if (!event.geometry) {
            notificationError("Luogo/indirizzo non valido");
            return;
        }

        const lat = event.geometry.location.lat();
        const lng = event.geometry.location.lng();
        $("#Latitude").val(lat);
        $("#Longitude").val(lng);

        // Se vuoi mostrare nel campo l’indirizzo completo (utile per attività)
        if (event.formatted_address) {
            $("#Address").val(event.formatted_address);
        } else if (event.name) {
            $("#Address").val(event.name);
        }

        // Estrai la città in modo robusto (non con split!)
        const city = getAddressComponent(event.address_components, "locality")
            || getAddressComponent(event.address_components, "administrative_area_level_3")
            || getAddressComponent(event.address_components, "administrative_area_level_2");

        if (city) $("#City").val(city);
    });
}
function getAddressComponent(components, type) {
    if (!components) return "";
    const comp = components.find(c => c.types && c.types.includes(type));
    return comp ? comp.long_name : "";
}

//#endregion