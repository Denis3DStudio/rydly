/** DEVELOPER METHODS */

//#region Ajax

function get_call(url, params, successfulCallback, failureCallback, async = true) {

    make_ajax_call({
        type: 'GET',
        async: async,
        url: url,
        data: params,
    }, successfulCallback, failureCallback);

}
function post_call(url, params, successfulCallback, failureCallback, async = true) {

    make_ajax_call({
        type: 'POST',
        async: async,
        url: url,
        data: params ? JSON.stringify(params) : null,
    }, successfulCallback, failureCallback);

}
function put_call(url, params, successfulCallback, failureCallback, async = true) {

    make_ajax_call({
        type: 'PUT',
        async: async,
        url: url,
        data: params ? JSON.stringify(params) : null,
    }, successfulCallback, failureCallback);

}
function delete_call(url, params, successfulCallback, failureCallback, async = true) {

    make_ajax_call({
        type: 'DELETE',
        async: async,
        url: url,
        data: params ? JSON.stringify(params) : null,
    }, successfulCallback, failureCallback);

}
function chunk_call(url, params, input, successfulCallback, failureCallback = null, successfulChunkCallback = null, failureChunkCallback = null) {

    showLoader();

    setTimeout(() => {

        getInputFileCustomRenderChunkQueue(
            input,
            function (identifier, failed_files) {

                // Check if the params is null
                if (params == null)
                    params = {};

                // Add the identifier to the params
                params["Identifier"] = identifier;

                // Call the post call
                post_call(
                    url,
                    params,
                    function (response, message) {

                        hideLoader();

                        // Check if the successful callback is set
                        if (successfulCallback != null)
                            successfulCallback(response, message);

                        // Check if there are failed files
                        if (failed_files.length > 0)
                            notificationWarning("I seguenti file non sono stati caricati: <br>" + failed_files.join(";<br>"));
                        else
                            notificationSuccess("File caricati con successo!");
                    },
                    function (response, message) {

                        hideLoader();

                        // Check if the failure callback is set
                        if (failureCallback != null)
                            failureCallback(response, message);
                    },
                )
            },
            function () {
                hideLoader();

                // Check if the failure callback is set
                if (failureCallback != null)
                    failureCallback();
            },
            successfulChunkCallback,
            failureChunkCallback
        );

    }, 300);
}
function file_call(url, params, input, successfulCallback, failureCallback) {

    getInputFileCustomRenderQueue(input,
        function (files, finished) {

            var formData = new FormData();

            // Insert files
            for (let index = 0; index < files.length; index++) {
                const file = files[index];
                formData.append("Files[]", file);
            }

            // Merge with params
            for (var key in params) {

                var value = params[key];

                // If is null or undefined, set as empty string
                if (value === null || value === undefined)
                    value = "";

                // Check if is an array
                else if (Array.isArray(value))
                    value = `FILE_CALL_ARRAY${value.join("FILE_CALL_SEPARATOR_ARRAY")}`;

                formData.append(key, value);
            }

            var succCall = null;
            var failCall = null;

            // Check if finished and set the right callbacks
            if (finished) {
                succCall = successfulCallback;
                failCall = failureCallback;
            }

            make_ajax_call({
                type: "POST",
                dataType: 'json',
                async: false,
                contentType: false,
                processData: false,
                url: url,
                data: formData,
            }, succCall, failCall, true);

        },
        function () {
            hideLoader();
        });

}

function make_ajax_call(options, successfulCallback, failureCallback, file_upload = false) {

    // Get url
    var url = check_ajax_request_url(options.url, failureCallback);

    // Check if url is valid
    if (url === false) return;

    // Set url
    options.url = url.url;

    // Format request payload form data
    var payload = format_request_form_data(options.data);

    // Is not a file upload
    if (file_upload === false) {

        // Format request payload (using the {...} cause of the reference of the object)
        options.data = { ...format_request_before_send(payload, false) };

        // Set content type
        options.headers = { "Content-Type": 'application/json' };

        // Check if is not a GET request and stringify the data
        if (options.type !== 'GET' && !isNullOrEmpty(options.data))
            options.data = JSON.stringify(options.data);
    }

    // Add headers
    options.headers = {
        "Payload-Validation-Header": create_request_validation_header(format_request_before_send(payload)),
        "Validation-Header-Origin": "EXTERNAL",
        "Payload-Validation-Header-Unix": getUnix(),
        ...options.headers,
        ...url.headers
    }

    $.ajax(options)
        .done(function (data, status) {
            ajax_call_callback(data, successfulCallback, failureCallback);
        })
        .fail(function (data, status) {
            ajax_call_callback(data?.responseJSON, failureCallback, failureCallback);
        });
}

