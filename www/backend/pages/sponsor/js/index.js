$(document).ready(function () {
    getLanguages();
    // Get the categories sponsors
    getCategories(renderTable);
});

// Get
function getCategories(callback = null) {

    // Init the picker
    get_call(
        BACKEND.CATEGORY_PLACE.ALL,
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

function renderTable() {
    console.log($('#categorySelect').val());
    kT = new KTable("#dtSponsors", {
        ajax: {
            url: BACKEND.SPONSOR.ALL,
            data: {
                IdsCategories: $('#categorySelect').val()
            }
        },
        columns: [
            {
                title: 'Nome',
                render: function (data) {
                    return data.Name;
                }
            },
            {
                title: 'Categoria Principale',
                render: function (data) {
                    return data.MainCategory;
                }
            },
            {
                title: 'Categorie',
                render: function (data) {
                    return data.Categories;
                }
            },
            {
                title: 'Città',
                render: function (data) {
                    return data.City;
                }
            },
            {
                title: 'Visualizzazione',
                render: function (data) {
                    return data.IsActive == 1 ? '<span class="badge text-bg-success">Pubblicata</span>' : '<span class="badge text-bg-warning">Bozza</span>';
                }
            },
            {
                title: 'Scheda',
                render: function (data) {
                    return data.IsClaimed == 1 ? '<span class="badge text-bg-primary">Rivendicata</span>' : '<span class="badge text-bg-secondary">Da rivendicare</span>';
                }
            },
            {
                title: 'Azioni',
                orderable: false,
                searchable: false,
                render: function (data) {
                    return `
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/sponsor/${data.IdSponsor}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.SPONSOR}, ${data.IdSponsor})">
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
function createSponsor() {

    showLoader();

    post_call(
        BACKEND.SPONSOR.INDEX,
        null,
        function (idSponsor) {

            hideLoader();

            // Open the news detail page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/sponsor/${idSponsor}`;

        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}