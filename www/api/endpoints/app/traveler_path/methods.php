<?php

    namespace App\Traveler_Path;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Customer_Type;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get 
            public function getRecap() {

                $sql = "SELECT sqt.Question, GROUP_CONCAT(sqat.Answer) AS Answers
                        FROM surveys_accounts_answers saa
                        INNER JOIN surveys_questions sq ON saa.IdSurveyQuestion = sq.IdSurveyQuestion AND sq.IsValid = 1 AND sq.IsDeleted = 0
                        INNER JOIN surveys_questions_translations sqt ON sq.IdSurveyQuestion = sqt.IdSurveyQuestion AND sqt.IdLanguage = {$this->Logged->IdLanguage}
                        INNER JOIN surveys_questions_answers sqa ON saa.IdSurveyQuestionAnswer = sqa.IdSurveyQuestionAnswer AND sqa.IsValid = 1 AND sqa.IsDeleted = 0
                        INNER JOIN surveys_questions_answers_translations AS sqat ON sqa.IdSurveyQuestionAnswer = sqat.IdSurveyQuestionAnswer AND sqat.IdLanguage = {$this->Logged->IdLanguage}
                        WHERE saa.IdAccount = {$this->Logged->IdAccount}
                        GROUP BY sqt.Question
                        ORDER BY MAX(sq.OrderNumber) ASC";

                $recap = $this->__linq->queryDB($sql)->getResults();
                $recap = array_map(function($item) {
                    $item->Answers = implode(", ", explode(",",$item->Answers));
                    return $item;
                }, $recap);

                return $this->Success($recap);
            }
            public function getQuestions($forMapFilter = 0) {

                $distinct = "";
                $questions_inner = "";
                $questions_group_and_order_by = "ORDER BY sq.OrderNumber ASC";

                $answers_inner = "";
                $answers_group_and_order_by = "ORDER BY sqa.IdSurveyQuestion ASC, sqa.OrderNumber ASC";

                if ($forMapFilter == 1) {

                    $distinct = "DISTINCT";

                    $questions_inner = "INNER JOIN surveys_accounts_answers saa ON sq.IdSurveyQuestion = saa.IdSurveyQuestion AND saa.IdAccount = {$this->Logged->IdAccount}";
                    $questions_group_and_order_by = "GROUP BY sq.IdSurveyQuestion, sq.Type, sq.Collapsable, sqt.Question
                                                     ORDER BY MAX(sq.OrderNumber) ASC";


                    $answers_inner = "INNER JOIN surveys_accounts_answers saa ON sqa.IdSurveyQuestionAnswer = saa.IdSurveyQuestionAnswer AND saa.IdAccount = {$this->Logged->IdAccount}";                                                     
                    $answers_group_and_order_by = "GROUP BY sqa.IdSurveyQuestion, sqa.IdSurveyQuestionAnswer, sqat.Answer
                                                   ORDER BY sqa.IdSurveyQuestion ASC, MAX(sqa.OrderNumber) ASC";
                }

                // Get all the questions with their translations (only the answers of the logged account)
                $sql = "SELECT $distinct sq.IdSurveyQuestion, sq.Type, sq.Collapsable, sqt.Question
                        FROM surveys_questions sq
                        INNER JOIN surveys_questions_translations sqt ON sq.IdSurveyQuestion = sqt.IdSurveyQuestion AND sqt.IdLanguage = {$this->Logged->IdLanguage}
                        $questions_inner
                        WHERE sq.IdSurvey = 1 AND sq.IsValid = 1 AND sq.IsDeleted = 0
                        $questions_group_and_order_by";

                $questions = $this->__linq->queryDB($sql)->getResults();

                if (Base_Functions::IsNullOrEmpty($questions))
                    return $this->Success([]);

                // Get all the questions ids
                $questions_ids = array_column($questions, "IdSurveyQuestion");
                $questions_ids_string = implode(", ", $questions_ids);

                $sql = "SELECT $distinct sqa.IdSurveyQuestion, sqa.IdSurveyQuestionAnswer, sqat.Answer
                        FROM surveys_questions_answers sqa
                        INNER JOIN surveys_questions_answers_translations sqat ON sqa.IdSurveyQuestionAnswer = sqat.IdSurveyQuestionAnswer AND sqat.IdLanguage = {$this->Logged->IdLanguage}
                        $answers_inner
                        WHERE sqa.IsValid = 1 AND sqa.IsDeleted = 0 AND sqa.IdSurveyQuestion IN ($questions_ids_string)
                        $answers_group_and_order_by";

                $answers = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "IdSurveyQuestion", true);

                if (Base_Functions::IsNullOrEmpty($answers))
                    return $this->Success([]);

                $account_answers = array_column($this->__linq->fromDB("surveys_accounts_answers")->whereDB("IdAccount = {$this->Logged->IdAccount}")->getResults(), "IdSurveyQuestionAnswer");

                $response = new stdClass();
                $response->QuestionsAnswers = array();
                $response->AccountAnswers = $account_answers;

                // Cycle all the questions
                foreach($questions as $question) {

                    $obj = new stdClass();
                    $obj->IdSurveyQuestion = $question->IdSurveyQuestion;
                    $obj->Question = $question->Question;
                    $obj->Type = $question->Type;
                    $obj->Collapsable = $question->Collapsable;

                    // Check if there are answers for the question, if yes, add them
                    if (property_exists($answers, $question->IdSurveyQuestion) && !Base_Functions::IsNullOrEmpty($answers->{$question->IdSurveyQuestion})) {

                        $obj->Answers = $answers->{$question->IdSurveyQuestion};
                        // Add also the IdsAnswers array to easily the toggle selected answers
                        $obj->IdsAnswers = array_column($answers->{$question->IdSurveyQuestion}, "IdSurveyQuestionAnswer");

                        array_push($response->QuestionsAnswers, $obj);
                    }
                }

                return $this->Success($response);
            }

            // Put
            public function saveAnswers($idsAnswers) {

                $obj = new stdClass();
                $obj->IdAccount = $this->Logged->IdAccount;

                // Delete the previous answers
                $this->__opHelper->object($obj)->table("surveys_accounts_answers")->where("IdAccount")->delete();

                // Check if there are answers to save
                if (!Base_Functions::IsNullOrEmpty($idsAnswers)) {

                    $idsAnswers_string = implode(", ", $idsAnswers);

                    // Get the data to insert
                    $sql = "SELECT sq.IdSurvey, sq.IdSurveyQuestion, sq.Type AS QuestionType, sqa.IdSurveyQuestionAnswer
                            FROM surveys_questions_answers sqa
                            INNER JOIN surveys_questions sq ON sqa.IdSurveyQuestion = sq.IdSurveyQuestion
                            WHERE sqa.IsValid = 1 AND sqa.IsDeleted = 0 AND sqa.IdSurveyQuestionAnswer IN ($idsAnswers_string)";

                    $answers = $this->__linq->queryDB($sql)->getResults();

                    // Cycle all the answers to insert
                    foreach($answers as $answer) {
    
                        $answer->IdAccount = $this->Logged->IdAccount;
                        $this->__opHelper->object($answer)->table("surveys_accounts_answers")->insert();
                    }
                }

                return $this->Success();
            }

            #region Map

                public function getMapFilters() {

                    // Get the logged account's traveler answers
                    $test = "SELECT sqt.Question, sqat.Answer, saa.IdSurveyQuestion, saa.IdSurveyQuestionAnswer
                            FROM surveys_accounts_answers saa
                            INNER JOIN surveys_questions sq ON saa.IdSurveyQuestion = sq.IdSurveyQuestion AND sq.IsValid = 1 AND sq.IsDeleted = 0
                            INNER JOIN surveys_questions_translations sqt ON sq.IdSurveyQuestion = sqt.IdSurveyQuestion AND sqt.IdLanguage = {$this->Logged->IdLanguage}
                            INNER JOIN surveys_questions_answers sqa ON saa.IdSurveyQuestionAnswer = sqa.IdSurveyQuestionAnswer AND sqa.IsValid = 1 AND sqa.IsDeleted = 0
                            INNER JOIN surveys_questions_answers_translations AS sqat ON sqa.IdSurveyQuestionAnswer = sqat.IdSurveyQuestionAnswer AND sqat.IdLanguage = {$this->Logged->IdLanguage}
                            WHERE saa.IdAccount = {$this->Logged->IdAccount}
                            ORDER BY sq.IdSurveyQuestion ASC";

                    $response = $this->__linq->queryDB($test)->getResults();

                    // Check success
                    if (!$this->Success)
                        return $this->Not_Found();

                    return $this->Success($response);
                }

            #endregion 

        #endregion

    }

?>