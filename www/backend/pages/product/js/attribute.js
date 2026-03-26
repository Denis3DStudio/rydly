var attribute_global = {
    Attributes: {},
    LastIdsSelected: [],
    ValuesIdsSelected: {},
    CreateOrChangeValue: false
}

// Add listener on modal open
$(document).on("shown.bs.modal", "#modalAttributes", function () {
    getAttributes();
});

// Add listener on the attributes_select to save the values selected to the attribute_global.ValuesIdsSelected obj
$(document).on("change", "[attributes_select]", function () {

    // Get the id of the attribute
    var idAttribute = $(this).attr("id").replace("attributes_select_", "");

    // Get the value selected
    var idAttributeValue = $(this).val();

    // Check if the idAttributeValue is null
    if (idAttributeValue == null)
        idAttributeValue = [];

    // Save the values selected in the attribute_global.ValuesIdsSelected obj
    saveValuesIdsSelected(idAttribute, idAttributeValue);
});

// Get
function getAttributes() {

    // Call the api
    get_call(
        BACKEND.ATTRIBUTE.ALL,
        null,
        function (attributes) {

            // Cycle all attributes
            attributes.forEach(attribute => {

                // Check if the attribute exists in the attribute_global.Attributes obj
                if (!(attribute.IdAttribute in attribute_global.Attributes)) {

                    // Add the values in the attributes obj
                    attribute_global.Attributes[attribute.IdAttribute] = {
                        IdAttribute: attribute.IdAttribute,
                        Text: attribute.Text
                    };

                    // Insert the option in the select
                    $("#attributes").append(`<option value="${attribute.IdAttribute}">${attribute.Text}</option>`);
                }
            });

            // Refresh the selectpicker
            $("#attributes").selectpicker("refresh");

            getAttributesSelected();
        }
    )
}
function getAttributesSelected() {

    get_call(
        BACKEND.PRODUCT.ATTRIBUTES,
        {
            IdProduct: Url.Params.IdProduct
        },
        function (data) {

            // Get the values
            values_keys = Object.keys(data);

            // Check that is not null
            if (values_keys.length > 0) {

                // Set the values in the attributes select
                $("#attributes").val(values_keys);

                $("#attributes").selectpicker("refresh");

                attribute_global.ValuesIdsSelected = data;
            }

            renderAttributesAccordions();
        }
    );

}
function getAttributeValues(idAttribute) {

    // Get the array of the attribute values selcted
    var attribute_values_selected = (idAttribute in attribute_global.ValuesIdsSelected) ? attribute_global.ValuesIdsSelected[idAttribute] : [];

    // Create new id table
    var id_table = "dtAttributes" + idAttribute + Date.now();

    // Update the id of the table
    $(`[name="dtAttributes${idAttribute}"]`).attr("id", id_table);

    // Call the api
    get_call(
        BACKEND.ATTRIBUTE_VALUE.ALL,
        {
            IdAttribute: idAttribute
        },
        function (data) {

            // Build the picker
            buildPicker(data, "#attributes_select_" + idAttribute, "IdAttributeValue", "Text", attribute_values_selected);
        }
    );

    // Init all the tooltips
    initTooltip();
}

function saveValuesIdsSelected(idAttribute, idAttributeValue) {

    // Check if the idAttribute exists in the obj
    if (!(idAttribute in attribute_global.ValuesIdsSelected))
        // Init the array for these attribute
        attribute_global.ValuesIdsSelected[idAttribute] = [];

    // Check if the idAttributeValue exists in the array of the attribute
    if (attribute_global.ValuesIdsSelected[idAttribute].includes(idAttributeValue)) {

        // Remove the idAttributeValue from the array
        attribute_global.ValuesIdsSelected[idAttribute] = attribute_global.ValuesIdsSelected[idAttribute].filter(idValue => {

            // Check that the idvalue is not the idAttributeValue
            if (idValue != idAttributeValue)
                return idValue;
        });

        // Check the length of the array
        if (attribute_global.ValuesIdsSelected[idAttribute].length == 0)
            delete attribute_global.ValuesIdsSelected[idAttribute];
    }
    else
        attribute_global.ValuesIdsSelected[idAttribute].push(idAttributeValue);

    showHideGenerateVariants();
}
function openDetailTab(idAttribute, idAttributeValue) {

    // Set the CreateOrChangeValue to true
    attribute_global.CreateOrChangeValue = true;

    // Open the detail page
    window.open(`/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${idAttribute}/${idAttributeValue}?from=product`);
}

