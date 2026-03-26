let isDev = $("#is_prod").val() == 0 ? true : false;
let global_k_table_rendered_on_click = [];

//#region OnClick or OnChange

$(document).on("click", `[fileUpload][fileUploadType=${ENUM.BASE_FILES_UPLOAD_TYPE.ON_CLICK}]`, function () {
    // Upload
    uploadManager($(this).attr("nameInput") ?? $(this).attr("name"));
});
$(document).on("change", `[fileUpload][fileUploadType=${ENUM.BASE_FILES_UPLOAD_TYPE.ON_CHANGE}]`, function () {
    // Upload
    uploadManager($(this).attr("nameInput") ?? $(this).attr("name"));
});
$(document).on("click", `[fileRender]`, function () {

    // Get the tableId, macro and idType
    var tableId = `#${$(this).attr("fileRender")}`;
    var macro = $(tableId).attr("fileMacro");
    var idType = $(tableId).attr("fileType");

    // Check if the macro and idType are valid
    if (macro == null || idType == null) {
        notificationError("Macro or idType not valid!");
        return;
    }

    // Check if the table is already rendered on click
    if(global_k_table_rendered_on_click.includes(tableId))
        return;
    else
        global_k_table_rendered_on_click.push(tableId);

    // Upload
    getFilesManager(
        tableId,
        macro,
        idType,
    );
});

//#endregion

// Get
function getFileManager(idRow, macro, idType, tableId = null) {

    // Return if the macro or idType are not valid
    if (macro == null || idType == null) return;

    // Get the params
    var params = {
        IdRow: idRow ?? Url.Params[ENUM.BASE_FILES.IDS_DB[macro]],
        Macro: macro,
        Type: idType,
    }

    // Call api
    get_call(
        BACKEND.UTILITY.FILE,
        params,
        function (response) {

            // Init object
            var objGeneric = {
                IdRow: idRow,
                Macro: macro,
                Type: idType,
            }

            // Init object render
            var objRender = {
                TableId: tableId,
            }

            // Clear the modal
            clearInputsByContainer("#editFileModal");

            // Fill the modal
            fillContentByNames("#editFileContinerGeneric", objGeneric);
            fillContentByNames("#editFileContinerRender", objRender);
            fillContentByNames("#editFileContiner", response);

            // Show the modal
            $("#editFileModal").modal("show");

            // Hide the loader
            hideLoader();
        },
        function () {
            // Show the notification
            notificationError("Qualcosa è andato storto, riprova!");

            // Return
            return [];
        },
        false
    );
}
function getFilesManager(tableId, macro, idType, idRow = null, extras = null) {

    // Return if the macro or idType are not valid
    if (macro == null || idType == null || tableId == null) notificationError("Macro, idType or tableId not valid!");

    // Make int all the params
    idRow = idRow != null ? parseInt(idRow) : Url.Params[ENUM.BASE_FILES.IDS_DB[macro]];
    idType = parseInt(idType);
    macro = parseInt(macro);

    // Try to get extra from the table if not passed as param
    if (extras == null) extras = getExtras(tableId);

    // Get the params
    var params = {
        IdRow: idRow,
        Macro: macro,
        Type: idType,
        Extras: JSON.stringify(extras),
    }

    // Get the idTable
    idTable = `${tableId.includes("#") ? tableId : `#${tableId}`}`;

    // Render the table
    kT = new KTable(idTable, {
        ajax: {
            url: BACKEND.UTILITY.FILES,
            data: params
        },
        sortable: ["IdFile", "FileExtension"],
        columns: [
            {
                title: "Preview",
                image: true,
                render: function (data) {

                    var response = "";

                    // Check the type
                    switch (data.FileExtension) {
                        case ENUM.BASE_FILES_TYPES.ATTACHMENT:
                            response = "/assets/backend/img/img-file.png";
                            break;

                        case ENUM.BASE_FILES_TYPES.IMAGE:
                        case ENUM.BASE_FILES_TYPES.VIDEO:
                        case ENUM.BASE_FILES_TYPES.GENERIC:
                            response = data.FullPath
                            break;

                        default:
                            break;
                    }

                    // Return the response
                    return response;
                },
            },
            {
                title: "Azioni",
                render: function (data) {

                    // Build the buttons
                    var buttons = getCustomButtons(tableId, data.IdFile, macro, idType);

                    // If has the show action
                    if (ENUM.BASE_FILES_TYPES.ACTIONS[idType].includes(ENUM.BASE_FILES_ACTIONS.SHOW))
                        buttons += `<a href="${data.FullPath}" class="btn btn-sm btn-link text-primary" target="_blank">
                                                <i class="fa fa-fw fa-eye"></i>
                                            </a>`;

                    // If has the show action
                    if (ENUM.BASE_FILES_TYPES.ACTIONS[idType].includes(ENUM.BASE_FILES_ACTIONS.EDIT))
                        buttons += `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="getFileManager(${data.IdFile}, '${macro}', ${idType}, '${tableId}')">
                                                                <i class="fa fa-fw fa-edit"></i>
                                                            </button>`;

                    // If has the download action
                    if (ENUM.BASE_FILES_TYPES.ACTIONS[idType].includes(ENUM.BASE_FILES_ACTIONS.DELETE))
                        buttons += `<button type="button" onclick="deleteFileManager(${data.IdFile}, '${macro}', ${idType}, '${tableId}')" class="btn btn-sm btn-link text-danger">
                                                                <i class="fa fa-fw fa-trash"></i>
                                                            </button>`;

                    // Return the buttons
                    return buttons;

                },
            },
        ],
        events: {
            sortableCompleted(data) {

                // Check tha data length
                if (data.length == 0)
                    return;

                // Call the api
                reorderFiles(macro, idType, data);
            },
            completed(data) {
                hideLoader();
            }
        },
    });
}

