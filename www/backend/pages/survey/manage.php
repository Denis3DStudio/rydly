<div class="container-fluid">
    <div class="row page-head">
        <div class="col-6 col-md-6 p-0">
            <h1><i class="fa fa-fw fa-square-poll-horizontal op-2"></i> Gestione Traveler Path</h1>
        </div>
        <div class="col-6 col-md-6 p-0 text-end">
            <div class="d-flex justify-content-end">
                <a type="button" class="btn btn-primary me-2" href="/<?= Base_Keys::BACKEND_PATH ?>/survey-answers/<?= $this->__route->__base->Params->IdSurvey ?>"><i class="fa fa-fw fa-comment"></i> Controlla Risposte Utenti</a>
                <button type="button" class="btn btn-success" onclick="createQuestion()"><i class="fa fa-fw fa-plus"></i> Aggiungi Domanda</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <div class="table-responsive">
                    <table class="table table-hover dt-responsive has--actions" id="dtQuestions" style="width: 100%;"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1" role="dialog" aria-labelledby="questionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="questionModalLabel">Gestione Domanda</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row" id="question_container">
                    <input type="hidden" name="IdSurveyQuestion">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Tipologia</label>
                            <select class="selectpicker form-control" name="Type" data-title="Seleziona..." data-width="100%" mandatory>
                                <?php
                                foreach (Base_Survey_Question_Type::ALL as $type) {
                                ?>
                                    <option value="<?= $type ?>"><?= Base_Survey_Question_Type::NAMES[$type] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <div class="form-check">
                                <label class="form-check-label" for="Collapsable">
                                    Filtro Collassabile. <i class="fa fa-fw fa-circle-info op-5" tooltip="Il filtro collassabile permette di mostrare/nascondere le opzioni di filtro associate alla domanda nell'app."></i>
                                </label>
                                <input class="form-check-input" type="checkbox" id="Collapsable" name="Collapsable" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs bg-light" id="nav-tab" role="tablist">
                            </div>
                        </nav>
                        <div class="card mb-3 border-0">
                            <div class="card-body">
                                <div class="tab-content" id="nav-tabContent">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="question_answers_container" style="display: none;">
                    <div class="col-12">
                        <hr class="mt-0">
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <div class="table-responsive">
                                <table class="table table-hover dt-responsive has--actions" id="dtAnswers" style="width: 100%;"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="exportTranslations" onclick="saveQuestion()">Salva</button>
            </div>
        </div>
    </div>
</div>
<!-- Answer Modal -->
<div class="modal fade" id="answerModal" tabindex="-1" role="dialog" aria-labelledby="answerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h1 class="modal-title fs-5" id="answerModalLabel">Gestione Risposta alla domanda: <span id="question_answer_title"></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row" id="answer_container">
                    <input type="hidden" name="IdSurveyQuestionAnswer">
                    <div class="col-12">
                        <!-- <div class="form-group">
                            <label>Risposta</label>
                            <textarea class="form-control" name="Answer" id="Answer" rows="2" mandatory></textarea>
                        </div> -->
                        <nav>
                            <div class="nav nav-tabs bg-light" id="nav-tab-answer" role="tablist">
                            </div>
                        </nav>
                        <div class="card mb-3 border-0">
                            <div class="card-body">
                                <div class="tab-content" id="nav-tab-answer-Content">

                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr class="mt-0">
                            <div class="form-group">
                                <label>Luoghi</label>
                                <select class="selectpicker form-control" name="IdsPlaces" data-title="Seleziona..." data-width="100%" data-live-search="true" multiple>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="exportTranslations" onclick="saveQuestionAnswer()">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Question - Languages -->
<script type="text/html" id="language_tab_template">
    <div class="tab-pane fade" language="{{Language}}" name="tabLang" id="tabLang-{{Language}}" role="tabpanel" aria-labelledby="tabLang-{{Language}}-tab" tabToTranslate>

        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Domanda</label>
                        <textarea class="form-control" name="Question" id="Question" rows="4" mandatory deepl_to_translate deepl_text_format="<?= Base_Text_Format::NORMAL ?>"></textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
</script>

<script type="text/html" id="language_answer_tab_nav_template">
    <a class="nav-item nav-link text-dark op-5" id="tabLang-answer-{{Language}}-tab" data-bs-toggle="tab" href="#tabLang-answer-{{Language}}" role="tab" aria-controls="tabLang-answer-{{Language}}" aria-selected="true">
        <i class="flag flag-{{LanguageLower}}"></i>
    </a>
</script>
<script type="text/html" id="language_answer_tab_template">
    <div class="tab-pane fade" language="{{Language}}" name="tabLang-answer" id="tabLang-answer-{{Language}}" role="tabpanel" aria-labelledby="tabLang-answer-{{Language}}-tab" tabToTranslate>

        <div id="tabContent-{{Language}}" mandatory_fields_container>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Risposta</label>
                        <textarea class="form-control" name="Answer" id="Answer" rows="2" mandatory deepl_to_translate></textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
</script>