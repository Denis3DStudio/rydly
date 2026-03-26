$(document).ready(function () {

    getLanguages();
    getLanguagesTabs();
    getLanguagesTabs(
        "language_answer_tab_template",
        true,
        "nav-tab-answer",
        "nav-tab-answer-Content",
        "language_answer_tab_nav_template",
        "tabLang-answer"
    );
    get();
});

// Get
function get() {

    get_call(
        BACKEND.SURVEY.INDEX,
        {
            IdSurvey: Url.Params.IdSurvey
        },
        function (response) {

            if (response.AnswersNumber > 0)
                $("#answers_button_container").show();

            // Fill the general data
            fillContentByNames("#common_container", response);
            renderQuestions();

            getPlaces();
        }
    );
}
function getPlaces() {

    get_call(
        BACKEND.PLACE.ALL,
        null,
        function (response) {

            // Fill the places select
            response.forEach(place => {

                $("[name='IdsPlaces']").append(`<option value="${place.IdPlace}" data-subtext="${place.Address}">${place.Name}</option>`);
            });

            // Refresh the selectpicker
            $("[name='IdsPlaces']").selectpicker('refresh');
        }
    )
}

// Put
function save() {

    if (checkMandatory("#common_container")) {

        params = getContentData("#common_container");
        params["IdSurvey"] = Url.Params.IdSurvey;

        put_call(
            BACKEND.SURVEY.INDEX,
            params,
            function (response, message) {

                location.href = `/${ENUM.BASE_KEYS.BACKEND_PATH}/survey?st=ok&m=${message}`
            },
            function (response, message) {

                hideLoader();
                notificationError(message);
            }
        )
    }
}

//#region Questions

// Get
function renderQuestions() {

    Questions_kT = new KTable("#dtQuestions", {
        ajax: {
            url: BACKEND.SURVEY.QUESTIONS,
            data: {
                IdSurvey: Url.Params.IdSurvey
            }
        },
        sortable: "IdSurveyQuestion",
        // buttons: `
        //     <button class="btn btn-outline-success btn-sm" type="button" onclick="createQuestion()">
        //         Aggiungi Domanda
        //     </button>
        // `,
        columns: [
            {
                title: 'Domanda',
                render: function (data) {
                    return data.Question;
                }
            },
            {
                title: 'Tipologia',
                render: function (data) {
                    return `<span class="badge text-bg-${ENUM.BASE_SURVEY_QUESTION_TYPE.COLORS[data.Type]}">${ENUM.BASE_SURVEY_QUESTION_TYPE.NAMES[data.Type]}</span>`;
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
                title: 'Azioni',
                render: function (data) {

                    return `
                        <button type="button" class="btn btn-secondary" onclick="getQuestion(${data.IdSurveyQuestion})">
                            <i class="fa fa-fw fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-link text-danger" onclick="deleteQuestion(${data.IdSurveyQuestion})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        events: {
            pageChanged() {
            },
            sortableCompleted(data) {

                showLoader();

                put_call(
                    BACKEND.SURVEY.QUESTIONSORDER,
                    {
                        Order: data,
                    },
                    function (response, message) {

                        hideLoader();
                        notificationSuccess(message);
                    }
                );
            },
            completed(data) {

                hideLoader();
            }
        },
    });
}
function getQuestion(id) {

    showLoader();

    get_call(
        BACKEND.SURVEY.QUESTION,
        {
            IdSurveyQuestion: id
        },
        function (response) {

            // Fill the question data
            fillContentByNames("#question_container", response);

            // Clear all the language tabs inputs
            clearInputsByContainer("#nav-tabContent");
            $("#nav-tab a").addClass("op-5").removeClass("bg-danger");

            // Set languages
            response.Languages.forEach(news_language => {

                // Check that the news_language is valid and the title is not null
                if (!isEmpty(news_language.Question)) {

                    // Remove the opacity class from the nav flags
                    $(`#tabLang-${news_language.IdLanguage}-tab`).removeClass("op-5");

                    // Insert the data in the inputs
                    fillContentByNames(`#tabLang-${news_language.IdLanguage}`, news_language);
                }
            });

            // Show the question modal
            $("#questionModal").modal("show");
            checkQuestionType();
        }
    );
}

// Post
function createQuestion() {

    post_call(
        BACKEND.SURVEY.QUESTION,
        {
            IdSurvey: Url.Params.IdSurvey
        },
        function (id) {

            getQuestion(id);
        }
    )
}

// Put
function saveQuestion() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs();

    // Check if the mandatory fields are filled
    if (tabs_data.validity && checkMandatory("#question_container")) {

        params = getContentData("#question_container", true);

        // Check if the question type is free or has at least two answer
        if (params.Type != ENUM.BASE_SURVEY_QUESTION_TYPE.FREE && Answers_kT.getLength() < 2) {
            notificationError("Per le domande a scelta multipla è necessario inserire almeno due risposte.");
            return;
        }

        params["Languages"] = tabs_data.Languages;
        delete params["Question"];
        delete params["tabLang"];

        showLoader();
        put_call(
            BACKEND.SURVEY.QUESTION,
            params,
            function (response, message) {

                // Hide the modal
                $("#questionModal").modal("hide");
                // Show success notification
                notificationSuccess(message);
                // Reload the questions table
                renderQuestions();
            },
            function (response, message) {

                hideLoader();
                notificationError(message);
            }
        )
    }
}

