var variant_global = {
    VariantTable: null,
    CurrentVariantDefaultValue: null,
};

// Add listener on tab click
$(document).one("click", "#tabVariant-tab", function () {
    getVariants(null, true);
});

// Get
function getVariants(callback = null, is_first_time = false) {
    get_call(
        BACKEND.PRODUCT.VARIANTS,
        {
            IdProduct: Url.Params.IdProduct,
        },
        function (variants) {
            fillImageVariantSelect(variants.Values, callback);

            var extra_column_defs = [];

            $.each(variants.Columns, function (index, column) {
                extra_column_defs.push({
                    title: column,
                    filterable: true,
                    render: function (data) {
                        return data[column];
                    },
                });
            });

            // Create the columnDefs array
            var columnDefs = [];

            // Merge the columnDefs with extra_column_defs
            columnDefs = $.merge(columnDefs, extra_column_defs);

            // Build the pickers only the first time
            if (is_first_time) {
                // Build the picker
                buildPicker(variants.Values, "#IdProductVariant", "IdProductVariant", "Name");

                // Check if the Old Default Value is present in the new Values array
                var oldDefaultValuePresent = [];
                if (variants.Values) {
                    oldDefaultValuePresent = variants.Values.some(function (variant) {
                        return parseInt(variant.IdProductVariant) == parseInt($("#IdProductVariantDefault").val());
                    });
                }

                // Build the IdProductVariantDefault picker
                buildPicker(variants.Values, "#IdProductVariantDefault", "IdProductVariant", "Name", oldDefaultValuePresent ? $("#IdProductVariantDefault").val() : null);
            }

            // Set the default value
            variant_global.CurrentVariantDefaultValue = variants.DefaultValue;

            // Remove the tooltip
            $(".tooltip").remove();

            // Push the action obj in the columnDefs array
            columnDefs.push(
                {
                    title: "Prezzo",
                    render: function (data) {
                        return !isEmpty(data.Price) ? `${data.Price} €` : `-`;
                    },
                },
                {
                    title: "Prezzo Scontato",
                    render: function (data) {
                        return !isEmpty(data.PriceDiscount) ? `${data.PriceDiscount} €` : `-`;
                    },
                },
                {
                    title: "Quantità",
                    render: function (data) {
                        return data.Quantity;
                    },
                },
                {
                    title: "Azioni",
                    render: function (data) {
                        return `
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="getVariantContents(${data.IdProductVariant})">
                                <i class="fa fa-fw fa-images"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="getVariant(${data.IdProductVariant})">
                                <i class="fa fa-fw fa-edit"></i>
                            </button>
                        `;
                    },
                }
            );

            // Set the table html
            $("#variant_table_container").html($("#variant_table_template").html());

            // Create new id table
            var id_table = "dtVariants" + Date.now();

            // Update the id of the table
            $(`[name="dtVariants"]`).attr("id", id_table);

            // Initialize the datatable
            kT = new KTable(
                `[name="dtVariants"]`,
                {
                    data: variants.Values,
                    columns: columnDefs,
                    buttons:
                        `<button type="button" class="btn btn-outline-success" onclick="showVariantModal()">
                            <i class="fa fa-fw fa-money-bills"></i> Gestione Prezzi
                        </button>`
                    ,
                    sort: {
                        0: "ASC"
                    },
                    pagination: {
                        page: 1,
                        slices: [15, 30, 45],
                    },
                    events: {
                        pageChanged() {
                        },
                        completed(data) {

                            hideLoader();
                        }
                    }
                }
            );

            // Assign the table to the global var
            variant_global.VariantTable = kT;
        }
    );
}
function getVariant(idProductVariant) {
    get_call(
        BACKEND.PRODUCT.VARIANT,
        {
            IdProduct: Url.Params.IdProduct,
            IdProductVariant: idProductVariant,
        },
        function (data) {

            showVariantModal(data);
        }
    );
}
function showVariantModal(data = null) {

    // Clear all the inputs values
    $("#modalVariantBody input").val("");
    $("#modalVariantBody select").val("").selectpicker("refresh");

    // Check if the data is not null
    if (!isEmpty(data)) {

        // Fill the data
        fillContentByNames("#modalVariantBody", data);
        // Refresh the selectpicker
        $("#modalVariantBody select").selectpicker("refresh");

        $("#id_product_variant_container").hide();
    }
    else
        $("#id_product_variant_container").show();

    // Show the modal
    $("#modalVariant").modal("show");
}

// Put
function saveVariant() {
    // Check mandatory data in the modal
    if (checkMandatory("#modalVariantBody")) {
        // Get the params
        var params = getContentData("#modalVariantBody");
        params["IdProduct"] = Url.Params.IdProduct;

        showLoader();

        put_call(BACKEND.PRODUCT.VARIANT, params, function () {
            // Reload the variants table
            getVariants();

            // Hide the modal
            $("#modalVariant").modal("hide");

            hideLoader();

            notificationSuccess("Variante modidica con successo!");
        });
    }
}
function saveVariantDefaultValue(idProductVariant) {

    // Check if the value is not the default value
    if (variant_global.CurrentVariantDefaultValue != idProductVariant) {

        showLoader();

        put_call(
            BACKEND.PRODUCT.VARIANTDEFAULT,
            {
                IdProduct: Url.Params.IdProduct,
                IdProductVariant: idProductVariant,
            },
            function () {

                getVariants();

                hideLoader();

                notificationSuccess("Valore di default salvato con successo!");
            },
            function () {
                hideLoader();
                notificationError("Qualcosa è andato storto!");
            }
        );
    }
    else
        notificationInfo("Il valore selezionato è già il valore di default!");
}

//#region Images

function getVariantContents(idProductVariant) {

    // Show the modal
    $("#modalVariantImages").modal("show");

    // Set the id in the modal
    $("#modalVariantImages #IdProductVariant").val(idProductVariant);

    // Get the contents
    getContents(idProductVariant);
}

//#endregion