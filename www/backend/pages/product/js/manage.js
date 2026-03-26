var checkSelect = false;

$(document).ready(function () {

    getLanguagesTabs();
    // Get the products data
    getCategories();

});

//#region Product

// Get
function getProduct() {

    get_call(
        BACKEND.PRODUCT.INDEX,
        {
            IdProduct: Url.Params.IdProduct
        },
        function (response) {

            // Set main data
            fillContentByNames("", response);

            // Set languages
            response.Languages.forEach(product_language => {

                // Check that the product_language is valid and the title is not null
                if (product_language.IsValid = 1 && !isEmpty(product_language.Title)) {

                    // Remove the opacity class from the nav flas
                    $(`#tabLang-${product_language.IdLanguage}-tab`).removeClass("op-5");

                    // Insert the data in the inputs
                    fillContentByNames(`#tabLang-${product_language.IdLanguage}`, product_language);
                }

                // Load links and attachments
                setTimeout(function () {
                    getLinks(product_language.IdLanguage);
                    getAttachments(product_language.IdLanguage);
                }, 100 * product_language.IdLanguage);
            });

            // Refresh the selectpicker
            $(".selectpicker").selectpicker("refresh");

            // If the product has variants build the variants select
            if (response.HasVariants == ENUM.BASE_PRODUCT_VARIANT_TYPE.WITH_VARIANTS)
                buildPicker(response.Variants, "#IdProductVariantDefault", "IdProductVariant", "Name", response.IdVariantDefault);

            initFileCustomRender();

            togglePricesTab(false);

            hideLoader();
        },
        function () {
            renderRouterErrorPage();
        }
    )

}
function getProducts(callback) {

    get_call(
        BACKEND.PRODUCT.ALL,
        null,
        function (response) {

            // Build the select
            buildPicker(response.filter(product => product.IdProduct != Url.Params.IdProduct), "[name='IdsProductsRelated']", "IdProduct", "Title");

            // Callback
            callback();
        },
        function () {
            notificationError("Qualcosa è andato storto, riprova!");
        }
    )
}

// Put
function saveProduct() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs(false);

    // Check the validity of the languages tab and the common container data
    if (tabs_data.validity && checkMandatory("#common_container") && checkMandatoryTabs()) {

        // Create the params
        var params = {
            IdProduct: Url.Params.IdProduct,
            Languages: tabs_data.Languages
        };

        // Create the final params
        var final_params = { ...params, ...getContentData("#common_container", true), ...getPriceTabData() };
        // Check if the IdsCategories is not an array
        if (!Array.isArray(final_params.IdsCategories))
            final_params.IdsCategories = [final_params.IdsCategories];

        put_call(
            BACKEND.PRODUCT.INDEX,
            final_params,
            function () {

                window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product?st=ok&m=Prodotto salvato correttamente!`;
            }
        );
    }
}

//#endregion

//#region Categories

// Get
function getCategories() {

    get_call(
        BACKEND.CATEGORY_PRODUCT.SELECT,
        null,
        function (response) {

            // Check if the categories is not empty
            if (response.length > 0) {

                // Cycle the categories
                response.forEach(parent_category => {

                    // Init the html of the optgroup
                    var html = `<optgroup label="${parent_category.CategoryParentName}">`;

                    // Cycle parent_category childs
                    parent_category.Childs.forEach(child_category => {

                        html += `<option value="${child_category.IdCategory}">${child_category.Name}</option>`;
                    });

                    html += `</optgroup>`;

                    // Append the html to the select
                    $('#categorySelect').append(html);
                });

                // Refresh the select
                $('#categorySelect').selectpicker('refresh');

                getProducts(function () {
                    getProduct();
                });
            }
        },
        function () {

            notificationError("Qualcosa è andato storto, riprova!");
        }
    )
}

//#endregion

//#region Links

// Get
function getLinks(language) {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.PRODUCT.LINKS)
        .setAjaxData("IdProduct", Url.Params.IdProduct)
        .setAjaxData("IdLanguage", language)
        .setIdTable("dtLinks" + language)
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
                    title: 'Link',
                    render: function (data, type, row) {

                        return `<a href="${row.Link}" target="_blank">${row.Link}</a>`;
                    }
                },
                {
                    title: 'Azioni',
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="btn btn-sm btn-link text-danger" onclick="deleteLink(${row.IdLink}, ${row.IdLanguage})">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `
                    }
                },
            ],
            fnRowCallback: function (row, data, iDisplayIndex, iDisplayIndexFull) {
                $(row).attr("id", data.IdLink);
            },
        })
        .setReorderCallback(function (data) {

            // Check the length of the data
            if (data.length > 0) {
                put_call(
                    BACKEND.PRODUCT.LINKSORDER,
                    {
                        IdProduct: Url.Params.IdProduct,
                        IdLanguage: language,
                        Order: data,
                    },
                    function () {
                        notificationSuccess("Ordine salvato con successo!");
                    },
                    function () {
                        notificationError("Qualcosa è andato storto, riprova!");
                    }
                );
            }
        })
        .initDatatable();

}

