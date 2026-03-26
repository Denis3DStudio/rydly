var languagesToBackup = [];
$(document).on('change', "[name='LanguagesTo']", function () {

    // Get the languages
    var selectedLanguages = $(this).val();

    // Get the diffrence between the two arrays
    var diff = selectedLanguages.filter(x => !languagesToBackup.includes(x));

    // Check if diff has "-1"
    if (diff.includes("-1")) {

        // Get the fields to translate
        $(this).val("-1").selectpicker("refresh");

        // Close and open the picker
        $(this).selectpicker("toggle").selectpicker("toggle");

        // Set the backup
        languagesToBackup = ["-1"];

    } else if (selectedLanguages.length != 1 && !diff.includes("-1")) {

        // Delete the -1
        $(this).val(selectedLanguages.filter(x => x != "-1")).selectpicker("refresh");

        // Close and open the picker
        $(this).selectpicker("toggle").selectpicker("toggle");

        // Set the backup
        languagesToBackup = selectedLanguages.filter(x => x != "-1");
    }
});
// Init
function renderDeepl(isTranslationPage = false, translateAll = false) {

    // Check if to translate all
    if (translateAll) {

        // Hide fields selections
        $('#select_translations_container').hide();

        // Hide button
        $('#btnConfirmDeepl').hide();

        // Overwrite btnConfirmDeeplSave onclick with translateAll
        $('#btnConfirmDeeplSave').attr('onclick', 'translateAll()');
    }

    // Build the languages pickers
    buildPicker(global.Languages, "[name='LanguageFrom']", "Language", "LanguageName", Logged.IdLanguage);
    buildPicker(global.Languages, "[name='LanguagesTo']", "Language", "LanguageName", "-1", "Tutte");

    // Trigger the change
    $("[name='LanguagesTo']").trigger("change");

    // Get the fields to translate
    getFieldsToTranslate(`#${getIdTabToTranslate(true)}`);

    // If is translation page
    if (!isTranslationPage && !translateAll) {

        // Hide the confirm button
        $('#btnConfirmDeeplSave').hide();

        // Delte the class btn-outline-secondary and add btn-success
        $('#btnConfirmDeepl').removeClass("btn-outline-secondary").addClass("btn-success");
    }
}

// Post
function getTranslationDeepl(save = false) {

    // Check if to check mandatory
    if (save ? checkMandatory("[deepl_container]") : true) {

        showLoader();

        // Build the data
        var data = getContentData("#languages_container");
        data.ToTranslate = [];

        // Get the content from the tab
        var allFieldsToTranslate = getCleanToTranslate(data.LanguageFrom);

        // Cicle the to allFieldsToTranslate
        Object.keys(allFieldsToTranslate).forEach(key => {
            // Push the object
            data.ToTranslate.push({
                Name: key,
                Text: allFieldsToTranslate[key],
                TextFormat: $(`[name='${key}']`).attr("deepl_text_format") ?? ENUM.BASE_TEXT_FORMAT.NORMAL
            });
        });

        // Delete the object that are not to translate
        var toTranslate = $("[name='ToTranslate']").val();

        // Delete all the fields that are not to translate
        if (toTranslate.length > 0)
            data.ToTranslate = data.ToTranslate.filter(x => toTranslate.includes(x.Name));

        post_call(
            BACKEND.TRANSLATION.DEEPL,
            data,
            function (translations) {

                hideLoader();

                // Cicle all the languages
                translations.forEach(language => {

                    // Fill the form with new translations
                    if (!isEmpty(language))
                        fillContentByNames(`#${getIdTabToTranslate()}-${language.Language}`, language.Translations);

                });

                setTimeout(() => {

                    // Check if to save the translations
                    if (save)
                        saveTranslation();

                    // Hide the modal 
                    $("#modalConfirmTranslations").modal("hide");
                    notificationSuccess("Traduzione avvenuta con successo!!");

                }, 500);


            },
            function () {
                hideLoader();

                notificationError("Traduzione non salvata. Potrebbe già esistere.")
            }
        )

    } else if (save) {

        // Show the error
        notificationError("Traduzione non salvata. Controllare i campi obbligatori.");

        // Hide the modal
        $("#modalConfirmTranslations").modal("hide");
    }

    return false;
}
function translateAll() {

    // Show the loader
    showLoader();

    // Backup the translations
    exportTranslations();

    // Get the data
    var data = getContentData("#languages_container");

    // Call the API
    post_call(
        BACKEND.TRANSLATION.TRANSLATEALL,
        data,
        function () {

            // Hide the loader
            hideLoader();

            // Hide the modal
            $("#modalConfirmTranslations").modal("hide");

            // Notification
            notificationSuccess("Traduzione avvenuta con successo!!");
        },
        function (error, message) {

            // Notification
            notificationError("Errore nella traduzione.");
        }
    );
}

//#region Utils

function getFieldsToTranslate(container) {

    // Show all the to_translate
    var to_translate = $(`${container} [deepl_to_translate]`);

    // Init the names array
    var names = [];

    // Cicle all the to_translate and log their names
    to_translate.each(function (index, element) {
        // Push 
        names.push(
            {
                Name: $(element).attr("name"),
                Text: $(element).closest(".form-group").find("label").first().text().replace('*', '')
            }
        );
    });

    // Build the picker
    buildPicker(names, "[name='ToTranslate']", "Name", "Text");

    // Fill the ToTranslate picker
    $("#ToTranslate").selectpicker("refresh");

    // Check if the deepl is enabled
    $("#deepl_container").show();
}
function getIdTabToTranslate(withId = false) {
    return `${$("[tabToTranslate]").first().attr("name") + (withId ? ('-' + global.Languages[0].Language) : "")}`;
}
function getCleanToTranslate(language) {

    var data = getContentData(`#${getIdTabToTranslate()}-${language}`);

    // Cicle all and delete the ones that are not to translate
    Object.keys(data).forEach(key => {
        if ($(`[name='${key}']`).attr("deepl_to_translate") === undefined)
            delete data[key];
    });

    return data;

}

//#endregion