<?php

    class Base_Simple_Delete {

        const ACCOUNT = 1;
        const TRANSLATION = 2;
        const PROJECT = 3;
        const NEWS = 5;
        const EVENT = 6;
        const SURVEY = 7;
        const SURVEY_QUESTION = 8;
        const SURVEY_QUESTION_ANSWER = 9;
        const CUSTOMER = 10;
        const SPONSOR = 11;
        const ORGANIZATION = 12;
        const COUPON = 13;

        const API_ENDPOINTS = [
            self::ACCOUNT => "ACCOUNT",
            self::TRANSLATION => "TRANSLATION",
            self::PROJECT => "PROJECT",
            self::NEWS => "NEWS",
            self::EVENT => "EVENT",
            self::SURVEY => "SURVEY",
            self::SURVEY_QUESTION => "SURVEY",
            self::SURVEY_QUESTION_ANSWER => "SURVEY",
            self::CUSTOMER => "CUSTOMER",
            self::SPONSOR => "SPONSOR",
            self::ORGANIZATION => "ORGANIZATION",
            self::COUPON => "COUPON",
        ];

        // Insert only if the METHOD is different from INDEX
        const CUSTOM_API_ENDPOINT_METHODS = [
            self::SURVEY_QUESTION => "QUESTION",
            self::SURVEY_QUESTION_ANSWER => "QUESTIONANSWER",
        ];

        const REF_IDS = [
            self::ACCOUNT => "IdAccount",
            self::TRANSLATION => "IdTranslation",
            self::PROJECT => "IdProject",
            self::NEWS => "IdNews",
            self::EVENT => "IdEvent",
            self::SURVEY => "IdSurvey",
            self::SURVEY_QUESTION => "IdSurveyQuestion",
            self::SURVEY_QUESTION_ANSWER => "IdSurveyQuestionAnswer",
            self::CUSTOMER => "IdCustomer",
            self::SPONSOR => "IdSponsor",
            self::ORGANIZATION => "IdOrganization",
            self::COUPON => "IdCoupon",
        ];

        const INDEX_PAGES = [
            self::ACCOUNT => "account",
            self::TRANSLATION => "translation",
            self::PROJECT => "project",
            self::NEWS => "news",
            self::EVENT => "event",
            self::SURVEY => "survey",
            self::SURVEY_QUESTION => null,
            self::SURVEY_QUESTION_ANSWER => null,
            self::CUSTOMER => "customer",
            self::SPONSOR => "sponsor",
            self::ORGANIZATION => "organization",
            self::COUPON => "coupon",
        ];

        const MODAL_QUESTIONS = [
            self::ACCOUNT => "Sei sicuro di voler eliminare l'account selezionato?",
            self::TRANSLATION => "Sei sicuro di voler eliminare la traduzione selezionata?",
            self::PROJECT => "Sei sicuro di voler eliminare il progetto selezionato?",
            self::NEWS => "Sei sicuro di voler eliminare il blog selezionato?",
            self::EVENT => "Sei sicuro di voler eliminare l'evento selezionato?",
            self::SURVEY => "Sei sicuro di voler eliminare il traveler path selezionato?",
            self::SURVEY_QUESTION => "Sei sicuro di voler eliminare la domanda selezionata?",
            self::SURVEY_QUESTION_ANSWER => "Sei sicuro di voler eliminare la risposta selezionata?",
            self::CUSTOMER => "Sei sicuro di voler eliminare il cliente selezionato?",
            self::SPONSOR => "Sei sicuro di voler eliminare lo sponsor selezionato?",
            self::ORGANIZATION => "Sei sicuro di voler eliminare l'organizzatore selezionato?",
            self::COUPON => "Sei sicuro di voler eliminare il coupon selezionato?",
        ];
    }

?>