<?php

class Base_Automatic_Mail {

    public static function createMail($label) {

        $linq = new Base_LINQHelper();

        if(!Base_Functions::IsNullOrEmpty($label)) {

            // Get all args
            $args = func_get_args();

            // Get if the email is to send
            $toAddToQueue = true;

            // Init the emailExtra
            $emailExtra = new stdClass();
            $emailExtra->Sender = MAIL_NOREPLY;

            // Check the label
            switch (strtoupper($label)) {

                case Mails_Labels::FORGOT_PASSWORD_APP:
                    
                    // Get opt
                    $otp = $args[3];

                    // Get customer
                    $customer = $linq->selectDB("Name, Surname")->fromDB("customers")->whereDB("IdCustomer = $args[1]")->getFirstOrDefault();

                    // Build obj for email
                    $obj = new stdClass();
                    $obj->Name = $customer->Name . ' ' . $customer->Surname;
                    $obj->OTPCode = $otp->Code;
                    $obj->ExpirationDate = Base_Functions::FormatDate('Y-m-d H:i', $otp->ExpirationDate);

                    // Build the emailExtra
                    $emailExtra->Receiver = $args[2];
                    
                    // Get email built
                    $email = self::createEmailObj($label, $obj, $emailExtra);

                    break;

                case Mails_Labels::NEWS_WRITE_REQUEST:

                    // set reciver admin
                    $emailExtra->Receiver = ADMIN_EMAIL;

                    // Build the obj
                    $obj = new stdClass();
                    $obj->name = $args[1];
                    $obj->surname = $args[2];
                    $obj->email = $args[3];
                    $obj->message = $args[4];

                    Translations::$IdLanguage = Base_Languages::ITALIAN;

                    // Get email built
                    $email = self::createEmailObj($label, $obj, $emailExtra);

                    break;
                case Mails_Labels::PLACE_CLAIM_REQUEST:

                    // set reciver admin
                    $emailExtra->Receiver = ADMIN_EMAIL;

                    // Build the obj
                    $obj = new stdClass();
                    $obj->FullName = trim($args[1] . ' ' . $args[2]);
                    $obj->Email = $args[3];

                    // Get the place name
                    $place = $linq->selectDB("Name")->fromDB("places")->whereDB("IdPlace = " . $args[4])->getFirstOrDefault();
                    $obj->PlaceName = $place->Name;

                    Translations::$IdLanguage = Base_Languages::ITALIAN;

                    // Get email built
                    $email = self::createEmailObj($label, $obj, $emailExtra);

                    break;
                case Mails_Labels::PLACE_SUGGEST_REQUEST:

                    // set reciver admin
                    $emailExtra->Receiver = ADMIN_EMAIL;

                    // Build the obj
                    $obj = new stdClass();
                    $obj->Email = $args[1];
                    $obj->PlaceName = $args[2];
                    $obj->City = $args[3];
                    $obj->Message = $args[4];

                    Translations::$IdLanguage = Base_Languages::ITALIAN;

                    // Get email built
                    $email = self::createEmailObj($label, $obj, $emailExtra);

                    break;
            }

            // Check if the email is to send
            if($toAddToQueue) {

                // Check what environment is and get the right send now array
                $env = PROD ? "PROD" : ((DEV && !SANDBOX) ? "DEV" : (SANDBOX ? "SANDBOX" : "DEV"));

                // Check it the email is to send now
                $to_send_now = in_array($label, Mails_Labels::SEND_NOW[$env]);

                return Base_Mail::addToMailQueue($email, $to_send_now);
            }
        }
    }

    public static function insertAttachments($idEmail, $attachments = [], $send = true) {

        // Initialize
        $opHelper = new Base_OperationsHelper();

        // Cicle all the attachments
        foreach ($attachments as $attachment) {

            // Add the idEmail
            $attachment->IdEmail = $idEmail;

            // Insert the attachment
            $opHelper->object($attachment)->table("emails_attachments")->insert();
        }

        // Send the mail
        if($send)
            Base_Mail::buildForSendMail($idEmail);
    } 

    private static function createEmailObj($label, $obj, $extras = new stdClass()) {

        // Create email obj
        $email = self::getTemplate($label, $obj);

        // Complete email obj with extras
        $email->Receiver = property_exists($extras, "Receiver") ? $extras->Receiver : "";
        $email->Sender = property_exists($extras, "Sender") ? $extras->Sender : "";
        $email->ContentRefId = property_exists($extras, "ContentRefId") ? $extras->ContentRefId : null;
        $email->ContentName = property_exists($extras, "ContentName") ? $extras->ContentName : null;
        $email->CC = property_exists($extras, "CC") ? $extras->CC : "";
        $email->BCC = property_exists($extras, "BCC") ? $extras->BCC : "";
        $email->Subject = property_exists($extras, "Subject") ? $extras->Subject : ($email->Subject ?? MAIL_SUBJECT);
        $email->Preheader = property_exists($extras, "Preheader") ? $extras->Preheader : "";

        // Return the email obj
        return $email;
    }

    private static function getTemplate($file_name, $obj = null) {

        $ret = new stdClass();
        $ret->Subject = "";
        $ret->Text = "";

        // Init separator keyword
        $separator_keyword = "_-_-_";

        // get template from template
        $template = fill__t("EMAIL." . Mails_Labels::TEMPLATE[$file_name], $obj, "@@", "@@");

        // Check if is not null
        if (!Base_Functions::IsNullOrEmpty($template)) {

            // Check if has separator between subject and text
            if(Base_Functions::HasSubstring($template, $separator_keyword)) {
                    
                // Separate
                $exp = explode($separator_keyword, $template);
    
                $ret->Subject = html_entity_decode(strip_tags(trim($exp[0])));
                $ret->Text = trim($exp[1]);
    
            } else {
                $ret->Text = trim($template);
            }
        }

        return $ret;
    }
}

class Mails_Labels {

    const FORGOT_PASSWORD_APP = "FORGOT_PASSWORD_APP";

    const NEWS_WRITE_REQUEST = "NEWS_WRITE_REQUEST";
    
    const PLACE_CLAIM_REQUEST = "PLACE_CLAIM_REQUEST";
    const PLACE_SUGGEST_REQUEST = "PLACE_SUGGEST_REQUEST";

    const TEMPLATE = [
        self::FORGOT_PASSWORD_APP => "CUSTOMER.FORGOT_PASSWORD_APP",
        self::NEWS_WRITE_REQUEST => "NEWS.WRITE_REQUEST",
        self::PLACE_CLAIM_REQUEST => "PLACE.CLAIM_REQUEST",
        self::PLACE_SUGGEST_REQUEST => "PLACE.SUGGEST_REQUEST"
    ];

    const SEND_NOW = [
        "PROD" => [
            self::FORGOT_PASSWORD_APP
        ],
        "DEV" => [
            // self::FORGOT_PASSWORD_APP,
            // self::NEWS_WRITE_REQUEST,
            self::PLACE_CLAIM_REQUEST,
            // self::PLACE_SUGGEST_REQUEST
        ],
        "SANDBOX" => [
            self::FORGOT_PASSWORD_APP,
            self::NEWS_WRITE_REQUEST,
            self::PLACE_CLAIM_REQUEST,
            self::PLACE_SUGGEST_REQUEST
        ]
    ];

}