// Post
function insertLink(idLanguage) {

    // Check mandatory data
    if (checkMandatory("#link_container_" + idLanguage)) {

        post_call(
            BACKEND.PRODUCT.LINK,
            {
                IdProduct: Url.Params.IdProduct,
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
                BACKEND.PRODUCT.LINK,
                {
                    IdProduct: Url.Params.IdProduct,
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

//#region Attachments

// Get 
function getAttachments(language) {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.PRODUCT.ATTACHMENTS)
        .setAjaxData("IdProduct", Url.Params.IdProduct)
        .setAjaxData("IdLanguage", language)
        .setIdTable(`dtAttachments${language}`)
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
                        return `<i class="fa fa-fw fa-file"></i>`;
                    },
                },
                {
                    title: "Nome",
                    render: function (data, type, row) {
                        return `<a href="${row.FullPath}" target="_blank">${row.FileName}</a>`;
                    },
                },
                {
                    title: "Azioni",
                    render: function (data, type, row) {
                        return `<button type="button" onclick="deleteAttachment(${row.IdAttachment}, ${language})" class="btn btn-sm btn-link text-danger">
                                    <i class="fa fa-fw fa-trash"></i>
                                </button>`
                    },
                },
            ],
            fnRowCallback: function (row, data, iDisplayIndex, iDisplayIndexFull) {
                $(row).attr("id", data.IdAttachment);
            },
        })
        .setReorderCallback(function (data) {

            // Check tha data length
            if (data.length > 0) {
                put_call(
                    BACKEND.PRODUCT.ATTACHMENTSORDER,
                    {
                        IdProduct: Url.Params.IdProduct,
                        IdLanguage: language,
                        Order: data,
                    },
                    function (data) {
                        notificationSuccess("Ordine salvato con successo!");
                    },
                    function () {
                        notificationError("Qualcosa è andato storto, riprova!");
                    },
                    false
                );
            }
        })
        .initDatatable();


}

// Post
function uploadAttachments(language) {

    showLoader();

    file_call(
        BACKEND.PRODUCT.ATTACHMENTS,
        {
            IdProduct: Url.Params.IdProduct,
            IdLanguage: language
        },
        `[name="Attachments${language}"]`,
        function () {

            // Get the attachments by the language
            getAttachments(language);

            hideLoader();

            notificationSuccess("Allegato caricato con successo!");
        },
        function () {
            hideLoader();

            notificationError("Qualcosa è andato storto, riprova!");
        }
    );

}

// Delete
function deleteAttachment(idAttachment, idLanguage) {

    confirmDeleteModal(
        function () {

            delete_call(
                BACKEND.PRODUCT.ATTACHMENT,
                {
                    IdProduct: Url.Params.IdProduct,
                    IdAttachment: idAttachment
                },
                function () {

                    // Render the links table
                    getAttachments(idLanguage);
                }
            )
        }
    )
}

//#endregion

//#region Images

$(document).on("click", "#tabImages-tab", function () {
    // Get the contents
    getContents();
});
function fillImageVariantSelect(variants, callback = null) {

    // Remove the variants options and refresh
    $("#IdProductVariantImage [variant_option]").remove().selectpicker("refresh");

    if (!isEmpty(variants)) {

        // Cycle all variants to add the option in the select
        variants.forEach(variant => {

            $("#IdProductVariantImage").append(`<option value_name="${variant.Name}" value="${variant.IdProductVariant}" variant_option>${variant.Name}</option>`);
        });

        // Refresh
        $("#IdProductVariantImage").val("0").selectpicker("refresh");
    }

    if (callback != null)
        callback();
}