// Post
function uploadManager(name) {

    // Get the attr
    var attr = $(`[nameInput = "${name}"]`).length != 0 ? "nameInput" : "name";

    // Get the IdRow and the Macro
    var idRow = Url.Params[ENUM.BASE_FILES.IDS_DB[$(`[${attr}="${name}"]`).attr("fileMacro")]];
    var idMacro = $(`[${attr} = "${name}"]`).attr("fileMacro");
    var idType = $(`[${attr} = "${name}"]`).attr("fileType");

    // Check if the IdRow and Macro are valid
    if (!idRow || !idMacro) {

        // Show the notification
        $("#is_prod").val() ? notificationError("IdRow or Macro not valid!") : null;
        return;
    }

    // Get the params
    var params = {
        IdRow: idRow,
        Macro: idMacro,
        Type: idType
    }

    // Get the element
    var element = $(`[${attr} = "${name}"]`);

    // Get the extras
    var extra = getExtras(element);

    // Add the extra to the params
    if (Object.keys(extra).length > 0)
        params["Extras"] = JSON.stringify(extra);

    // Call api
    file_call(
        BACKEND.UTILITY.FILES,
        params,
        `[name = "${name}"]`,
        function () {

            // Show the notification
            notificationSuccess("Contenuto caricato con successo!");

            // Render the new files
            if (!element.attr("callback") && !element.attr("renderTableId"))
                if (isDev)
                    notificationError("Callback or table name to render not set!");
                else
                    return;

            // Make the callback
            if (element.attr("renderTableId")) {

                // Get the files
                getFilesManager(element.attr("renderTableId"), idMacro, idType, idRow, extra);
                return;
            }

            // Create the callback
            var callback = function (callback) {
                eval(element.attr("callback"));
            }

            // Call the callback
            callback();
        },
        function () {
            hideLoader();

            notificationError("Qualcosa è andato storto, riprova!");
        }
    );

}

// Put
function updateFileManager() {

    // Get the IdRow and the Macro
    if (!checkMandatory("#editFileContiner")) return;

    // Get params
    var params = getContentData("#editFileContinerGeneric");

    // Add the data to the params
    params.Data = getContentData("#editFileContiner");

    // Call api
    put_call(
        BACKEND.UTILITY.FILE,
        params,
        function () {

            // Show the notification
            notificationSuccess("Contenuto aggiornato con successo!");

            // Hide the modal
            $("#editFileModal").modal("hide");

            // Render the new files
            if (params.Type == null || params.Macro == null)
                if (isDev)
                    notificationError("Type or Macro not set!");
                else
                    return;

            // Get the tableId
            var tableId = $(`#editFileModal #editFileContinerRender [name='TableId']`).val();

            // Get the files
            getFilesManager(tableId, params.Macro, params.Type, Url.Params[ENUM.BASE_FILES.IDS_DB[params.Macro]]);
        },
        function () {
            // Show the notification
            notificationError("Qualcosa è andato storto, riprova!");
        }
    );
}
function reorderFiles(macro, type, data) {

    // Return if the data is not valid
    if (!data || data.length == 0) return;

    // Show the loader
    showLoader();

    // Call api
    put_call(
        BACKEND.UTILITY.REORDERFILES,
        {
            IdRow: Url.Params[ENUM.BASE_FILES.IDS_DB[macro]],
            Macro: macro,
            Type: type,
            Data: data
        },
        function (data) {

            // Hide the loader
            hideLoader();

            // Show the notification
            notificationSuccess("Ordine salvato con successo!");
        },
        function () {

            // Hide the loader
            hideLoader();

            // Show the notification
            notificationError("Qualcosa è andato storto, riprova!");
        }
    );
}

