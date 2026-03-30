<?php

    class Base_Simple_Delete {

        const ACCOUNT = 1;
        const TRANSLATION = 2;
        const PROJECT = 3;
        const NEWS = 5;
        const PLACE = 6;
        const SURVEY = 7;
        const SURVEY_QUESTION = 8;
        const SURVEY_QUESTION_ANSWER = 9;
        const CUSTOMER = 10;
        const SPONSOR = 11;
        const ORGANIZER = 12;

        const API_ENDPOINTS = [
            self::ACCOUNT => "ACCOUNT",
            self::TRANSLATION => "TRANSLATION",
            self::PROJECT => "PROJECT",
            self::NEWS => "NEWS",
            self::PLACE => "PLACE",
            self::SURVEY => "SURVEY",
            self::SURVEY_QUESTION => "SURVEY",
            self::SURVEY_QUESTION_ANSWER => "SURVEY",
            self::CUSTOMER => "CUSTOMER",
            self::SPONSOR => "SPONSOR",
            self::ORGANIZER => "ORGANIZER",
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
            self::PLACE => "IdPlace",
            self::SURVEY => "IdSurvey",
            self::SURVEY_QUESTION => "IdSurveyQuestion",
            self::SURVEY_QUESTION_ANSWER => "IdSurveyQuestionAnswer",
            self::CUSTOMER => "IdCustomer",
            self::SPONSOR => "IdSponsor",
            self::ORGANIZER => "IdOrganizer",
        ];

        const INDEX_PAGES = [
            self::ACCOUNT => "account",
            self::TRANSLATION => "translation",
            self::PROJECT => "project",
            self::NEWS => "news",
            self::PLACE => "place",
            self::SURVEY => "survey",
            self::SURVEY_QUESTION => null,
            self::SURVEY_QUESTION_ANSWER => null,
            self::CUSTOMER => "customer",
            self::SPONSOR => "sponsor",
            self::ORGANIZER => "organizer",
        ];

        const MODAL_QUESTIONS = [
            self::ACCOUNT => "Sei sicuro di voler eliminare l'account selezionato?",
            self::TRANSLATION => "Sei sicuro di voler eliminare la traduzione selezionata?",
            self::PROJECT => "Sei sicuro di voler eliminare il progetto selezionato?",
            self::NEWS => "Sei sicuro di voler eliminare il blog selezionato?",
            self::PLACE => "Sei sicuro di voler eliminare il luogo selezionato?",
            self::SURVEY => "Sei sicuro di voler eliminare il traveler path selezionato?",
            self::SURVEY_QUESTION => "Sei sicuro di voler eliminare la domanda selezionata?",
            self::SURVEY_QUESTION_ANSWER => "Sei sicuro di voler eliminare la risposta selezionata?",
            self::CUSTOMER => "Sei sicuro di voler eliminare il cliente selezionato?",
            self::SPONSOR => "Sei sicuro di voler eliminare lo sponsor selezionato?",
            self::ORGANIZER => "Sei sicuro di voler eliminare l'organizzatore selezionato?",
        ];
    }

?>