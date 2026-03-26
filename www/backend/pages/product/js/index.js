$(document).ready(function () {

    getLanguages();
    renderTable();
});

// Get
function renderTable() {

    var dt = new AC_Datatable();
    dt.setAjaxUrl(BACKEND.PRODUCT.ALL)
        .setIdTable("dtProducts")
        .setOptions({
            columnDefs: [
                {
                    title: 'Nome',
                    render: function (data, type, row) {
                        return row.Title;
                    }
                },
                {
                    title: 'Data',
                    type: 'date-uk',
                    render: function (data, type, row) {

                        return fDate("d/m/Y", row.Date);
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
                        return `
                            <button type="button" onclick="duplicateProduct(${row.IdProduct})" class="btn btn-outline-secondary">
                                <i class="fa fa-fw fa-clone"></i>
                            </button>
                            <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/product/${row.IdProduct}">
                                <i class="fa fa-fw fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-link text-danger" onclick="deleteProduct(${row.IdProduct})">
                                <i class="fa fa-fw fa-trash"></i>
                            </button>
                        `
                    }
                },
            ],
            initComplete: function (settings, json) {
                initTooltip();
                hideLoader();
            }
        })
        .initDatatable();
}

// Post
function createProduct() {

    showLoader();

    post_call(
        BACKEND.PRODUCT.INDEX,
        null,
        function (idProduct) {

            hideLoader();

            // Open the product detail page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product/${idProduct}`;

        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}
function duplicateProduct(idProduct) {
    
    showLoader();

    post_call(
        BACKEND.PRODUCT.DUPLICATE,
        { IdProduct: idProduct },
        function (idProduct) {

            hideLoader();

            // Open the product detail page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/product/${idProduct}`;

        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}