// Get
function getContents(idProductVariant = 0) {

    // Build the table name
    var tbName = idProductVariant == 0 ? "#dtContents" : `#dtVariantContents`;

    // Check if the table is already initialized
    if (isEmpty(kT[`${idProductVariant}`]) == false)
        kT[`${idProductVariant}`].refresh();

    // Build the table
    kT[`${idProductVariant}`] = new KTable(tbName, {
        ajax: {
            url: BACKEND.PRODUCT.CONTENTS,
            data: {
                IdProduct: Url.Params.IdProduct,
                IdProductVariant: idProductVariant
            }
        },
        sortable: ["Id", "Type"],
        columns: [
            {
                title: "Preview",
                image: true,
                render: function (data) {
                    return data.Preview ?? data.FullPath;
                },
            },
            {
                title: "Azioni",
                render: function (data) {
                    return `<button tooltip="Didascalia" type="button" onclick="getContentCaption(${data.Id}, ${data.Type})" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-fw fa-quote-left"></i>
                            </button>
                            <button type="button" onclick="deleteFileManager(${data.Id}, ${ENUM.BASE_FILES.PRODUCT}, ${data.Type}, null, function () { getContents(${idProductVariant}) })" class="btn btn-link text-danger">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>`
                },
            },
        ],
        events: {
            sortableCompleted(data) {

                put_call(
                    BACKEND.PRODUCT.CONTENTSORDER,
                    {
                        IdProduct: Url.Params.IdProduct,
                        Order: data,
                    },
                    function (response, message) {

                        hideLoader();
                        notificationSuccess(message);
                    }
                )
            },
            completed(data) {
                hideLoader();
            }
        },
    });

}

// Delete
function deleteContent(contentRefId, contentType) {

    confirmDeleteModal(
        function () {

            showLoader();

            delete_call(
                BACKEND.PRODUCT.CONTENT,
                {
                    IdProduct: Url.Params.IdProduct,
                    ContentRefId: contentRefId,
                    ContentType: contentType
                },
                function () {

                    // Get the contents
                    getContents();

                    hideLoader();

                    notificationSuccess("Contenuto eliminato con successo!");
                }
            )
        }
    )
}

//#region Images

// Post
function uploadImages(generic = true) {

    // Create the data object
    const data = {
        IdProduct: Url.Params.IdProduct,
    }

    // Get the rigth images
    if (!generic)
        data.IdProductVariant = $("#modalVariantImages #IdProductVariant").val();

    // Get the input files
    const inputFiles = generic ? `[name="GenericImages[]"]` : `[name="VariantImages[]"]`;

    // Build the success message
    const successMessage = generic ? "Immagini caricate con successo!" : "Immagini della variante caricate con successo!";

    showLoader();

    // Call the file upload
    file_call(
        BACKEND.PRODUCT.IMAGES,
        data,
        inputFiles,
        function () {

            // Get the images
            generic ? getContents() : getVariantContents(data.IdProductVariant);

            hideLoader();

            notificationSuccess(successMessage);
        },
        function () {
            hideLoader();

            notificationError("Qualcosa è andato storto, riprova!");
        }
    );
}

//#endregion

//#region Videos

// Post
function insertVideo(generic = true) {

    // Check mandatory data
    if (checkMandatory("#video_container")) {

        post_call(
            BACKEND.PRODUCT.VIDEO,
            {
                IdProduct: Url.Params.IdProduct,
                IdProductVariant: generic ? 0 : $("#modalVariantImages #IdProductVariant").val(),
                VideoCode: generic ? $(`#videoUrl`).val() : $(`#videoUrlVariant`).val()
            },
            function () {

                // Clear the input
                generic ? $(`#videoUrl`).val("") : $(`#videoUrlVariant`).val("");

                // Render the contents table
                generic ? getContents() : getVariantContents(generic ? 0 : $("#modalVariantImages #IdProductVariant").val());

                // Show the notification
                notificationSuccess("Video caricato con successo!");
            }
        );
    }
}

//#endregion

//#region Captions

// Get 
function getContentCaption(contentRefId, contentType) {

    getContentCaptionsTranslations(
        BACKEND.PRODUCT.CAPTION,
        {
            IdProduct: Url.Params.IdProduct,
            ContentRefId: contentRefId,
            ContentType: contentType
        }
    );
}