function ajax_call_callback(response, callback, failureCallback, err = null) {

    // Check response length and keys
    if (isEmpty(response) || Object.keys(response).length == 0 || JSON.stringify(Object.keys(response)) !== JSON.stringify(["Response", "Message", "IsGeneric", "Code"])) {

        // Set error message
        err = err ?? "Error!";

        // Check if failure function exists
        if (typeof failureCallback !== 'function') {
            hideLoader();

            notificationError(err);

            return;
        }

        // Call failure callback
        failureCallback(null, err);
        return;
    }
    else {

        // Check if is generic
        if (response.IsGeneric === true && (response.Code < 200 || response.Code > 299)) {

            // Check if exists the generic error translation of the code
            if ($("#generic_error_translation_" + response.Code).length > 0)
                response.Message = $("#generic_error_translation_" + response.Code).val();

            // Check if exists the generic error translation
            else if ($("#generic_error_translation_error_message").length > 0)
                response.Message = $("#generic_error_translation_error_message").val();
        }

        // Everything is fine, call callback
        if (typeof callback === 'function')
            callback(response.Response, response.Message);
        else
            notificationError(response.Message);
    }

}

function format_request_form_data(payload) {

    // Check if is a formData
    if (payload instanceof FormData) {
        var tmp = {};

        // Cycle all keys
        for (var key of payload.keys()) {
            if (key != "Files[]") {
                var value = payload.get(key);

                // Check if is an array from file call
                if (value.lastIndexOf("FILE_CALL_ARRAY") != -1)
                    value = value.replace("FILE_CALL_ARRAY", "").split("FILE_CALL_SEPARATOR_ARRAY");

                // Set
                tmp[key] = value
            }
        }

        payload = tmp;
    }

    return payload;

}
function format_request_before_send(payload, creating_validation_header = true) {

    // Check if payload is null
    if (isNullOrEmpty(payload))
        return null;

    // Check if JSON
    try {
        payload = typeof payload === "string" ? JSON.parse(payload) : payload;
    } catch (e) { }

    for (const key in payload) {
        const value = payload[key];

        // Check if undefined or null or empty string
        if (typeof value === "undefined" || value === null || (typeof value === "string" && isNullOrEmpty(value)))
            payload[key] = null;

        // Check if is an object or an array
        else if (typeof value === "object") {

            // Check if is an empty object or array
            if (Object.keys(value).length === 0 || (Array.isArray(value) && value.length === 0))
                delete payload[key];

            // Format the object or array recursively
            else
                payload[key] = format_request_before_send(value, creating_validation_header);

        }

        // Check if is a number and not a boolean
        else if (!isNaN(value) && typeof value !== "boolean") {

            // Check length (after 16 digits, the number is rounded automatically) - parse only if is creating the validation header
            if (value.toString().length < 16 && creating_validation_header)
                payload[key] = Number(value) === value && value % 1 === 0 ? parseInt(value) : parseFloat(value);
            else
                payload[key] = value.toString();
        }

        // Check string
        else if (typeof value === "string" && creating_validation_header)
            payload[key] = value.replace(/[^\x00-\x7F]/g, "").trim();
    }

    // Check if payload is an empty object
    if (typeof payload === "undefined" || isNullOrEmpty(payload) || Object.keys(payload).length === 0)
        payload = null;

    return payload;
}
function create_request_validation_header(payload) {

    // Check payload
    if (isNullOrEmpty(payload))
        return "";

    var response = JSON.stringify(payload);

    // Cast to SHA256
    response = CryptoJS.SHA256(response).toString(CryptoJS.enc.Hex);

    // Get current unix timestamp + 3 seconds
    const unix = getUnix() + 3;

    // Create header value
    response = encrypt(response, unix);

    return response;

}

function check_ajax_request_url(url, failureCallback) {

    // Check if is a string
    if (typeof url === "string")
        return {
            url: format_ajax_request_url(url),
            headers: {}
        };

    // Check if is an object
    if (typeof url === "object") {

        // Get keys
        var keys = Object.keys(url);

        // Check if the keys are [Url, OverwriteUrl]
        if (keys.length == 2 && keys.includes("Url") && keys.includes("OverwriteUrl")) {
            var merge = {};

            // Check if overwrite url is empty
            if (!isEmpty(url.OverwriteUrl))
                merge = {
                    "External-Overwrite-Url": format_ajax_request_url(url.OverwriteUrl)
                }

            // Return the object
            return {
                url: format_ajax_request_url(url.Url),
                headers: merge
            };

        }

    }

    console.error("The url is not a string or an object with the right keys", url);

    // Build error object
    var response = {
        Response: null,
        Message: null,
        IsGeneric: true,
        Code: 404
    }

    // Call as failure callback
    ajax_call_callback(response, failureCallback, null);

    return false;
}
function format_ajax_request_url(url) {

    // Check if is a complete url
    if (url.includes("http://") || url.includes("https://"))
        return url;

    // Check if has the / at the end
    if (url[url.length - 1] != "/")
        url += "/";

    return url;
}

//#endregion

//#region Errors Check

