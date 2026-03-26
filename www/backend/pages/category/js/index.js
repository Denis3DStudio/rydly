$(document).ready(function () {

    getLanguages();
    renderTable();
});

// Get
function renderTable() {
    kT = new KTable("#dtItems", {
        ajax: {
            url: BACKEND.CATEGORY.ALL
        },
        sort: {
            1: "ASC"
        },
        columns: [
            {
                title: 'Titolo',
                render: function (data) {
                    return data.Title;
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
                title: 'Colore',
                render: function (data) {
                    const colorEl = '<input type="color" value="' + data.Color + '" disabled />'
                    return data.Color ? colorEl : '-';
                }
            },
            {
                title: 'Azioni',
                render: function (data) {

                    var disabled = data.IsDeletable == 1 ? '' : 'disabled';

                    return `
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/category/${data.IdCategory}">
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
            pageChanged() {
            },
            completed(data) {
                hideLoader();
            }
        },
    });
}
// Post
function create() {

    showLoader();

    post_call(
        BACKEND.CATEGORY.INDEX,
        null,
        function (id) {

            hideLoader();
            
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/category/${id}`;
        },
        function (response, message) {

            hideLoader();
            notificationError(message);
        }
    )
}