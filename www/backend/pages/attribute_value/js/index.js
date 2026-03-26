$(document).ready(function () {

    getLanguages();
    renderTable();

});

function renderTable() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.ATTRIBUTE_VALUE.ALL)
        .setAjaxData("IdAttribute", Url.Params.IdAttribute)
        .setIdTable("dtCategories")
        .setOptions({
            columnDefs: [
                {
                    title: 'Testo',
                    render: function (data, type, row) {
                        return row.Text;
                    }
                },
                {
                    title: 'Lingue',
                    render: function (data, type, row) {
                        var text = '';

                        global.Languages.forEach(language => {

                            // Check if website has this language
                            var disabled = row.LanguagesIds.indexOf(parseInt(`${language.Language}`)) > -1 ? '' : 'disabled';

                            text += `<i class="flag flag-${language.LanguageLower.toLowerCase()} ${disabled} me-1" tooltip="${language.LanguageLower.toUpperCase()}"></i>`

                        });

                        return text;
                    }
                },
                {
                    title: 'Azioni',
                    render: function (data, type, row) {
                        
                        // Set the action and the disabled attribute
                        var dltBtnAction = row.CanDelete ? `deleteAttributeValue(${row.IdAttributeValue})` : "";
                        var disabled = row.CanDelete ? "" : 'disabled';

                        return `
                            <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${Url.Params.IdAttribute}/${row.IdAttributeValue}">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-link text-danger" onclick="${dltBtnAction}" ${disabled}>
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `;
                    }
                },
            ],
            initComplete: function (settings, json) {
                hideLoader();
            }
        })
        .initDatatable();

}

function createAttributeValue() {

    showLoader();

    post_call(
        BACKEND.ATTRIBUTE_VALUE.INDEX,
        {
            IdAttribute: Url.Params.IdAttribute
        },
        function (response) {

            hideLoader();

            // Change the location to index page
            window.location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/attribute_value/${Url.Params.IdAttribute}/${response}`;
        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}