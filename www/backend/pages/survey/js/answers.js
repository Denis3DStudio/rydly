var global = {
    tables: {},
    accountsAnswers: {}
};

$(document).ready(function () {

    getSurveyAnswers();
});

// Get
function getSurveyAnswers() {

    get_call(
        BACKEND.SURVEY.ACCOUNTSANSWERS,
        {
            IdSurvey: Url.Params.IdSurvey
        },
        function (response) {

            // Set the title
            $("#survey_title").attr("href", `/${ENUM.BASE_KEYS.BACKEND_PATH}/survey/${Url.Params.IdSurvey}`);
            $("#survey_title span").text(response.General.Title);

            if (response.Questions.length > 0) {

                response.Questions = response.Questions.map(function (question) {
                    // Set the question type
                    question.TypeName = ENUM.BASE_SURVEY_QUESTION_TYPE.NAMES[question.Type];
                    // Set the question class
                    question.TypeColor = ENUM.BASE_SURVEY_QUESTION_TYPE.COLORS[question.Type];
                    // Set the question table class
                    question.TableClass = question.Type == ENUM.BASE_SURVEY_QUESTION_TYPE.FREE ? "" : "has--actions";
                    return question;
                });
    
                // Create the questions container
                var template = new AC_Template();
                template.setContainerId("questions_container")
                    .setTemplateId("question_template")
                    .setObjects(response.Questions)
                    .renderView();
    
                // Cycle all the questions
                response.Questions.forEach(function (question) {
    
                    if (question.Type == ENUM.BASE_SURVEY_QUESTION_TYPE.FREE)
                        renderFreeAnswers(question.IdSurveyQuestion, question.Answers);
                    else
                        renderCloseAnswers(question.IdSurveyQuestion, question.Answers);
                });
            }

            hideLoader();
        }
    );
}

// Renders
function renderFreeAnswers(idSurveyQuestion, answers) {

    global.tables[idSurveyQuestion] = new KTable(`#dtQuestion-${idSurveyQuestion}`, {
        data: answers,
        sort: {
            2: "DESC"
        },
        columns: [
            {
                title: "Risposta",
                render: function (data) {
                    return data.Answer;
                },
            },
            {
                title: "Utente",
                render: function (data) {
                    return data.Account.Username;
                },
            },
            {
                title: "Data inserimento",
                render: function (data) {
                    return data.InsertDate;
                },
            },
        ],
        events: {
            pageChanged() {
            },
            completed(data) {
            }
        },
    });
}
function renderCloseAnswers(idSurveyQuestion, answers) {

    global.tables[idSurveyQuestion] = new KTable(`#dtQuestion-${idSurveyQuestion}`, {
        data: answers,
        sort: {
            2: "DESC"
        },
        columns: [
            {
                title: "Risposta",
                render: function (data) {
                    return data.Answer;
                },
            },
            {
                title: "Numero risposte",
                render: function (data) {
                    return data.Number;
                },
            },
            {
                title: "Percentuale",
                render: function (data) {
                    return data.Percentage + "%";
                },
            },
            {
                title: 'Azioni',
                render: function (data) {

                    global.accountsAnswers[data.IdSurveyQuestionAnswer] = data.Accounts;

                    return `
                        <button type="button" class="btn btn-outline-secondary" onclick="renderCloseAnswersAccounts(${data.IdSurveyQuestionAnswer}, '${data.Answer}')">
                            <i class="fa fa-fw fa-users"></i>
                        </button>
                    `;
                }
            }
        ],
        events: {
            pageChanged() {
            },
            completed(data) {
            }
        },
    });
}
function renderCloseAnswersAccounts(idSurveyQuestionAnswer, answer) {

    kT = new KTable(`#dtAccountsAnswer`, {
        data: global.accountsAnswers[idSurveyQuestionAnswer] || [],
        sort: {
            1: "DESC"
        },
        columns: [
            {
                title: "Utente",
                render: function (data) {
                    return data.Username;
                },
            },
            {
                title: "Data inserimento",
                render: function (data) {
                    return data.InsertDate;
                },
            }
        ],
        events: {
            pageChanged() {
            },
            completed(data) {
            }
        },
    });

    $("#account_answer_title").text(answer);
    $("#accountsAnswerModal").modal("show");
}