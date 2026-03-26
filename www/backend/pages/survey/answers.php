<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 col-md-9 p-0">
            <h1><i class="fa fa-fw fa-square-poll-horizontal op-2"></i> Risposte al traveler path <u><a id="survey_title"><span></span></a></u></h1>
        </div>
    </div>

    <div class="row" id="questions_container">
        <div class="col-12">
            <div class="alert alert-primary text-center" role="alert">
                Nessun utente ha ancora risposto al traveler path.
            </div>
        </div>
    </div>
</div>

<!-- Accounts Answer Modal -->
<div class="modal fade" id="accountsAnswerModal" tabindex="-1" role="dialog" aria-labelledby="accountsAnswerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="accountsAnswerModalLabel">Utenti che hanno risposto "<span id="account_answer_title"></span>"</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <div class="table-responsive">
                            <table class="table bg-white table-bordered dt-responsive" id="dtAccountsAnswer" width="100%" cellspacing="0"></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question template -->
<script type="text/html" id="question_template">
    <div class="col-12 mb-3">
        <div class="accordion" id="question-{{IdSurveyQuestion}}">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{IdSurveyQuestion}}" aria-expanded="false" aria-controls="flush-collapse-{{IdSurveyQuestion}}">
                        {{Question}}
                        <span class="badge bg-{{TypeColor}} ms-2">{{TypeName}}</span>
                    </button>
                </h2>
                <div id="collapse-{{IdSurveyQuestion}}" class="collapse" aria-labelledby="collapse-{{IdSurveyQuestion}}" data-bs-parent="#question-{{IdSurveyQuestion}}">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col">
                                <div class="table-responsive">
                                    <table class="table bg-white table-bordered dt-responsive {{TableClass}}" id="dtQuestion-{{IdSurveyQuestion}}" width="100%" cellspacing="0"></table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>