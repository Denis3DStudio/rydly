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
    // Hide
    $("[to_hide]").hide();

    getLanguagesTabs();

    // Show General tab first
    $("#tabLang-1-tab").before($("#tabGeneral-tab"));

    // Get the organizer data
    getCategories(function () {
        getOrganizer();
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

//#region Organizer

// Get
function getOrganizer() {

    get_call(
        BACKEND.ORGANIZER.INDEX,
        {
            IdOrganizer: Url.Params.IdOrganizer
        },
        function (response) {
            console.log(response);

            // Set main data
            fillContentByNames("#common_container", response);
            fillContentByNames("#tabGeneral", response);

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

            // Ricostruisci il picker della main category ORA che hai il valore corretto
            checkMainCategory();

            hideLoader();
        },
        function () {
            notificationError("Errore durante il caricamento del luogo!");
        }
    )

}

// Put
function saveOrganizer() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs();

    // Check the validity of the languages tab and the common container data
    if (tabs_data.validity && checkMandatory("#tabGeneral") && checkMandatory("#common_container")) {

        // Build
        var params = {
            IdOrganizer: Url.Params.IdOrganizer,
            Languages: tabs_data.Languages,
            ...getContentData("#common_container", true),
            ...getContentData("#tabGeneral", true)
        };

        put_call(
            BACKEND.ORGANIZER.INDEX,
            params,
            function () {

                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/organizer?st=ok&m=Luogo salvato correttamente!`;
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
            IdType: ENUM.BASE_CATEGORY_TYPE.ORGANIZER
        },
        function (categories) {
            // console.log(categories);

            // Save in the global variable
            categories_list = categories;

            // Init the picker
            buildPicker(categories, '#Categories', 'IdCategory', 'Title', selects.Categories, '');

            // Check if callback is not null
            if (callback != null)
                callback();
            else
                hideLoader();
        }
    );
}
function checkMainCategory() {

    // Get the values selected in the category select
    var selected_values = $("#Categories").val();
    // Get also the main category select
    var main_category_select = $('#MainCategory').val() || category_main_selected; // if the select is empty get the value from the global variable (used on page load)

    // Reset the global variable
    if (!isEmpty(category_main_selected))
        category_main_selected = null;

    if (selected_values.length > 0) {

        // Filter the categories list to get only the selected categories
        var main_categories = categories_list.filter(function (category) {
            return selected_values.includes(category.IdCategory.toString());
        });

        // Init the main category select 
        buildPicker(main_categories, '#MainCategory', 'IdCategory', 'Title', main_category_select);

        // Set as mandatory
        $('#MainCategory').attr('mandatory', 'true');

        // Show the main category container
        $('#main_category_container').show();
    }
    // Empty selection
    else {

        // Empty the main category select
        buildPicker([], '#MainCategory', 'IdCategory', 'Title');
        // Remove mandatory
        $('#MainCategory').removeAttr('mandatory');

        // Hide the main category container
        $('#main_category_container').hide();
    }
}

$(document).on("change", "#Categories", checkMainCategory);

//#endregion

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
        const address = autocomplete.getPlace();

        if (!address.geometry) {
            notificationError("Luogo/indirizzo non valido");
            return;
        }

        const lat = address.geometry.location.lat();
        const lng = address.geometry.location.lng();
        $("#Latitude").val(lat);
        $("#Longitude").val(lng);

        // Se vuoi mostrare nel campo l’indirizzo completo (utile per attività)
        if (address.formatted_address) {
            $("#Address").val(address.formatted_address);
        } else if (address.name) {
            $("#Address").val(address.name);
        }

        // Estrai la città in modo robusto (non con split!)
        const city = getAddressComponent(address.address_components, "locality")
            || getAddressComponent(address.address_components, "administrative_area_level_3")
            || getAddressComponent(address.address_components, "administrative_area_level_2");

        if (city) $("#City").val(city);
    });
}
function getAddressComponent(components, type) {
    if (!components) return "";
    const comp = components.find(c => c.types && c.types.includes(type));
    return comp ? comp.long_name : "";
}

//#endregion