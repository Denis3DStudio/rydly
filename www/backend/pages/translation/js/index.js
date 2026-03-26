$(document).ready(function () {

    getLanguages();
    renderTranslations();

    // setTimeout(() => {
    //     createCache();
    // }, 500);

    renderDeepl(false, true);
});

// Render
function renderTable() {

    kT = new KTable("#dtTranslation", {
        ajax: {
            url: BACKEND.TRANSLATION.ALL,
        },
        sort: {
            6: "DESC"
        },
        buttons: `
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="renderTable()">
                <i class="fa fa-fw fa-sync"></i> Ricarica
            </button>
        `,
        columns: [
            {
                title: 'Traduzione',
                render: function (data) {
                    var max = 50;
                    var striped = data.Translation.replace(/(<([^>]+)>)/gi, "");
                    var dots = (striped.length > max) ? '...' : '';

                    return striped.substr(0, max) + dots;
                }
            },
            {
                title: '',
                visible: false,
                render: function (data) {
                    return data.Translation.replace(/(<([^>]+)>)/gi, "");
                }
            },
            {
                title: '',
                visible: false,
                render: function (data) {
                    return `${data.Section}.${data.Page}.${data.Label}`
                }
            },
            {
                title: 'Sezione',
                filterable: true,
                render: function (data) {
                    return data.Section;
                }
            },
            {
                title: 'Pagina',
                filterable: true,
                render: function (data) {
                    return data.Page;
                }
            },
            {
                title: 'Etichetta',
                render: function (data) {
                    return data.Label;
                }
            },
            {
                title: 'Ultima modifica',
                render: function (data) {
                    return fDate("d/m/Y H:i", data.UpdateDate);
                }
            },
            {
                title: 'Lingue',
                render: function (data) {
                    var text = '';

                    global.Languages.forEach(language => {

                        // Check if website has this language
                        var disabled = data.Languages.indexOf(language.Language) > -1 ? '' : 'disabled';

                        text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`

                    });

                    return text;
                }
            },
            {
                title: 'Azioni',
                render: function (data) {
                    return `<div class="btn-group me-1">
                            <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('${data.Page}.${data.Label}')" tooltip="Copia negli Appunti"><i class="fa fa-fw fa-copy"></i></button>
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="#" class="dropdown-item" onclick="copyPageLabel('${data.Section}', '${data.Page}.${data.Label}', ${ENUM.BASE_PROGRAMMING_LANGUAGE.JS})">JS</a></li>
                                <li><a href="#" class="dropdown-item" onclick="copyPageLabel('${data.Section}', '${data.Page}.${data.Label}', ${ENUM.BASE_PROGRAMMING_LANGUAGE.PHP})">PHP</a></li>
                                <li><a href="#" class="dropdown-item" onclick="copyPageLabel('${data.Section}', '${data.Page}.${data.Label}')">P.L.</a></li>
                            </ul>
                        </div>
                        <a class="btn btn-outline-secondary" onclick="duplicateTranslation(${data.IdTranslation})" tooltip="Duplica">
                            <i class="fa fa-fw fa-clone"></i>
                        </a>
                        <a class="btn btn-secondary ms-2" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/translation/${data.IdTranslation}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.TRANSLATION}, ${data.IdTranslation})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>`;
                }
            },
        ],
        events: {
            pageChanged() {

                initTooltip();
            },
            completed(data) {
                hideLoader();
            }
        },
    });

}
function renderTranslations() {

    // Get the translations
    get_call(
        BACKEND.TRANSLATION.ALL,
        null,
        function (translations) {

            // Create the table
            renderTable();

            // Init the duplicate select
            translations.forEach(translation => {
                translation.Label = `${translation.Section}.${translation.Page}.${translation.Label}`;
            });

            // Build the pickers
            buildPicker(translations, "#duplicateModal #IdsTranslations", "IdTranslation", "Label");
            buildPicker(translations, "#exportModal #IdsTranslations", "IdTranslation", "Label");
        },
        function () {
            notificationError("Qualcosa è andato storto!");
        }
    );
}
function renderTableErrors(errors) {

    // Show the container
    $('.duplicate_errors').show();

    errors.forEach(error => {
        $('#duplicatesErrors').append(`<code>${error.ExpectedSpl}</code> --> <code>${error.Spl}</code><br>`);
    });

}
function exportTranslations(all = true) {

    // Build the IdsTranslations
    var idsQuery = "";

    // Check if all
    if (!all && checkMandatory("#export_container"))
        idsQuery = `?IdsTranslations=${$("#exportModal #IdsTranslations").val().join(",")}`;

    // Open the new window
    window.open(BACKEND.TRANSLATION.EXPORT.Url + idsQuery, "_blank");

    // Close the modal
    $('#exportModal').modal("hide");

    // Show the success message
    notificationSuccess("Traduzioni esportate con successo!");
}

// Post
function importTranslations() {

    showLoader();

    file_call(
        BACKEND.TRANSLATION.IMPORT,
        {
            Overwrite: $('#overwriteImportCheckbox').prop('checked') ? 1 : 0
        },
        "#importTranslationsFile",
        function (response) {

            $('#importTranslationsFile').val('');
            $('#importChangedTranslations').html('');
            $('#importChangedTranslationsContainer').hide();

            // Check if response is null
            if (response == "" || response == null || response.length == 0)
                $('#importModal').modal("hide");
            else {
                $('#importChangedTranslations').html(response.join("<br>"));

                $('#importChangedTranslationsContainer').show();
            }

            renderTable();

            hideLoader();

        },
        function () {
            hideLoader();

            notificationError("Something went wrong!");
        }
    )
}
function duplicateTranslation(idTranslation) {
    post_call(
        BACKEND.TRANSLATION.DUPLICATE,
        {
            IdTranslation: idTranslation
        },
        function (newIdTranslation) {
            // open new page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/translation/${newIdTranslation}`;
        },
        function () {
            notificationError("Qualcosa è andato storto, riprova!");
        }
    );
}
function duplicateTranslations() {

    // Check mandatory fields
    if (checkMandatory("#duplicate_container")) {

        showLoader();

        // Disable the button
        $('#duplicateTranslations').prop('disabled', true);

        var data = getContentData("#duplicate_container");

        post_call(
            BACKEND.TRANSLATION.DUPLICATE,
            data,
            function (errors) {

                // Refresh the table
                renderTable();

                // Show the errors
                if (errors.length > 0)
                    renderTableErrors(errors);

                // Show the success message
                notificationSuccess("Traduzioni duplicate con successo!");

                // Enable the button
                $('#duplicateTranslations').prop('disabled', false);

                // Hide the modal
                $('#duplicateModal').modal('hide');

                // Clear the container
                clearInputsByContainer("#duplicateModal");

                hideLoader();

            },
            function () {

                // Show the error message
                notificationError("Qualcosa è andato storto, riprova!");

                // Enable the button
                $('#duplicateTranslations').prop('disabled', false);

                hideLoader();
            }
        );
    }

}
function createTranslation() {

    showLoader();

    post_call(
        BACKEND.TRANSLATION.INDEX,
        null,
        function (response) {

            hideLoader();

            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/translation/${response}`;

        },
        function () {
            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )

}

// Utility
function copyPageLabel(section, text, type) {

    // If the section is not WEBSITE or BACKEND add the section to the text
    if (section !== "WEBSITE" && section !== "BACKEND")
        text = `${section}.${text}`;

    // Switch the type
    switch (type) {

        case ENUM.BASE_PROGRAMMING_LANGUAGE.JS:

            copyToClipboard('__t("' + text + '")');
            break;

        case ENUM.BASE_PROGRAMMING_LANGUAGE.PHP:
            copyToClipboard('<?= __t("' + text + '") ?>');
            break;

        default:
            copyToClipboard(text);
            break;
    }
}
function confirmDeeplTranslateAll() {



}

// On open
$("#duplicateModal").on("show.bs.modal", function (e) {
    // Reset the container
    clearInputsByContainer("#duplicateModal");
});

//#region Cache

function createCache() {
    // Create the cache
    post_call(BACKEND.TRANSLATION.CACHE, null, function () { });
}
function deleteCache() {

    // Delete the cache
    delete_call(
        BACKEND.TRANSLATION.CACHE,
        null,
        function () {
            notificationSuccess("Cache cancellata con successo!");
        }
    );

}

//#endregion