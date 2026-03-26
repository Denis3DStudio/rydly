$(document).ready(function () {

    getLanguages();
    renderTable();
});

// Get
function renderTable() {

    kT = new KTable("#dtSurveys", {
        ajax: {
            url: BACKEND.SURVEY.ALL,
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
                title: 'Azioni',
                render: function (data) {

                    return `
                        <a class="btn btn-outline-primary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/survey-answers/${data.IdSurvey}" tooltip="Visualizza Risposte">
                            <i class="fa fa-fw fa-comment"></i>
                        </a>
                        <a class="btn btn-secondary" href="/${ENUM.BASE_KEYS.BACKEND_PATH}/survey/${data.IdSurvey}">
                            <i class="fa fa-fw fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link text-danger" onclick="simpleDelete(${ENUM.BASE_SIMPLE_DELETE.SURVEY}, ${data.IdSurvey})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `;
                }
            }
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
        BACKEND.SURVEY.INDEX,
        null,
        function (id) {

            location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/survey/${id}`;
        }
    )
}