function checkMandatory(selector = null) {
    var ret = true;
    var mandatory = $((selector == null) ? '[mandatory]' : `${selector} [mandatory]`);

    for (let index = 0; index < mandatory.length; index++) {
        const element = mandatory[index];
        var err = false;
        var errTxt = "";

        var selectpicker = false;

        // SELECT
        if (element.type == "select-one" || element.type == "select-multiple") {
            err = $(element).length == 0 || $(element).val() == null || $(element).val() == "" || typeof $(element).val() == 'undefined';
            selectpicker = $(element).parent(".dropdown.bootstrap-select").length > 0;
            errTxt = $('#error_mandatory_select').val();
        }
        // checkbox
        else if (element.type == "checkbox") {
            err = $(element).prop("checked") ? false : true;
            errTxt = $('#error_mandatory_checkbox').val();
        }
        // Normal INPUT
        else {
            err = ($(element).val().trim().replace(/(<([^>]+)>)/ig, '').length == 0);
            errTxt = $(element).attr('type') == "date" ? $('#error_mandatory_date').val() : $('#error_mandatory_field').val();
        }

        // Get element parent
        var parent = $(element).parent();

        if (err) {
            ret = false;

            if (selectpicker) {
                $(element).closest(".dropdown.bootstrap-select").addClass("is-invalid");
                parent = $(element).closest(".dropdown.bootstrap-select").parent();
            }
            else
                $(element).addClass("is-invalid");

            // Append .invalid-feedback if not exists
            if (parent.find(".invalid-feedback").length == 0)
                parent.append(`<div class='invalid-feedback'>${errTxt}</div>`);

        } else {
            if (selectpicker) {
                $(element).closest(".dropdown.bootstrap-select").removeClass("is-invalid");
                parent = $(element).closest(".dropdown.bootstrap-select").parent();
            }
            else
                $(element).removeClass("is-invalid");

            // Remove .invalid-feedback if exists
            if (parent.find(".invalid-feedback").length > 0)
                parent.find(".invalid-feedback").remove();
        }

    }

    // Check checkFieldsValidation
    if (!checkFieldsValidation())
        ret = false;

    return ret;
}
function checkMandatoryTabs() {

    var validity = true;

    // Cycle all mandatory tabs of the page
    $("[mandatory_tab_content]").each(function (index, element) {

        // Get the id of the element
        var id = $(element).attr("id");

        var tab_id = $(element).attr("href");

        // Check the mandatory fields of the rows
        var tab_valid = checkMandatory(`${tab_id}`);

        // Check if the tab_valid is not valid
        if (tab_valid == false) {

            // Set the validity to false
            validity = false;

            $(`#${id}`).addClass("bg-danger");
        }
        else
            $(`#${id}`).removeClass("bg-danger");

    });

    return validity;
}

function addErrorClass(element, message = null) {
    $(element).addClass("is-invalid");

    if (!isEmpty(message))
        addErrorLabel(element, message);
}
function removeErrorClass(element, remove_label = false) {
    $(element).removeClass("is-invalid");

    if (remove_label)
        removeErrorLabel(element);
}
function addErrorLabel(element, message) {
    $(element).closest(".form-group").append("<div class='invalid-feedback'>" + message + "</div>");
}
function removeErrorLabel(element) {
    $(element).closest(".form-group").find(".invalid-feedback").remove();
}

$(document).ready(renderMandatoryAsterisks);
function renderMandatoryAsterisks() {

    setTimeout(() => {

        var mandatory = $('[mandatory]');

        for (let index = 0; index < mandatory.length; index++) {
            const element = mandatory[index];

            // Get the label
            const labelElement = $(element).closest(".form-group").find('label');

            // Check if the label exists and if it has not the asterisk
            if (labelElement && labelElement.html() !== undefined && !labelElement.html().includes('*'))
                labelElement.html(labelElement.html() + " *");
        }


    }, 500);

}
//#endregion

//#region Tabs check mandatory

function checkInput(inputs, flagDiv) {

    var inputFilled = 0;
    var inputsNeeded = 0;

    inputs.each(function (index, element) {

        // Get the type
        var $element = $(element);
        var elementType = $element.prop('type');
        var checkNeeded = $element.attr('needed') !== undefined;

        if (checkNeeded) {

            inputsNeeded++;

            // Switch all the cases
            switch (elementType) {
                case 'textarea':
                    var textAreaContent = $element.val().replace(/(<([^>]+)>)/gi, "");
                    if (textAreaContent !== '') {
                        inputFilled++;
                    }
                    break;

                case 'checkbox':
                    if ($element.prop('checked')) {
                        inputFilled++;
                    }
                    break;

                default:
                    if ($element.val() !== '') {
                        inputFilled++;
                    }
                    break;
            }

        }

    });

    if (inputFilled != 0) {
        if (inputFilled < inputsNeeded) {
            setMandatory(inputs);
            $(flagDiv).addClass("bg-danger");
        }
    }

    return inputFilled;

}

function setMandatory(inputs) {
    inputs.each(function (index, element) {
        var $element = $(element);

        var checkNeeded = $element.attr('needed') !== undefined;
        if (checkNeeded)
            $element.attr('mandatory', true);

    });

}

function showErrorsInputs(errors, tab) {

    (errors.Duplicates).forEach(function (input) {

        addErrorClass(input.IdInput);

    });

    (errors.Languages).forEach(function (language) {

        var idTab = '#' + tab + '-' + language + '-tab';

        $(idTab).addClass("bg-danger");
    })


}

//#endregion

//#region Date format

