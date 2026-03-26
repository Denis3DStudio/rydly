$(document).ready(function () {
    getLanguages();
    renderTable();
});

// Get
function renderTable() {

    kT = new KTable("#dtItems", {
        ajax: {
            url: BACKEND.CATEGORY_PLACE.ALL,
        },
        sortable: ["IdCategory"],
        columns: [
            {
                title: 'Emoji',
                image: true,
                render: function (data) {
                    return data.Emoji ?? '';
                }
            },
            {
                title: 'Titolo',
                render: function (data) {
                    return data.Title + " [" + data.places_number + "]";
                }
            },
            {
                title: 'Visualizzazione',
                render: function (data) {
                    return data.IsActive == 1 ? '<span class="badge text-bg-success">Pubblicata</span>' : '<span class="badge text-bg-warning">Bozza</span>';
                }
            },
            {

                title: 'Azioni',
                render: function (data) {

                    var disabled = data.IsDeletable == 1 ? '' : 'disabled';

                    return `
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/place_category/${data.IdCategory}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger ${disabled}" onclick="deleteCategory(${data.IdCategory})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `
                }

            }
        ],
        events: {
            pageChanged() {
            },
            sortableCompleted(data) {
                put_call(
                    BACKEND.CATEGORY_PLACE.CATEGORIESORDER,
                    {
                        IdCategory: Url.Params.IdCategory,
                        Order: data,
                    },
                    function (response, message) {

                        hideLoader();
                        notificationSuccess(message);
                    }
                )
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
        BACKEND.CATEGORY_PLACE.INDEX,
        null,
        function (id) {

            hideLoader();
            
            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/place_category/${id}`;
        },
        function (response, message) {

            hideLoader();
            notificationError(message);
        }
    )
}