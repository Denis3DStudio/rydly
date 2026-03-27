$(document).ready(function () {

    getLanguages();
    renderTable();
});

// Get
function renderTable() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.CATEGORY.ALL)
        .setAjaxData("IdType", $("#IdCategoryType").val() ?? null)
        .setIdTable("dtItems")
        .setOptions({
            columnDefs: [
                {
                    title: 'Titolo',
                    render: function (data, type, row) {
                        return row.Title;
                    }
                },
                {
                    title: 'Descrizione',
                    render: function (data, type, row) {

                        // If the description is too long, truncate it
                        return (row.Description.length > 100) ? row.Description.substring(0, 100) + '...' : row.Description;
                    }
                },
                {
                    title: 'Lingue',
                    render: function (data, type, row) {
                        var text = '';

                        global.Languages.forEach(language => {

                            // Check if the language is disabled
                            var disabled = row.IdLanguages.indexOf(parseInt(`${language.Language}`)) > -1 ? '' : 'disabled';

                            text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`

                        });

                        return text;
                    }
                },
                {
                    title: 'Azioni',
                    render: function (data, type, row) {

                        var disabled = row.IsDeletable == 1 ? '' : 'disabled';

                        return `
                            <a class="btn btn-outline-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/${ENUM.BASE_CATEGORY_TYPE.PAGES[$("#IdCategoryType").val()]}${row.IdCategory}">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-link text-danger ${disabled}" onclick="deleteCategory(${row.IdCategory})">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `
                    }
                },
            ],
            initComplete: function (settings, json) {
                hideLoader();
            }
        })
        .initDatatable();

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