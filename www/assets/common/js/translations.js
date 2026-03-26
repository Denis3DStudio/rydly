var global_translations = {
    translations: null
};

function __t(label) {

    // Format the label
    label = label.toUpperCase().trim().replaceAll(' ', '_');

    // Check if the label has the format (SECTION.PAGE.LABEL)
    var split = label.split('.');

    if (split.length == 2) {

        // Get default section
        var default_section = $("#active_type_platform").val() === 'frontend' ? 'WEBSITE' : 'BACKEND'

        // Add to label
        label = default_section + '.' + label;

        // Add the default section
        split.unshift(default_section);
    }

    // Get the arguments as an array and remove the first element
    var args = Array.prototype.slice.call(arguments).slice(1);

    // Get section, page and label
    var l_section = split[0];
    var l_page = split[1];
    var l_label = split[2];

    // Check
    if(l_section in TRANSLATIONS && l_page in TRANSLATIONS[l_section] && l_label in TRANSLATIONS[l_section][l_page]) {

        // Get the translation
        var translation = TRANSLATIONS[l_section][l_page][l_label];

        // Replace what is into the ( ) with *
        translation = translation.replace(/\/\(.*?\)/g, '/(*)');

        // Check if args is not empty
        if (args.length > 0) {

            // Replace the (*) with the args
            args.forEach(function (arg) {
                translation = translation.replace("(*)", arg);
            });
        }

        // Return the translation without the (*)
        return translation.replaceAll('(*)', '');
    }
    else
        return `<mark><del>${label}</del></mark>`;
}