// Put
function saveContentCaption() {

    // Get the params
    var params = getContentData("#modalCaptionBody");

    // Add IdProduct to params
    params["IdProduct"] = Url.Params.IdProduct;
    delete params["Caption"];

    saveContentCaptionTranslations(
        BACKEND.PRODUCT.CAPTION,
        params
    );
}

//#endregion

//#endregion

//#region Prices and Variants

$(document).on("change", '[name="HasVariants"]', function () {
    // Toggle the prices tab
    togglePricesTab();

    // Show the default variant container
    if (parseInt($(this).val()) == ENUM.BASE_PRODUCT_VARIANT_TYPE.NO_VARIANTS) {

        // Remove mandatory from the mandatory_for_variant
        $(`[mandatory_for_variant]`).removeAttr("mandatory");

        // Hide the containers with mandatory_for_variant_container
        $(`[mandatory_for_variant_container]`).hide();

    } else {

        // Add mandatory to the mandatory_for_variant
        $(`[mandatory_for_variant]`).attr("mandatory", true);

        // Show the containers with mandatory_for_variant_container
        $(`[mandatory_for_variant_container]`).show();

        // Click the variant tab
        $("#tabVariant-tab").trigger("click");
    }
});
$(document).on("change", '[name="ShowPriceRange"]', function () {
    // Check if the checkbox is not checked
    if (!$(this).is(":checked")) {

        // Show the price range container
        $(`[price_range_container]`).show();

        // Add mandatory class
        $(`[mandatory_price_range]`).attr("mandatory", true);

    } else {

        // Hide the price range container
        $(`[price_range_container]`).hide();

        // Remove mandatory class
        $(`[mandatory_price_range]`).removeAttr("mandatory");
    }
});


function togglePricesTab(change_tab = true) {

    //  Get the value
    var value = $('[name="HasVariants"]').val();

    if (!isEmpty(value)) {

        if (parseInt(value) == ENUM.BASE_PRODUCT_VARIANT_TYPE.NO_VARIANTS) {

            $("#tabPrices-tab").attr("mandatory_tab_content", true);

            showHidePriceTabs(ENUM.BASE_PRODUCT_VARIANT_TYPE.NO_VARIANTS, ENUM.BASE_PRODUCT_VARIANT_TYPE.WITH_VARIANTS, change_tab);
        } else {

            // Remove the mandatory tab content
            $("#tabPrices-tab").removeAttr("mandatory_tab_content");

            // Remove errore class
            $("#tabPrices-tab").removeClass("bg-danger");

            // Remove the errors classes
            $("#price_container .is-invalid").removeClass("is-invalid")

            showHidePriceTabs(ENUM.BASE_PRODUCT_VARIANT_TYPE.WITH_VARIANTS, ENUM.BASE_PRODUCT_VARIANT_TYPE.NO_VARIANTS, change_tab);
        }
    }
    else
        $(`[has_variants]`).hide();
}
function getPriceTabData() {

    var prices_data = {};

    // Check if the product has not variants
    if (parseInt($('[name="HasVariants"]').val()) == ENUM.BASE_PRODUCT_VARIANT_TYPE.NO_VARIANTS) {

        // Get the data
        prices_data = getContentData("#price_container");
    }

    return prices_data;
}
function showHidePriceTabs(show, hide, change_tab) {

    // Show / hide
    $(`[has_variants="${show}"]`).show();
    $(`[has_variants="${hide}"]`).hide();

    // Check if the tab selected is hidden
    var tab_selected_type = $("#nav-tab .active").attr("has_variants");

    // Check if is not undefined
    if (tab_selected_type != undefined && change_tab == true) {

        // Check if the tab selected is the same of the hide
        if (tab_selected_type == hide) {

            // Remove the active class from the tab
            $(`[has_variants]`).removeClass("active");
            $(".tab-pane.fade").removeClass("active").removeClass("show")

            // Get the first tab of the type
            var first_tab = $(`[has_variants="${show}"]`).first();
            // Get the id of the tab
            var tab_id = first_tab.attr("id");
            // Get the id of the tab content
            var tab_content_id = first_tab.attr("aria-controls");

            $(`#${tab_id}`).addClass("active");
            $(`#${tab_content_id}`).addClass("active").addClass("show");
            $(`[has_variants="${show}"]`).first().trigger("click");
        }
    }
}

//#endregion