// Delete
function deleteQuestion(id) {

    simpleDelete(
        ENUM.BASE_SIMPLE_DELETE.SURVEY_QUESTION,
        id,
        function (response, message) {

            // Hide the modal
            $("#questionModal").modal("hide");
            // Reload the questions table
            renderQuestions();
        });
}

$(document).on("change", "#question_container [name='Type']", checkQuestionType);
function checkQuestionType() {

    // Get type selected
    var type = $('#question_container [name="Type"]').val();

    if (type == ENUM.BASE_SURVEY_QUESTION_TYPE.FREE) {

        $("#question_answers_container").hide();
        hideLoader();
    }
    else {

        // Check if the answers container is already visible
        if (!$("#question_answers_container").is(":visible")) {

            // Render the answers table
            renderQuestionAnswers();
            // Show the answers container
            $("#question_answers_container").show();
        }
        else
            hideLoader();
    }
}

//#endregion

//#region Answers

// Get
function renderQuestionAnswers() {

    Answers_kT = new KTable("#dtAnswers", {
        ajax: {
            url: BACKEND.SURVEY.QUESTIONANSWERS,
            data: {
                IdSurveyQuestion: $('#question_container [name="IdSurveyQuestion"]').val()
            }
        },
        sortable: "IdSurveyQuestionAnswer",
        buttons: `
            <button class="btn btn-outline-success btn-sm" type="button" onclick="createQuestionAnswer()">
                Aggiungi Risposta
            </button>
        `,
        columns: [
            {
                title: 'Risposta',
                render: function (data) {
                    return data.Answer;
                }
            },
            {
                title: 'Luoghi collegate',
                render: function (data) {
                    return data.PlacesNumber;
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
                title: 'Azioni',
                render: function (data) {

                    return `
                        <button class="btn btn-secondary" onclick="getQuestionAnswer(${data.IdSurveyQuestionAnswer})">
                            <i class="fa fa-fw fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-link text-danger" onclick="deleteQuestionAnswer(${data.IdSurveyQuestionAnswer})">
                            <i class="fa fa-fw fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        events: {
            pageChanged() {
            },
            sortableCompleted(data) {

                showLoader();

                put_call(
                    BACKEND.SURVEY.QUESTIONANSWERSORDER,
                    {
                        Order: data,
                    },
                    function (response, message) {

                        hideLoader();
                        notificationSuccess(message);
                    }
                );
            },
            completed(data) {

                hideLoader();
            }
        },
    });
}
function getQuestionAnswer(id) {

    showLoader();

    get_call(
        BACKEND.SURVEY.QUESTIONANSWER,
        {
            IdSurveyQuestionAnswer: id
        },
        function (response) {


            // Fill the answer data
            fillContentByNames("#answer_container", response);
            $("#question_answer_title").text($("#Question").val());

            // Clear all the language tabs inputs
            clearInputsByContainer("#nav-tab-answer-Content");
            $("#nav-tab-answer a").addClass("op-5").removeClass("bg-danger");

            // Set languages
            response.Languages.forEach(news_language => {

                // Check that the news_language is valid and the title is not null
                if (!isEmpty(news_language.Answer)) {

                    // Remove the opacity class from the nav flags
                    $(`#tabLang-answer-${news_language.IdLanguage}-tab`).removeClass("op-5");
                    // Insert the data in the inputs
                    fillContentByNames(`#tabLang-answer-${news_language.IdLanguage}`, news_language);
                }
            });

            // Show the answer modal
            $("#answerModal").modal("show");
            hideLoader();
        }
    );
}

// Post
function createQuestionAnswer() {

    showLoader();
    post_call(
        BACKEND.SURVEY.QUESTIONANSWER,
        {
            IdSurveyQuestion: $('#question_container [name="IdSurveyQuestion"]').val()
        },
        function (id) {

            getQuestionAnswer(id);
        }
    );
}

// Put
function saveQuestionAnswer() {

    // Get the tabs data
    var tabs_data = checkLanguagesTabs(true, "tabLang-answer");

    // Check if the mandatory fields are filled
    if (tabs_data.validity && checkMandatory("#answer_container")) {

        showLoader();

        // Get the parameters
        params = getContentData("#answer_container");
        params["Languages"] = tabs_data.Languages;
        delete params["Answer"];
        delete params["tabLang-answer"];

        put_call(
            BACKEND.SURVEY.QUESTIONANSWER,
            params,
            function (response, message) {

                // Hide the modal
                $("#answerModal").modal("hide");
                // Show success notification
                notificationSuccess(message);
                // Reload the answers table
                renderQuestionAnswers();
            },
            function (response, message) {

                hideLoader();
                notificationError(message);
            }
        )
    }

}

// Delete
function deleteQuestionAnswer(id) {

    simpleDelete(
        ENUM.BASE_SIMPLE_DELETE.SURVEY_QUESTION_ANSWER,
        id,
        function (response, message) {

            // Hide the modal
            $("#answerModal").modal("hide");
            // Reload the answers table
            renderQuestionAnswers();
        });
}

//#endregion