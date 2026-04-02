$(document).ready(function () {
    getLanguages();
    // Get the categories events
    getCategories(renderTable);
});

// Get
function getCategories(callback = null) {

    // Init the picker
    get_call(
        BACKEND.CATEGORY.ALL,
        {
            IdType: ENUM.BASE_CATEGORY_TYPE.EVENT
        },
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
    kT = new KTable("#dtPlace", {
        ajax: {
            url: BACKEND.EVENT.ALL,
            data: {
                IdsCategories: $('#categorySelect').val()
            }
        },
        columns: [
            {
                title: 'Organizzatore',
                visible: !ENUM.BASE_ACCOUNT.ROLES_WITH_ORGANIZATION.includes(Logged.IdRole), // Hide if the user has a role with organization or belongs to an organization
                filterable: true,
                render: function (data) {
                    if(!data.Organization)
                        return '-';

                    return `<a class="underline" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/organization/${data.Organization.IdOrganization}" target="_blank">${data.Organization.Name}</a>`;
                }
            },
            {
                title: 'Nome',
                render: function (data) {
                    return data.Name;
                }
            },
            {
                title: 'Categoria Principale',
                filterable: true,
                render: function (data) {
                    // Check if the main category property exists
                    if (!data.MainCategory)
                        return '-';

                    return `<a class="underline" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/category/${data.MainCategory.IdCategory}" target="_blank">${data.MainCategory.Title}</a>`;
                }
            },
            {
                title: 'Categorie',
                filterable: true,
                render: function (data) {
                    // Check if the categories property exists and is an array
                    if (!data.Categories)
                        return '-';

                    return data.Categories.map(function (category) {
                        return `<a class="underline" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/category/${category.IdCategory}" target="_blank">${category.Title}</a>`;
                    }).join(' - ');
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
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/event/${data.IdEvent}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.EVENT}, ${data.IdEvent})">
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
function createPlace() {

    showLoader();

    post_call(
        BACKEND.EVENT.INDEX,
        null,
        function (idEvent) {

            hideLoader();

            // Open the news detail page
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/event/${idEvent}`;

        },
        function () {

            hideLoader();

            notificationError("Qualcosa è andato storto!");
        }
    )
}