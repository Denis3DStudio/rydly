$(document).ready(function () {
    
    getLanguagesTabs(
        "language_caption_image_tab_template",
        true,
        "caption-image-nav-tab",
        "caption-image-nav-tabContent",
        "language_caption_image_tab_nav_template"
    );
});

// Get
function getContentCaptionsTranslations(url, params, callback = null) {

    get_call(
        url,
        params,
        function (response) {

            // Set the first
            activeFirstCaptionTab();

            // Set the op-5 class to all the nav items
            $(".nav-item[id^='image-caption-tabLang-']").addClass("op-5");
            // Clear the caption inputs
            $("[id^='image-caption-tabContent-'] [name='Caption']").val("");

            // Set the content ref id
            fillContentByNames("#modalCaptionBody", response);

            // Cycle all languages
            response.Languages.forEach(news_language => {

                // Remove the opacity class from the nav flas
                $(`#image-caption-tabLang-${news_language.IdLanguage}-tab`).removeClass("op-5");
                // Insert the data in the inputs
                fillContentByNames(`#image-caption-tabLang-${news_language.IdLanguage}`, news_language);
            });

            // Callback
            if (callback != null)
                callback();

            // Show the modal
            $("#modalCaption").modal("show");
        }
    )
}
// Put
function saveContentCaptionTranslations(url, params, callback = null) {

    // Set the array of the languages
    params.Languages = [];

    // Get the captions
    $(".tab-pane[id^='image-caption-tabLang-']").each(function (index, element) {

        // Push the language
        params.Languages.push({
            IdLanguage: $(element).attr("language"),
            Caption: $(element).find("[name='Caption']").val()
        });
    })

    put_call(
        url,
        params,
        function () {

            // Hide the modal
            $("#modalCaption").modal("hide");

            // Callback
            if (callback != null)
                callback();

            // Show the success notification
            notificationSuccess("Didascalia salvata con successo");
        }
    )
}

// Render
function renderCaptionTemplates() {
    
    // Nav
    var template = new AC_Template();
    template.setTemplateId('language_caption_image_tab_nav_template')
        .setContainerId('caption-image-nav-tab')
        .setObjects(global.Languages)
        .setPrepend(true)
        .renderView();

    // Content
    var template = new AC_Template();
    template.setTemplateId('language_caption_image_tab_template')
        .setContainerId('caption-image-nav-tabContent')
        .setObjects(global.Languages)
        .setPrepend(true)
        .renderView();

    // Show first
    activeFirstCaptionTab();
}
function activeFirstCaptionTab() {

    // Remove the active class
    $('.nav-item[id^="image-caption-tabLang-"]').removeClass("active");
    $('[id^="image-caption-tabLang-"]').removeClass("active").removeClass("show");

    // Get the first language
    var language = global.Languages[0].Language;

    // Set the active class
    $(`#image-caption-tabLang-${language}-tab`).addClass("active");
    $(`#image-caption-tabLang-${language}`).addClass("active").addClass("show");
}