var input_file_custom_render_queue = {};
var current_custom_input_file = null;
var input_file_custom_render_is_deleting = false;

var translations = {
    h4:
        $('[__t="ac_file_h4_translation"]').length > 0
            ? $('[__t="ac_file_h4_translation"]').val()
            : "Premi o trascina<br> qui i file",
    span:
        $('[__t="ac_file_span_translation"]').length > 0
            ? $('[__t="ac_file_span_translation"]').val()
            : "Nessun file selezionato",
    confirm:
        $('[__t="ac_file_confirm_translation"]').length > 0
            ? $('[__t="ac_file_confirm_translation"]').val()
            : "Conferma",
};

$(document).ready(function () {
    initFileCustomRender();
});

function initFileCustomRender() {
    // Check if exists file and is not disabled the custom render
    var inputs = $(".custom-render[type='file']");
    for (let index = 0; index < inputs.length; index++) {
        current_custom_input_file = inputs[index];

        // Render
        createInputFileCustomRender();
    }
}

function createInputFileCustomRender() {
    // Get onclick action from input
    var onclick = $(current_custom_input_file).attr("onclick");
    // Get callback from input
    var callback = $(current_custom_input_file).attr("callback");

    // Check if onlick action exists
    var button = !isEmpty(onclick)
        ? getInputFileCustomRenderButtonTemplate(onclick, callback, $(current_custom_input_file))
        : "";

    // Create hidden input file
    var hidden_input = getInputFileCustomRenderHidden(
        $(current_custom_input_file).attr("id"),
        $(current_custom_input_file).attr("name"),
        $(current_custom_input_file).attr("multiple"),
        $(current_custom_input_file).attr("accept"),
        $(current_custom_input_file).attr("fileMacro"),
        $(current_custom_input_file).attr("fileType")
        // $(current_custom_input_file).attr("accept")
    );

    // Create container
    var custom_render = getInputFileCustomRenderTemplate(button, hidden_input);

    // Add hidden file input after the original
    $(current_custom_input_file).after(custom_render);

    // Remove the original
    $(current_custom_input_file).remove();

    // Init actions
    initInputFileCustomRenderActions();
}
function initInputFileCustomRenderActions() {
    var name = $(current_custom_input_file).attr("name");

    // File select
    $(`input[type="file"][name="${name}"]`).on(
        "change",
        inputFileCustomRenderSelectHandler
    );

    // Click start file browse
    $(`[custom_render="${name}"] label`).on("click", function () {
        if (!input_file_custom_render_is_deleting)
            $(`input[type="file"][name="${name}"]`).click();
        else input_file_custom_render_is_deleting = false;
    });

    // Check if XHR is available
    var xhr = new XMLHttpRequest();
    if (xhr.upload) {
        $(`[custom_render="${name}"] label`).on(
            "drop",
            inputFileCustomRenderSelectHandler
        );
        $(`[custom_render="${name}"] label`).on("dragleave", function (e) {
            e.stopPropagation();
            e.preventDefault();
        });
        $(`[custom_render="${name}"] label`).on("dragover", function (e) {
            e.stopPropagation();
            e.preventDefault();

            $(`[custom_render="${name}"]`).addClass("is-dropping");
        });

        // If at least one file is dropped, remove the class
        $(`[custom_render="${name}"] label`).on("drop", function (e) {
            e.stopPropagation();
            e.preventDefault();

            $(`[custom_render="${name}"]`).removeClass("is-dropping");
        });
    }
}
function inputFileCustomRenderSelectHandler(e) {
    e.stopPropagation();
    e.preventDefault();

    var files = [];

    // Get uploaded or dragged files
    if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length)
        files = e.originalEvent.dataTransfer.files;
    else files = $(e.target).prop("files");

    // Get custom name class
    var name = $(e.target).closest(`div[custom_render]`).attr("custom_render");

    var validity = checkInputFileCustomRenderFilesValidity(name, files);
    files = validity.Files;

    if (validity.Valid) {
        var files_list = files;

        // Merge files
        if (validity.AllowMultiple)
            files_list = mergeInputFileCustomRenderQueue(name, files);

        // Set the queue
        input_file_custom_render_queue[name] = files_list;

        // Show names
        showInputFileCustomRenderInseredFiles(name);
    } else showInputFileCustomRenderError(name);
}

