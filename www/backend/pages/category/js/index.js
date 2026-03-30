$(document).ready(function () {

    getLanguages();
    renderTable();
});

// Get
function renderTable() {

    new KTable("#dtItems", {
        ajax: {
            url: BACKEND.CATEGORY.ALL,
            data: {
                IdType: $("#IdCategoryType").val() ?? null
            }
        },
        columns: [
            {
                title: 'Titolo',
                render: function (data) {
                    return data.Title;
                }
            },
            {
                title: 'Descrizione',
                render: function (data) {
                    // If the description is too long, truncate it
                    return (data.Description.length > 100) ? data.Description.substring(0, 100) + '...' : data.Description;
                }
            },
            {
                title: 'Lingue',
                render: function (data) {
                    var text = '';

                    global.Languages.forEach(language => {

                        // Check if the language is disabled
                        var disabled = data.IdLanguages.indexOf(parseInt(`${language.Language}`)) > -1 ? '' : 'disabled';

                        text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`

                    });

                    return text;
                }
            },
            {
                title: 'Azioni',
                render: function (data) {

                    var disabled = data.IsDeletable == 1 ? '' : 'disabled';

                    return `
                        <a class="btn btn-outline-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/${ENUM.BASE_CATEGORY_TYPE.PAGES[$("#IdCategoryType").val()]}${data.IdCategory}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger ${disabled}" onclick="deleteCategory(${data.IdCategory})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `
                }
            },
        ],
        events: {
            completed(data) {
                hideLoader();
            }
        }
    });

}

// Post
function create() {

    showLoader();

    post_call(
        BACKEND.CATEGORY.INDEX,
        {
            IdType: $("#IdCategoryType").val() ?? null
        },
        function (id) {

            hideLoader();

            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/${ENUM.BASE_CATEGORY_TYPE.PAGES[$("#IdCategoryType").val()]}${id}`;
        },
        function (response, message) {

            hideLoader();
            notificationError(message);
        }
    )
}