function fDate(format, date) {
    // If there's no date, get current
    if (date === undefined) date = new Date();
    else date = rightDateFormat(date);

    // Split to get Dates and Hours
    var formats = format.split(" ");

    // Remove black char
    formats = formats.filter(function (el) {
        return (!isEmpty(el) && el !== undefined);
    });

    var res = [];
    for (let index = 0; index < formats.length; index++) {
        const f = formats[index];

        var sep = "";
        var arr = [];

        // Get each letter
        for (var i = 0; i < f.length; i++) {
            var letter = f.charAt(i);

            var type = getDateTypeValue(letter, date);

            // If is boolean, is not a type but is a special char (the separator)
            if (typeof type === "boolean") sep = letter;
            else arr.push(type);
        }

        res.push(arr.join(sep));
    }

    ret = res.join(' ');
    return ret;
}
function getDateTypeValue(type, date) {
    var d = new Date(date);
    var ret = null;

    switch (type.toUpperCase()) {
        case "Y":
            ret = d.getFullYear();
            break;
        case "M":
            ret = (d.getMonth() + 1).toString();
            if (ret.length < 2) ret = '0' + ret;
            break;
        case "D":
            ret = d.getDate().toString();
            if (ret.length < 2) ret = '0' + ret;
            break;
        case "H":
            ret = d.getHours().toString();
            if (ret.length < 2) ret = '0' + ret;;
            break;
        case "I":
            ret = d.getMinutes().toString();
            if (ret.length < 2) ret = '0' + ret;;
            break;
        case "S":
            ret = d.getSeconds().toString();
            if (ret.length < 2) ret = '0' + ret;;
            break;

        default:
            ret = true;
            break;
    }

    return ret;
}
// Convert every type of date/date and time in Y-m-d
function rightDateFormat(date) {
    if (typeof date === 'string') {
        var exp = date.split(" ");

        var d = exp[0].replace(/\//g, "-").split("-");

        // Check if date has all values
        if (d.length == 1)
            d.push("01");

        if (d.length == 2)
            d.push("01");

        // Check and get lengths
        var lengths = [];
        for (let index = 0; index < d.length; index++) {
            const element = d[index];

            if (element.length < 2)
                d[index] = `0${d[index]}`;

            // Push
            lengths.push(d[index].length);
        }

        // Get if there is the year
        if (lengths.indexOf(4) == -1)
            d[0] = `20${d[0]}`;

        // Merge together
        date = `${d.join("-")} ${exp.length > 1 ? exp[1] : ''}`.trim();
    }

    return date;
}

//#endregion

//#region Notifications

$(document).ready(function () {

    setTimeout(() => {

        // Get querystring params
        const urlParams = new URLSearchParams(window.location.search);

        // Check if exists at least one
        if (urlParams.has('st') == false && urlParams.has('m') == false)
            return;

        // Set default
        var status = "OK";
        var message = null;

        // Check if exists st param
        if (urlParams.get('st')) {
            status = urlParams.get('st');
            urlParams.delete("st");
        }

        // Check if exists m param
        if (urlParams.get('m')) {
            message = urlParams.get('m');
            urlParams.delete("m");
        }

        // Check if success
        if (status.toUpperCase() == "OK")
            notificationSuccess(message);

        // Check if error
        else if (status.toUpperCase() == "KO")
            notificationError(message);

        var new_url_params = [];

        // Rebuild url params
        for (const [key, value] of urlParams.entries())
            new_url_params.push(`${key}=${value}`)

        // Overwrite with the pathname (the url without query)
        window.history.replaceState({}, document.title, window.location.pathname + `?${new_url_params.join("&")}`);

        deleteQuerystringParam();

    }, 500);

});

function notificationSuccess(text = null) {
    text = isEmpty(text) ? "Modifiche salvate" : text;

    toastr.options = {
        closeButton: false,
        debug: false,
        newestOnTop: false,
        progressBar: false,
        positionClass: "toast-bottom-right",
        preventDuplicates: true,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
    };
    toastr.success(text);
}

function notificationWarning(text = null, fixed = false) {
    text = isEmpty(text) ? "Warning" : text;

    toastr.options = {
        closeButton: false,
        debug: false,
        newestOnTop: false,
        progressBar: false,
        positionClass: "toast-bottom-right",
        preventDuplicates: false,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: (!fixed) ? "5000" : 0,
        extendedTimeOut: (!fixed) ? "1000" : 0,
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
        tapToDismiss: fixed,
    };

    toastr.warning(text);
}

function notificationInfo(text = null) {
    text = isEmpty(text) ? "Info" : text;

    toastr.options = {
        closeButton: false,
        debug: false,
        newestOnTop: false,
        progressBar: false,
        positionClass: "toast-bottom-right",
        preventDuplicates: false,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
    };
    toastr.info(text);
}

function notificationError(description = null, title = null) {

    var error_message = $('#generic_error_translation_error').val();
    description = isEmpty(description) ? error_message : description;
    title = isEmpty(title) ? error_message : title;

    toastr.options = {
        closeButton: false,
        debug: false,
        newestOnTop: false,
        progressBar: false,
        positionClass: "toast-bottom-right",
        preventDuplicates: true,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
    };

    // Check that title is not empty
    if (!isEmpty(title))
        toastr.error(description, title);
    else
        toastr.error(description);
}

function documentErrorTitle(title = null) {

    // Get the actual title
    var actual_title = document.title;

    // Check if title is empty
    if (isEmpty(title))
        title = "Errore!";

    // Set the title
    document.title = title;

    // Wait 5 seconds and restore the title
    setTimeout(() => {
        document.title = actual_title;
    }, 3000);
}
function documentSuccessTitle(title = null) {

    // Get the actual title
    var actual_title = document.title;

    // Check if title is empty
    if (isEmpty(title))
        title = "Successo!";

    // Set the title
    document.title = title;

    // Wait 5 seconds and restore the title
    setTimeout(() => {
        document.title = actual_title;
    }, 3000);
}

//#endregion

//#region Forms

function getContentData(selector, useBinary = false) {

    var ret = {};

    // :not(.note-input) = excluding summernote element
    $(`${selector} [name]:not(.note-input)`).each(function () {
        var exclude = false;

        element = this;

        // Check if property with this "name" exists
        if (element != null) {

            nameAttr = $(element).attr("name");
            value = null;

            // CHECKBOX
            if ($(element).is(":checkbox")) {
                var val = $(element).prop("checked");

                value = (useBinary === true) ? (val ? 1 : 0) : val;
            }
            // RADIO
            else if ($(element).is(":radio")) {
                value = $(`${selector} [name="${nameAttr}"]:checked`).val();
            }
            // TEXTAREA WITH SUMMERNOTE
            else if (
                $(element).is("textarea") &&
                $(element).next().hasClass("note-editor")
            ) {
                value = $(element).summernote("code");
            }
            // Normal INPUT/SELECT
            else {

                // Check if not an element inside a summernote
                if ($(element).parents(".note-editor").length == 0)
                    value = $(element).val();
                else
                    exclude = true;

            }

            if (exclude === false)
                ret[nameAttr] = value;
        }
    });

    return ret;
}
function getContentDataByTabs() {

    var response = [];

    // Cycle all mandatory tabs of the page
    $("[mandatory_tab_content]").each(function (index, element) {

        // Get the id of the element
        var id = $(element).attr("id");

        // Get all data from id
        var data = getContentData(`#${id}`);

        // Add the data in the response array
        response[id] = data;
    });

    return response;
}

function fillContentByNames(selector, data, inputs = true) {
    for (var key in data) {
        var value = data[key];
        var element = null;

        if (value == null || value == "null")
            value = "";

        // Get element by name
        if ($(`${selector} [name="${key}"]`).length > 0)
            element = $(`${selector} [name="${key}"]`);

        // By id
        else if ($(`${selector} #${key}`).length > 0)
            element = $(`${selector} #${key}`);

        // Check if property with this "name" exists
        if (element != null) {

            if (inputs == true) {

                // CHECKBOX
                if ($(element).is(":checkbox")) {
                    $(element).prop("checked", value != 0);
                }
                // RADIO
                else if ($(element).is(":radio")) {
                    for (let index = 0; index < element.length; index++) {
                        const radio = element[index];

                        if ($(radio).val() == value)
                            $(radio).prop("checked", true);
                    }
                }
                // TEXTAREA WITH SUMMERNOTE
                else if (
                    $(element).is("textarea") &&
                    $(element).next().hasClass("note-editor")
                ) {
                    $(element).summernote("code", value);
                }
                // Normal INPUT/SELECT
                else {
                    $(element).val(value);
                }
            } else {
                // Set the html in the element
                $(element).html(value);
            }
        }

        // Trigger change event for inputs
        $(`${selector} [trigger_change]`).trigger("change");
    }

    // Check if there are selectpicker
    if (selector && $(selector).find(".selectpicker").length > 0)
        $(selector + ' .selectpicker').selectpicker("refresh");
    else
        $('.selectpicker').selectpicker("refresh");
}
function clearInputsByContainer(container_id, exclude = null) {

    // Clear the inputs by the error class
    $(`${container_id} .is-invalid`).removeClass("is-invalid")

    // Get the data in the container
    var data = getContentData(container_id);

    // Init the exclude array
    if (exclude == null)
        exclude = [];
    else if (!Array.isArray(exclude))
        exclude = [exclude];

    // Cycle the data
    for (var key in data) {

        // Initialize the variable
        var toClean = true;

        // Cycle through all exclude attributes
        exclude.forEach(function (ex) {

            // Check if the input element has the attribute
            if (toClean === false)
                return;

            // Check if the input element has the attribute
            if ($(`${container_id} [name="${key}"]`).is(`[${ex}]`))
                // Set toClean to false
                toClean = false;

        });

        // Clean the data if necessary
        if (toClean)
            data[key] = Array.isArray(data[key]) ? [] : "";
    }

    // Clear the container
    fillContentByNames(container_id, data);
}

function checkMailValidation(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
function checkPhoneValidation(phone) {
    const phoneRegex = /^\+?\d[\d\s]{0,14}$/;
    return phoneRegex.test(phone);
}
function checkFieldsValidation() {
    // Define validation types
    const types = [
        {
            selector: '[is_email]',
            validation: checkMailValidation,
            errorLabel: $('#error_fill_correct_email').val() ?? "Inserire un'email valida",
        },
        {
            selector: '[is_phone]',
            validation: checkPhoneValidation,
            errorLabel: $('#error_fill_correct_phone').val() ?? "Inserire un numero di telefono valido",
        }
    ];

    // Define sets to track tabs with and without errors
    const tabsWithErrors = new Set();
    const tabsWithoutErrors = new Set();

    // Check each type
    types.forEach(({ selector, validation, errorLabel }) => {
        const elements = $(selector);

        // Check each element
        elements.each(function () {
            const element = $(this);
            const value = element.val();

            // Skip if value is empty
            if (value == null || value == "null" || value == "")
                return;

            const isValid = validation(value);

            // Get parent tab-pane and tab ID
            const tabPane = element.closest(".tab-pane");
            const tabId = tabPane.length ? tabPane.attr("id") + "-tab" : null;

            // Remove previous error indicators
            removeErrorLabel(element);
            element.removeClass("is-invalid");

            if (!isValid) {
                element.addClass("is-invalid");
                addErrorLabel(element, errorLabel);
                if (tabId) {
                    tabsWithErrors.add(tabId);
                    tabsWithoutErrors.delete(tabId); // Ensure it's not in the error-free list
                }
            } else if (tabId && !tabsWithErrors.has(tabId)) {
                tabsWithoutErrors.add(tabId);
            }
        });
    });

    // Update tab styles in one go
    tabsWithErrors.forEach(tabId => $(`#${tabId}`).addClass("bg-danger"));
    tabsWithoutErrors.forEach(tabId => {
        if (!tabsWithErrors.has(tabId)) {
            $(`#${tabId}`).removeClass("bg-danger");
        }
    });

    // Return whether there are any errors
    return tabsWithErrors.size === 0;
}

$(document).on("keyup", "[phone]", function () {

    // Build the regex
    const phoneRegex = /^\+?\d[\d\s]{0,14}$/;

    // Check if the phone is valid
    if (!phoneRegex.test($(this).val()))
        // Delete all the invalid characters except spaces
        $(this).val($(this).val().replace(/[^\d\s]/g, "").replace(/^/, "+"));
});

//#endregion

//#region Loader

function showLoader() {
    $('body').addClass("is-loading");
}
function hideLoader() {
    $('body').removeClass("is-loading-full");
    $('body').removeClass("is-loading");
}
function hideFullLoader() {
    $('body').removeClass("is-loading-full");
}

//#endregion

//#region Tooltip

$(document).ready(initTooltip);

function initTooltip() {

    // Wait because some elements are not initialized yet
    setTimeout(() => {

        // Check if there are custom tooltip
        if ($("[tooltip]").length > 0) {

            var elements = $("[tooltip]");

            for (let index = 0; index < elements.length; index++) {
                const element = elements[index];

                // Get tooltip text
                var text = $(element).attr("tooltip");

                // Remove custom
                $(element).removeAttr("tooltip");

                // Add default
                $(element).attr("data-bs-toggle", "tooltip");
                $(element).attr("data-bs-placement", "top");
                $(element).attr("data-bs-html", "true");
                $(element).attr("title", text);
            }

        }

        if ($("[data-bs-toggle='tooltip']").length > 0)
            $('[data-bs-toggle="tooltip"]').tooltip();
    }, 1000);
}

//#endregion

//#region Summernote

function initSummernote(selector) {

    if ($(selector).length == 0)
        return;

    setTimeout(() => {

        // Init summernote
        $(selector).summernote({
            lang: "it-IT",
            placeholder: "Testo",
            disableDragAndDrop: true,
            height: 300,
            toolbar: [
                ["style", ["", "bold", "italic", "underline", "clear"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["height", ["height"]],
                ['style', ['style']],
                ["insert", ["picture"]],
                ["link"],
                ["codeview", ["codeview"]],
            ],
            styleTags: ['h2', 'h3', 'h4'],
            callbacks: {
                onPaste: function (e) {
                    e.preventDefault();

                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData("text/html");

                    if (bufferText != "")
                        document.execCommand("insertHtml", false, summernoteCleanPastedHTML(bufferText));

                    else {
                        bufferText = (
                            (e.originalEvent || e).clipboardData || window.clipboardData
                        ).getData("text/plain");

                        document.execCommand("insertHtml", false, summernoteCleanPastedHTML(bufferText));
                    }

                }
            },

        });

        window.onclick = function (e) {
            // Check if the element clicked IS NOT a child of a Summernote
            if ($(e.target).parents('.note-editor').length == 0) {

                // Clear the code
                $('.js-editor').each(function (index) {
                    $(this).summernote('code', summernoteCleanPastedHTML($(this).summernote('code')));
                });

            }
        }

        setTimeout(() => {
            let buttons = $('.note-editor button[data-toggle="dropdown"]');

            buttons.each((key, value) => {
                $(value).on('click', function (e) {
                    $(this).attr('data-bs-toggle', 'dropdown')
                    console.log()
                    ata('id', 'dropdownMenu');
                })
            })
        }, 100);

    }, 500);

}

function summernoteCleanPastedHTML(input) {

    // 1. remove line breaks / Mso classes
    var stringStripper = /(\n|\r| class=(")?Mso[a-zA-Z]+(")?)/g;
    var output_no_breaks = input.replace(stringStripper, ' ');

    // 2. strip Word generated HTML comments
    var commentSripper = new RegExp('<!--(.*?)-->', 'g');
    output = output_no_breaks.replace(commentSripper, '');

    // Check if Word text
    if (output_no_breaks != output) {

        // 3. remove tags leave content if any
        var tagStripper = new RegExp('<(/)*(meta|link|span|xmlns:|\\?xml:|st1:|o:|html|head|body|lang|font)(.*?)>', 'gi');
        output = output.replace(tagStripper, '');

        // 4. Remove everything in between and including tags '<style(.)style(.)>'
        var badTags = ['style', 'script', 'applet', 'embed', 'noframes', 'noscript', 'xmlns'];

        for (var i = 0; i < badTags.length; i++) {
            tagStripper = new RegExp('<' + badTags[i] + '.*?' + badTags[i] + '(.*?)>', 'gi');
            output = output.replace(tagStripper, '');
        }

        // 5. remove attributes ' style="..."'
        var badAttributes = ['style', 'start', 'class', 'xmlns', 'data-(.*?)', 'dir'];
        for (var i = 0; i < badAttributes.length; i++) {
            var attributeStripper = new RegExp(' ' + badAttributes[i] + '="(.*?)"', 'gi');
            output = output.replace(attributeStripper, '');
        }

    }

    return output.trim();
}

//#endregion

//#region Selectpicker

function initSelectpicker(selector) {

    if ($(selector).length > 0)
        $(selector).selectpicker("refresh");

}

function buildPicker(list, picker, valueName, textName, selected = null, custom = '') {

    // Init the options
    var options = [];

    // Check if there is a custom option to insert
    if (custom !== '')
        // Add the custom option
        options.push(`<option value="-1">${custom}</option>`);

    // Cycle all the list if exists
    var list_options = !isEmpty(list) && list.length > 0 ? list.map(x => `<option value="${x[valueName]}">${x[textName]}</option>`) : [];

    // Merge the options
    options = [...options, ...list_options];

    // Set the options in the picker
    $(picker).html(options.join(""));

    var is_select_picker = $(picker).hasClass("selectpicker");

    // Refresh the picker
    if (is_select_picker)
        $(picker).selectpicker("refresh");

    // Check if there is a selected value
    if (selected != null) {

        // Set the selected value
        $(picker).val(selected);

        // Refresh the picker
        if (is_select_picker)
            $(picker).selectpicker("refresh");
    }
}

//#endregion

//#region Encrypt

function encrypt(obj, key) {

    var CryptoJSAesJson = {
        stringify: function (cipherParams) {
            var j = { ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64) };
            if (cipherParams.iv) j.iv = cipherParams.iv.toString();
            if (cipherParams.salt) j.s = cipherParams.salt.toString();
            return JSON.stringify(j);
        },
        parse: function (jsonStr) {
            var j = JSON.parse(jsonStr);
            var cipherParams = CryptoJS.lib.CipherParams.create({ ciphertext: CryptoJS.enc.Base64.parse(j.ct) });
            if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv)
            if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s)
            return cipherParams;
        }
    }

    // Check if is not a string
    if (typeof obj !== "string")
        obj = JSON.stringify(obj);

    // Check if is not a string
    if (typeof key !== "string")
        key = JSON.stringify(key);

    return btoa(CryptoJS.AES.encrypt(obj, key, { format: CryptoJSAesJson }).toString());
}

//#endregion

//#region Modal

var old_visible_modals = [];
$(document).on("show.bs.modal", ".modal.fade", function () {
    // Get the modal
    var newModal = $(this);

    // Select all the visible modals except the new one
    var visibleModals = $(".modal.show").not(newModal);

    // If there are some visible modals
    if (visibleModals.length) {

        // Save the visible modals
        old_visible_modals = visibleModals;

        // Hide the visible modals
        visibleModals.modal('hide');
    }
});
$(document).on("hidden.bs.modal", ".modal.fade", function () {

    // If there are no modals visible and there are some saved
    if ($(".modal.show").length === 0 && old_visible_modals.length > 0) {

        // Show the hided modals
        old_visible_modals.modal('show');

        // Reset the saved modals
        old_visible_modals = [];
    }
});

//#endregion

//#region Others

function confirmDeleteModal(confCallback, cancelCallback = null, text = null, text_is_html = false) {
    if (!isEmpty(text)) {

        if (text_is_html)
            $('#modalConfirmDeleteText').html(text);
        else
            $('#modalConfirmDeleteText').text(text);
    }

    $("#modalConfirmDelete").modal("show");

    $("#confirmDeleteBtn")
        .off("click")
        .on("click", function () {
            $("#modalConfirmDelete").modal("hide");
            confCallback();
        });
    $("#cancelDeleteBtn")
        .off("click")
        .on("click", function () {
            $("#modalConfirmDelete").modal("hide");
            if (cancelCallback != null) cancelCallback();
        });
}
function confirmGenericModal(confCallback, cancelCallback = null, text = null, text_is_html = false) {
    if (!isEmpty(text)) {

        if (text_is_html)
            $('#modalConfirmGenericText').html(text);
        else
            $('#modalConfirmGenericText').text(text);
    }

    $('#modalConfirmGeneric').modal("show");

    $('#confirmGenericBtn').off("click").on("click", function () {
        $('#modalConfirmGeneric').modal("hide");
        confCallback();
    });
    $('#cancelGenericBtn').off("click").on("click", function () {
        $('#modalConfirmGeneric').modal("hide");
        if (cancelCallback != null) cancelCallback();
    });
}
function confirmGenericModalWithSubtitle(confCallback, cancelCallback = null, title = null, subtitle = null, title_is_html = false, subtitle_is_html = false) {

    if (!isEmpty(title)) {

        if (title_is_html)
            $('#modalConfirmGenericSubTitle').html(title);
        else
            $('#modalConfirmGenericSubTitle').text(title);
    }

    if (!isEmpty(title)) {

        if (subtitle_is_html)
            $('#modalConfirmGenericSubSubtitle').html(subtitle);
        else
            $('#modalConfirmGenericSubSubtitle').text(subtitle);
    }

    $('#modalConfirmGenericSub').modal("show");

    $('#confirmGenericSubBtn').off("click").on("click", function () {
        $('#modalConfirmGenericSub').modal("hide");
        confCallback();
    });
    $('#cancelGenericSubBtn').off("click").on("click", function () {
        $('#modalConfirmGenericSub').modal("hide");
        if (cancelCallback != null) cancelCallback();
    });
}

function hasSubstring(string, substring) {
    return (string.includes(substring));
}
function addElementToSerialized(serialized, key, value) {
    var toPush = { name: key, value: value };
    serialized.push(toPush);

    return serialized;
}
function isEmptyId(id) {
    return $("#" + id).val() == "" || $("#" + id).val() == null;
}
function isEmpty(string) {
    return string == "" || string == null || string == undefined;
}

function copyToClipboard(text) {

    navigator.clipboard.writeText(text).then(function () {
        notificationSuccess("Copiato");
    });

}

function isNullOrEmpty() {

    const isNullOrEmpty = [];

    // Get args
    const toCheck = Array.from(arguments);

    // Check if array
    if (!Array.isArray(toCheck)) toCheck = [toCheck];

    // Check fields
    toCheck.forEach((field) => {
        if (field === null)
            isNullOrEmpty.push(true);

        else if (typeof field === 'number' && !isNaN(field))
            isNullOrEmpty.push(false);

        else if (typeof field === 'string') {
            const check = field.replace(/(<([^>]+)>)/gi, '').replace(/\s/g, '');
            isNullOrEmpty.push(check === null || check === '');
        }

        else if (Array.isArray(field))
            isNullOrEmpty.push(field.length === 0);

        else if (typeof field === 'object')
            isNullOrEmpty.push(Object.keys(field).length === 0);

        else if (typeof field === 'boolean')
            isNullOrEmpty.push(false);

        else
            isNullOrEmpty.push(false);

    });

    // If even just one element is empty
    return isNullOrEmpty.includes(true);

}

function getUnix() {
    return Math.floor(new Date().getTime() / 1000);
}

function scrollToAnchor(element, minus_value = 0) {

    $('html,body').animate({ scrollTop: $(element).offset().top - minus_value }, 'slow');
}

$(document).ready(function () {

    // Get querystring params
    const urlParams = new URLSearchParams(window.location.search);

    // No params but url has the ? at the end
    if (urlParams.size == 0 && window.location.href[window.location.href.length - 1] == "?")
        deleteQuerystringParam();

});

function deleteQuerystringParam() {
    window.history.replaceState({}, document.title, window.location.pathname);
}

$(document).on("keyup", "[maxlength].counting_missing_characters", function () {

    // Get the max length of the input
    var max_length = $(this).attr("maxlength");

    // Get the text length of the input
    var text_length = $(this).val().length;

    // Calculate the missing characters
    var missing_characters = max_length - text_length;

    // Check if the input has a label
    if ($(this).parents(".form-group").find("label").length > 0) {

        $(this).parents(".form-group").find("label span[missing_characters]").remove();

        // Get the text
        var text = $(this).parents(".form-group").find("label").html();

        // Add the remaining
        text = `${text} <span missing_characters><small> - Caratteri Mancanti: ${missing_characters}</small></span>`;

        $(this).parents(".form-group").find("label").html(text);
    }

});

//#endregion

//#region Fix max/min values

$(document).on("focusout", "[type='number']", function () {

    var max = $(this).attr('max');
    // check if the element has a max
    if (typeof max !== 'undefined' && max !== false) {

        max = parseInt(max);

        // get value
        value = parseInt($(this).val());
        // check if value is greater than max
        if (value > max)
            $(this).val(max); // set max as value
    }

});

// check min of an element type number
$(document).on("focusout", "[type='number']", function () {

    var min = $(this).attr('min');
    // check if the element has a min
    if (typeof min !== 'undefined' && min !== false) {

        min = parseInt(min);

        // get value
        value = parseInt($(this).val());
        // check if value is greater than min
        if (value < min)
            $(this).val(min); // set min as value
    }

});

//#endregion