// Post
function createAttributeValue(idAttribute) {

    showLoader();

    post_call(
        BACKEND.ATTRIBUTE_VALUE.INDEX,
        {
            IdAttribute: idAttribute
        },
        function (response) {

            hideLoader();

            openDetailTab(idAttribute, response);
        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}
function generateVariants() {

    // Hide the modal
    $("#modalAttributes").modal("hide");

    // Show the confirm modal
    confirmGenericModalWithSubtitle(
        saveVariants,
        function () {
            // Show the modal
            $("#modalAttributes").modal("show");
        },
        "Sei sicuro di voler aggiornare le varianti?",
        "È possibile, nel caso in cui ci fossero già delle varianti inserite, riscontrare delle differenze con dati precedentemente inseriti.<br> Il processo di generazione delle varianti è <b>IRREVERSIBILE</b>.",
        false,
        true
    );
}
function saveVariants() {

    var values_keys = Object.keys(attribute_global.ValuesIdsSelected);

    // Check if the ValuesIdsSelectedi is null or not
    if (values_keys.length > 0) {

        var attributes = [];

        // Get the attributes selected
        var attributes_selected = $("#attributes").val();

        // Cycle the values_keys to create the value to pass to the params obj
        values_keys.forEach(idAttribute => {

            // Check if the idAttribute is in the attributes_selected array
            if (attributes_selected.includes(idAttribute)) {

                // Push the value in the attributes array
                attributes.push({
                    IdAttribute: idAttribute,
                    IdsAttributesValues: $(`#attributes_select_${idAttribute}`).val()
                });
            }
            else
                delete attribute_global.ValuesIdsSelected[values_keys];
        });

        // Check that attributes is not null
        if (attributes.length > 0) {

            // Create params obj
            var params = {
                IdProduct: Url.Params.IdProduct,
                Attributes: attributes
            };

            post_call(
                BACKEND.PRODUCT.VARIANTS,
                params,
                function (data) {

                    // Change the focus of the tab (triggered the click of the variants tab)
                    $("#tabVariant-tab").trigger("click");

                    // Get the contents of the variants tab
                    getVariants(getContents);

                    // Show the notification
                    notificationSuccess("Varianti generate con successo!");
                },
                function () {

                    notificationError("Qualcosa è andato storto!");

                    // Show the modal
                    $("#modalAttributes").modal("show");
                }
            );
        }
        else
            notificationError("Assicurati che i valori selezionati siano coerenti con gli attributi!");
    }
    else
        notificationError("Inserisci almeno un attributo!");
}
function showHideGenerateVariants() {

    // Check if the ValuesIdsSelectedi is null or not
    if (Object.keys(attribute_global.ValuesIdsSelected).length > 0 || $("#attributes").val().length > 0)
        $("#generate_variants_container").show();
    else
        $("#generate_variants_container").hide();
}

// Add listener on attributes select
$(document).on("change", "#attributes", function () {

    renderAttributesAccordions();
    showHideGenerateVariants();
});
function renderAttributesAccordions() {

    // Get the values of the attribute selected
    var attributes_ids_selected = $("#attributes").val();

    // Cycle all LastIdsSelected
    attribute_global.LastIdsSelected.forEach(id => {

        // Check if the id is not in the attributes_ids_selected array
        if (!attributes_ids_selected.includes(id)) {

            // Remove the accordion
            $(`#accordion-attribute-container-${id}`).remove();
        }
    });

    // Check that the ids is not null
    if (attributes_ids_selected.length > 0) {

        // Init the attributes_selected array
        var attributes_selected = [];

        // Cycle ids
        attributes_ids_selected.forEach(attribute_id => {

            // Check if the attribute_id exists in the attribute_global.Attributes
            if (attribute_id in attribute_global.Attributes && !attribute_global.LastIdsSelected.includes(attribute_id))
                attributes_selected.push(attribute_global.Attributes[attribute_id]);
        });

        // Nav
        var template = new AC_Template();
        template.setTemplateId('attribute_container_template')
            .setContainerId('attributes_accordions_container')
            .setObjects(attributes_selected)
            .setAppend(true)
            .renderView();

        // Cycle the new attributes inserted
        attributes_selected.forEach(attribute => {

            getAttributeValues(attribute.IdAttribute);
        });
    }

    // Update the LastIdsSelected
    attribute_global.LastIdsSelected = attributes_ids_selected;

    showHideGenerateVariants();
}

// Add listener on the visibility of the tab
document.addEventListener("visibilitychange", function () {

    // Check the visibilityState value and that the CreateOrChangeValue is true
    if (document.visibilityState === "visible" && attribute_global.CreateOrChangeValue == true) {

        // Get the array of the attributes selected
        var attributes_selected = $("#attributes").val();

        // Check the length of the attributes selected
        if (attributes_selected.length > 0) {

            // Cycle all attributes
            attributes_selected.forEach(attribute => {

                getAttributeValues(attribute);
            });
        }

        // Set CreateOrChangeValue to false
        attribute_global.CreateOrChangeValue = false;
    }
});