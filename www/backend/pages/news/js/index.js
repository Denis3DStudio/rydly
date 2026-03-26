$(document).ready(function () {

    getLanguages();
    // Get the categories news
    getCategories(renderTable);
});

//#region Categories

// Get
function getCategories(callback = null) {

    // Init the picker
    get_call(
        BACKEND.CATEGORY.ALL,
        null,
        function (categories) {

            initSelectpicker('#categorySelect');
            buildPicker(categories, '#categorySelect', 'IdCategory', 'Title');

            // Check if callback is not null
            if (callback != null)
                callback();
        }
    )

}

// Add listener to the category select
$(document).on("change", "#categorySelect", renderTable);

//#endregion

// Get
function renderTable() {
    kT = new KTable("#dtNews", {
        ajax: {
            url: BACKEND.NEWS.ALL,
            data: {
                IdsCategories: $('#categorySelect').val()
            }
        },
        sort: {
            1: "ASC"
        },
        columns: [
            {
                title: 'Titolo',
                name: 'Title',
                render: function (data) {
                    return data.Title;
                }
            },
            {
                title: 'Autore',
                name: 'Author',
                render: function (data) {
                    return data.Author || '-';
                }
            },
            {
                title: 'Data',
                type: 'date-uk',
                name: 'Date',
                render: function (data) {

                    return fDate("d/m/Y", data.Date);
                }
            },
            {
                title: 'Categorie',
                orderable: false,
                searchable: false,
                render: function (data) {
                    // Init the text
                    text = "";

                    // Check that categories is not null
                    if (data.Categories.length > 0) {

                        // Cycle all categories
                        for (let index = 0; index < data.Categories.length; index++) {

                            var color = (index % 2 == 0) ? "text-bg-secondary" : "text-bg-dark";
                            text += `<span class="badge ${color}">${data.Categories[index]}</span> `;
                        }
                    }

                    return text;
                }
            },
            {
                title: 'Lingue',
                orderable: false,
                searchable: false,
                render: function (data) {
                    var text = '';

                    global.Languages.forEach(language => {

                        // Check if website has this language
                        var disabled = data.LanguagesIds.indexOf(parseInt(`${language.Language}`)) > -1 ? '' : 'disabled';

                        text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`

                    });

                    return text;
                }
            },
            {
                title : 'Visualizzazione',
                name : 'Status',
                render: function(data) {
                    return (data.Status) ? 'Pubblicato' : 'Bozza'
                }
            },
            {
                title: 'Azioni',
                orderable: false,
                searchable: false,
                render: function (data) {
                    return `
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/news/${data.IdNews}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.NEWS}, ${data.IdNews})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `
                }
            },
        ],
        events: {
            pageChanged() {
            },
            completed(data) {
                initTooltip();
                hideLoader();
            }
        },
    });
}

// Post
function createNews() {

    showLoader();

    post_call(
        BACKEND.NEWS.INDEX,
        null,
        function (idNews) {

            hideLoader();

            // Open the news detail page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/news/${idNews}`;

        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}