function getInputFileCustomRenderTemplate(button, hidden) {
    var name = $(current_custom_input_file).attr("name");

    // check if has the class "is-horizontal"
    var is_horizontal = $(current_custom_input_file).hasClass("is-horizontal")
        ? "is-horizontal"
        : "";

    return `
        <div class="card card-custom-file-block ${is_horizontal}" custom_render="${name}">
            <div class="card-body">
                ${hidden}
                <label>
                    <div class="custom-file-block-content">
                        <i class="fa fa-fw fa-file-upload"></i>
                        <h4>${translations.h4}</h4>
                        <span class="file-container" file_name_container>${translations.span}</span>
                    </div>
                </label>
                ${button}
            </div>
        </div>
    `;
}
function getInputFileCustomRenderButtonTemplate(onclick, callback, extras) {

    // Add the extra parameters (fileUpload fileMacro fileType)
    var extra = [];
    if (!isEmpty(extras.attr("fileUpload")))
        extra.push(`fileUpload: '${extras.attr("fileUpload")}'`);
    if (!isEmpty(extras.attr("fileMacro")))
        extra.push(`fileMacro: '${extras.attr("fileMacro")}'`);
    if (!isEmpty(extras.attr("fileType")))
        extra.push(`fileType: '${extras.attr("fileType")}'`);

    extra = extra.join(" ").replace(/'/g, '"').replace(/:/g, "=").replace(/ /g, "") + " fileUpload";

    // Add the name of the file
    extra = `nameInput="${extras.attr("name")}" ${extra}`;

    return `

        <button class="btn btn-outline-success" type="button" onclick="${onclick}" callback="${callback}" ${extra}>
            <i class="fa fa-fw fa-upload"></i> ${translations.confirm}
        </button>

    `;
}
function getInputFileCustomRenderHidden(id, name, multiple, accept) {
    id = id == undefined ? "" : id;
    name = name == undefined ? "" : name;
    multiple = multiple == undefined ? "" : multiple;

    return `<input type="file" id="${id}" name="${name}" ${multiple} accept="${accept}" class="hidden" hidden/>`;
}

function mergeInputFileCustomRenderQueue(name, files) {
    var used = [];

    // Get the value of the multiple attribe
    var multiple = $("[name='immagine_profilo']").attr("multiple");

    // Get already stored files
    var stored = input_file_custom_render_queue[name];
    if (stored != undefined && multiple == true) {
        for (let index = 0; index < stored.length; index++) {
            const element = stored[index];

            used.push(element.name + " - " + element.size);
        }
    } else {
        stored = [];
    }

    // Merge objects
    for (let index = 0; index < files.length; index++) {
        const file = files[index];

        // Check if not already in the used
        var key = file.name + " - " + file.size;

        if (used.indexOf(key) == -1) {
            stored.push(file);

            // Add to the used
            used.push(key);
        }
    }

    return stored;
}
function getInputFileCustomRenderQueue(
    element,
    successfulCallback,
    failureCallback
) {
    var ret = [];

    // Get input render custom attribute
    var name = $(element).attr("name");

    // Check if in the queue by name
    if (name in input_file_custom_render_queue)
        ret = input_file_custom_render_queue[name];

    // Get from files
    if (ret == undefined || ret.length == 0) ret = [...$(element).prop("files")];

    // Delete
    deleteInputFileCustomRenderQueue(name);

    // Check if there are some files
    if (ret.length > 0) {
        setTimeout(() => {
            // Splice in chunk
            const chunkSize = 5;

            // Calculate the number of loop
            const loop_number = Math.ceil(ret.length / chunkSize);

            for (let i = 0; i < loop_number; i += 1) {
                const chunk = ret.slice(i * chunkSize, i * chunkSize + chunkSize);

                // Call callback
                successfulCallback(chunk, loop_number - 1 == i);
            }
        }, 100);
    } else {
        showInputFileCustomRenderError(name);
        failureCallback();
    }
}
function deleteInputFileCustomRenderQueue(name) {
    // Check if in the queue by name
    if (name in input_file_custom_render_queue)
        input_file_custom_render_queue[name] = [];

    $(`input[type="file"][name="${name}"]`).val("");
    $(`[custom_render="${name}"] span[file_name_container]`).html(
        translations.span
    );
}
function removeInputFileCustomRenderFromQueue(
    input_file_name,
    block_file_name
) {
    // Check if in the queue by name
    if (input_file_name in input_file_custom_render_queue) {
        input_file_custom_render_is_deleting = true;

        // Filter the input_file_custom_render_queue
        input_file_custom_render_queue[input_file_name] =
            input_file_custom_render_queue[input_file_name].filter(function (
                element
            ) {
                if (element.name != block_file_name) return element;
            });

        // Remove the element
        $(`.block-file-name[file_name='${block_file_name}']`).remove();

        // Check if the array is empty
        if (input_file_custom_render_queue[input_file_name].length == 0)
            $(`[custom_render="${input_file_name}"] span[file_name_container]`).html(
                translations.span
            );
    }
}
function checkInputFileCustomRenderFilesValidity(name, files) {
    var ret = true;
    var allow_multiple = true;

    // Get input file
    var input_file = $(`input[type="file"][name="${name}"]`);

    // Check file number
    if ($(input_file).attr("multiple") == undefined && files.length > 1) {
        // Use only the first one
        files = [files[0]];
        allow_multiple = false;
    }

    // Get accepted formats
    var accepted = $(input_file).attr("accept");

    if (accepted != undefined && accepted !== "undefined") {
        // Get accepted types
        accepted = accepted.split(",");

        // Trim the accepted values
        for (let index = 0; index < accepted.length; index++) {
            accepted[index] = accepted[index].trim();
        }

        // Check if loaded files are valid
        for (let index = 0; index < files.length; index++) {
            const file = files[index];

            // Check if accepted
            if (accepted.indexOf(file.type) == -1) {
                // Check changing the last part with the *
                var type = file.type.split("/")[0] + "/*";

                if (accepted.indexOf(type) == -1) {
                    ret = false;
                    break;
                }
            }
        }
    }

    return {
        Valid: ret,
        Files: files,
        AllowMultiple: allow_multiple,
    };
}

function showInputFileCustomRenderInseredFiles(name) {
    var names = "";

    // Get names
    var stored = input_file_custom_render_queue[name];
    if (stored != undefined) {
        for (let index = 0; index < stored.length; index++) {
            const element = stored[index];

            names +=
                `<div class="block-file-name" file_name="${element.name}">` +
                element.name +
                ` <span class="action-remove" onclick="removeInputFileCustomRenderFromQueue('${name}', '${element.name}')">&times;</span></div>`;
        }
    } else {
        names = translations.span;
    }

    // Show names
    $(`[custom_render="${name}"] span[file_name_container]`).html(names);
}
function showInputFileCustomRenderError(name) {
    // Add class error
    $(`[custom_render="${name}"]`).addClass("has-error");

    // Remove after 2 second
    setTimeout(function () {
        $(`[custom_render="${name}"]`).removeClass("has-error");
    }, 2000);
}

//#region Chunk upload

var chunk_upload_url = (ENUM.BASE_PATH.API.toLocaleUpperCase() == "/BACKEND") ? eval(ENUM.BASE_PATH.API.replaceAll("/", "").toLocaleUpperCase()).CHUNK.INDEX.Url : null;
var chunk_upload_success = true;
var chunk_upload_failed_file = [];

function getInputFileCustomRenderChunkQueue(element, successfulCallback, failureCallback, successChunkfulCallback = null, failureChunkfulCallback = null) {

    var files = [];

    // Check that the element is not a blob
    if (!(element instanceof Blob)) {

        // Get input render custom attribute
        var name = $(element).attr("name");

        // Check if in the queue by name
        if (name in input_file_custom_render_queue)
            files = input_file_custom_render_queue[name];

        // Get from files
        if (files == undefined || files.length == 0) files = [...$(element).prop("files")];
    }
    else
        files.push(element);

    // Delete the queue
    deleteInputFileCustomRenderQueue(name);

    // Create a unique identifier
    var identifier = new Date().getTime();

    // Check if there are some files
    if (files.length > 0) {

        // Cycle the files
        for (let current_file = 0; current_file < files.length; current_file++) {

            // Set the upload success to true for the current file
            chunk_upload_success = true;

            // Get the current file
            var file = files[current_file];

            // Divide the file in chunks
            var chunkSize = 1024 * 1024; // 1MB
            // Calculate the number of chunks
            var total_chunks = Math.ceil(file.size / chunkSize);

            // Cycle all the chunks of the single file
            for (let current_chunk = 0; current_chunk < total_chunks; current_chunk++) {

                // Get the current chunk
                var chunk = file.slice(current_chunk * chunkSize, (current_chunk + 1) * chunkSize);

                // Upload the chunk
                uploadChunk(chunk, file.name, identifier, current_chunk, total_chunks);

                // Check if the upload is failed
                if (chunk_upload_success == false)
                    break;
            }

            // Check if the upload is success
            if (chunk_upload_success == true) {

                // Check that successChunkfulCallback is not null
                if (successChunkfulCallback != null) {

                    var data = {
                        FileName: file.name,
                        FileNumber: (current_file + 1),
                        TotalFiles: files.length,
                    };

                    // Call the callback
                    successChunkfulCallback(data);
                }
            }
            else
                // Delete the chunk
                deleteChunkByIdentifier(identifier, file.name, failureChunkfulCallback);
        }

        // Check if at least one file is uploaded
        if (chunk_upload_failed_file.length < files.length)
            successfulCallback(identifier, chunk_upload_failed_file);
        else
            failureCallback();
    }
    else {
        showInputFileCustomRenderError(name);
        failureCallback();
    }
}
function uploadChunk(current_file, file_name, identifier, current_chunk, total_chunks, retries = 0) {

    // Create a new FormData object
    var formData = new FormData();

    // Set the form data
    formData.append("Files[]", current_file);
    formData.append("FileName", file_name);
    formData.append("ChunksCode", identifier);

    make_ajax_call({
        type: "POST",
        dataType: 'json',
        async: false,
        contentType: false,
        processData: false,
        url: formatUrl(chunk_upload_url),
        data: formData,
    },
        function () {
            chunk_upload_success = true;
        },
        function () {

            // Retry
            if (retries < 3)
                uploadChunk(current_file, file_name, identifier, current_chunk, total_chunks, retries + 1);
            else
                chunk_upload_success = false;

        }, true);

}
function deleteChunkByIdentifier(identifier, file_name, failCall = null) {

    delete_call(
        formatUrl(chunk_upload_url),
        {
            ChunksCode: identifier,
            FileName: file_name
        },
        function () {

            // Add the file to the failed list
            if (!chunk_upload_failed_file.includes(file_name))
                chunk_upload_failed_file.push(file_name);

            // Check if is not null
            if (failCall != null)
                failCall();
        },
        function () {
            notificationError("Qualcosa è andato storto");
        }
    )
}
function formatUrl(url) {

    // Check if has the / at the end
    if (url[url.length - 1] != "/")
        url += "/";

    return url;
}

//#endregion