// Delete
function deleteFileManager(idRow, macro, idType, tableId = null, callback) {

    // Show the modal
    confirmDeleteModal(
        function () {

            // Show the loader
            showLoader();

            // Get the params
            var params = {
                IdRow: idRow,
                Macro: macro,
                Type: idType,
            }

            // Call api
            delete_call(
                BACKEND.UTILITY.FILE,
                params,
                function () {

                    // Show the notification
                    notificationSuccess("Contenuto eliminato con successo!");

                    // Call the callback
                    if (tableId)
                        getFilesManager(tableId, macro, idType);
                    else if (!tableId && callback)
                        callback();
                    else if (isDev)
                        notificationError("TableId or callback not set!");

                    // Hide the loader
                    hideLoader();
                },
                function () {
                    // Hide the loader
                    hideLoader();

                    // Show the notification
                    notificationError("Qualcosa è andato storto, riprova!");
                }
            );
        }
    );
}

//#region Utils

function getExtras(idComponent) {

    let extras = {};
    let domElement = $(idComponent)[0];

    // Check if the element is valid and has attributes
    if (domElement && domElement.attributes) {

        // Cicle all the attr
        Array.from(domElement.attributes).forEach(attr => {

            // Check if the attr starts with fileExtra-
            if (attr.name.toLowerCase().startsWith("fileextra-")) {

                // Get the key
                let key = attr.name.replace(/fileextra-/i, ""); // insensitive

                // Explode the key by -
                let keys = key.split("-");

                // Set all the keys to first letter uppercase
                keys = keys.map(key => key.charAt(0).toUpperCase() + key.slice(1).toLowerCase());

                // Join the keys
                key = keys.join("");

                // Set the extra
                extras[key] = attr.value;
            }
        });
    }

    // Return the extras
    return extras;
}
function getFilesFromTable(table) {

    // Get fileMacro and fileType
    var fileMacro = $(table).attr("fileMacro");
    var fileType = $(table).attr("fileType");

    // Check if the fileMacro and fileType are valid
    if (!fileMacro || !fileType) {
        notificationError("FileMacro or fileType not valid!");
        return;
    }

    // Get the fileRow
    var fileRow = Url.Params[ENUM.BASE_FILES.IDS_DB[fileMacro]];

    // Check if the fileRow is valid
    if (!fileRow) {
        notificationError("FileRow not valid!");
        return;
    }

    // Get the extras
    var extras = getExtras(table);

    // Get the files
    getFilesManager(table, fileMacro, fileType, fileRow, extras);
}

//#endregion

//#region Buttons

function getCustomButtons(tableId, idFile = null, macro = null, type = null) {

    // Check if the tableId is valid
    if (tableId == null || tableId == "" || tableId == undefined) {

        // Show the notification
        notificationError("TableId not valid!");
        // Return
        return "";
    }

    // Get the buttons template
    var buttons = $(`${tableId}`).attr("hasCustomButtons");

    // Check if the buttons template is valid
    if (buttons == null || buttons == "" || buttons == undefined)
        return "";

    // Init the response
    var response = "";

    // Slit the buttons template
    buttonsTemplates = buttons.split(",");

    // Cicle all the buttons templates
    buttonsTemplates.forEach(buttonTemplate => {

        // Get the buttons template
        response += $(`#${buttonTemplate}`).html();
    });

    // Sostituzione dei segnaposto con i valori reali
    const replacements = {
        '{{IdFile}}': idFile,
        '{{IdRow}}': Url.Params[ENUM.BASE_FILES.IDS_DB[macro]],
        '{{Macro}}': macro,
        '{{Type}}': type,
    };

    for (const [placeholder, value] of Object.entries(replacements))
        response = response.replace(new RegExp(placeholder, 'g'), value);

    // Return the buttons template
    return response;
}

//#endregion

//#region Captions

// Get
function getFilesManagerCaption(idFile, macro, type, idRow, format) {

    var params = {
        IdFile: idFile,
        Macro: macro,
        Type: type,
        IdRow: idRow,
        Format: format
    };

    // Call api
    get_call(
        BACKEND.UTILITY.CAPTION,
        params,
        function (response) {

            // Build the modal
            buildModalCaption(format);

            // Fill modalCaptionFileManagerGeneric
            fillContentByNames("#modalCaptionFileManagerGeneric", params);

            // Check the format and fill the modal
            if (format == ENUM.BASE_FILES_CAPTIONS_TYPES.MULTI_LANG) {

                // Cicle all the languages
                response.forEach(langObj => {

                    // Fill the modal
                    fillContentByNames(`#fileCaption-TabLang-${langObj.Language}`, langObj);
                });

            } else if (format == ENUM.BASE_FILES_CAPTIONS_TYPES.MONO_LANG) {

                // Fill the modal
                fillContentByNames(`#modalCaptionFileManagerBody`, response);
            }

            // Check if the response is valid
            $(`#modalCaptionFileManager`).modal("show");
        },
        function () {
            // Show the notification
            notificationError("Qualcosa è andato storto, riprova!");

            // Return
            return [];
        },
        false
    );


}

// Put
function saveFilesManagerCaption() {

    // Get the params
    var params = getContentData(`#modalCaptionFileManagerGeneric`);

    // Init checkValidity
    var checkValidity = false;

    // Check mandatory data on the modal
    if (params.Format == ENUM.BASE_FILES_CAPTIONS_TYPES.MULTI_LANG) {
        // Check languages
        var languagesValidity = checkLanguagesTabs(true, "tabLangCaptionFile");

        // Add checkValidity
        checkValidity = languagesValidity.validity;

        // Add data
        params.Data = languagesValidity.Languages;

    } else if (params.Format == ENUM.BASE_FILES_CAPTIONS_TYPES.MONO_LANG) {
        // Check mandatory data
        checkValidity = checkMandatory(`#modalCaptionFileManagerBody`);

        // Add data
        params.Data = getContentData(`#modalCaptionFileManagerFields`);
    }

    // Check the validity
    if (checkValidity == false) {
        // Show the notification
        notificationError("Controlla i campi obbligatori!");
        return;
    }

    // Delete the format from the params
    delete params.Format;

    // Call api
    put_call(
        BACKEND.UTILITY.FILECAPTION,
        params,
        function (response) {

            // Empty the modal
            $(`#modalCaptionFileManagerFields`).empty();

            // Show the notification
            notificationSuccess("Caption aggiornata con successo!");

            // Hide the modal
            $(`#modalCaptionFileManager`).modal("hide");
        },
        function () {
            // Show the notification
            notificationError("Qualcosa è andato storto, riprova!");

            // Return
            return [];
        },
        false
    );

}

//#region Utils

function buildModalCaption(format) {

    // Empty the modal body
    $(`#modalCaptionFileManagerFields`).empty();

    // Check if the format is valid
    if (format == null) {
        notificationError("Format not valid!");
        return;
    }

    // Add the template to the modal
    $(`#modalCaptionFileManagerFields`).append($(`#${ENUM.BASE_FILES_CAPTIONS_TYPES.TEMPLATES[format]}`).html());

    // Check if the format is multi lang
    if (format == ENUM.BASE_FILES_CAPTIONS_TYPES.MULTI_LANG) {

        // Add the style to the modal (background-color: var(--bg-main);)
        $(`#modalCaptionFileManagerBody`).css("background-color", "var(--bg-main)");

        // Nav
        var template = new AC_Template();
        template.setTemplateId('languageCaptionFileTabNavTemplate')
            .setContainerId('captionFileNavTab')
            .setObjects(global.Languages)
            .setPrepend(true)
            .renderView();

        // Content
        var template = new AC_Template();
        template.setTemplateId('modalCaptionLanguageTemplate')
            .setContainerId('captionFileNavTabContent')
            .setObjects(global.Languages)
            .setPrepend(true)
            .renderView();

        // Set the active class to the first tab
        $(`#captionFileNavTab .nav-link`).removeClass("active");
        $(`#captionFileNavTabContent .nav-link`).removeClass("show");

        // Get the first language
        var language = global.Languages[0].Language;

        // Set the active class
        $(`#captionFileNavTab #fileCaption-TabLang-${language}-Tab`).addClass("active");
        $(`#captionFileNavTabContent #fileCaption-TabLang-${language}`).addClass("active show");


    }

}

//#endregion

//#endregion