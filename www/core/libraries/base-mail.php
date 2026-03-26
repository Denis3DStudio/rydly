<?php

use PHPMailer\PHPMailer\PHPMailer;

include_once($_SERVER["DOCUMENT_ROOT"] . '/core/mail/src/PHPMailer.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/core/mail/src/Exception.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/core/mail/src/SMTP.php');

class Base_Mail {

    // Insert the mail in the queue
    public static function addToMailQueue($email, $send = false) {

        // Initialize
        $opHelper = new Base_OperationsHelper();

        // Check if the email is not empty
        if(Base_Functions::IsNullOrEmpty($email->Text))
            return null;

        // Insert the email
        $idEmail = $opHelper->object($email)->table("emails")->insert();

        // Check if the email has a receiver
        if (Base_Functions::IsNullOrEmpty($email->Receiver)) {
            Base_Logs::Error("Insert mail with receiver null (idEmail: $idEmail)");
            $send = false; // Do not send the email if the receiver is null
        }

        // Check if to send now
        if($send)
            self::buildForSendMail($idEmail);

        return $idEmail; 
    }

    // Build send
    public static function buildForSendMail($idEmail) {

        // Initialize
        $linq = new Base_LINQHelper();

        // Get the mail
        $mail = $linq->fromDB("emails")->whereDB("IdEmail = $idEmail")->getFirstOrDefault();

        // Check if the mail is empty
        if(Base_Functions::IsNullOrEmpty($mail))
            return false;

        // Add the Attachments
        $mail->Attachments = self::buildAttachments($linq->selectDB("FileName, FullPath, InRoot")->fromDB("emails_attachments")->whereDB("IdEmail = $idEmail")->getResults());

        // Send the mail
        self::sendMail($mail);
    }

    // Send
    public static function sendMail($mail) {

        // Initialize
        $opHelper = new Base_OperationsHelper();
        $error = null;
        $html_template = "";
        
        // Check if to use the template
        if(defined("MAIL_TEMPLATE") && !Base_Functions::IsNullOrEmpty(MAIL_TEMPLATE))
            $html_template = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/" . MAIL_TEMPLATE, true);

        // Override receiver if DEV
        if(DEV || (defined("MAIL_GESTIONE") && !Base_Functions::IsNullOrEmpty(MAIL_GESTIONE))) 
            $mail->Receiver = MAIL_GESTIONE;
        
        // Override sender if DEV or SANDBOX
        if(DEV || SANDBOX) 
            $mail->Sender = "noreply@valeo.site";

        // Explode by ; receiver
        $mail->Receiver = explode(";", $mail->Receiver);

        // Add CC and BCC if not in debug mode
        $addCCandBCC = IS_DEBUG == false || !defined("MAIL_GESTIONE") || Base_Functions::IsNullOrEmpty(MAIL_GESTIONE);

        // Set CC and BCC
        $mail->CC = $addCCandBCC ? explode(";", $mail->CC) : "";
        $mail->BCC = $addCCandBCC ? explode(";", $mail->BCC) : "";

        // Format text and subject
        // $mail->Subject = Base_Functions::FormatSpecialChars($mail->Subject, false);
        $mail->Text = Base_Functions::FormatSpecialChars(str_replace("\n", "<br>", $mail->Text), false);
        $tmpBody = self::buildBody($mail->Text, $mail->Preheader);

        // PHPMailer Header
        $php_mailer = new PHPMailer();
        $php_mailer->SMTPAutoTLS = false;            // Disable automatic tls
        $php_mailer->CharSet = 'UTF-8';              // Set charset
        $php_mailer->isSMTP();                       // Set mailer to use SMTP
        $php_mailer->Host = SMTP_HOST;               // Specify main and backup SMTP servers
        $php_mailer->Username = SMTP_USERNAME;       // SMTP username
        $php_mailer->Password = SMTP_PASSWORD;       // SMTP password
        $php_mailer->Port = SMTP_PORT;               // TCP port to connect to
        $php_mailer->SMTPAuth = true;                // Enable SMTP authentication
        $php_mailer->isHTML(true);                   // Set email format to HTML
    
        // Set the protocol
        if(!Base_Functions::IsNullOrEmpty(SMTP_PROTOCOL))
            $php_mailer->SMTPSecure = (strtoupper(SMTP_PROTOCOL) == 'SSL') ? 'ssl' : 'tls';

        // Add all mail info
        $php_mailer->From = $mail->Sender;
        $php_mailer->FromName = MAIL_SENDER;
        $php_mailer->Subject = $mail->Subject;
        $php_mailer->Body = !Base_Functions::IsNullOrEmpty($html_template) ? str_replace("@@BODY@@", $tmpBody, $html_template) : $tmpBody;
        $php_mailer->AltBody = str_replace("<br>", "\n ", strip_tags($mail->Text, "<br>"));

        // Add all receivers
        foreach ($mail->Receiver as $receiver)
            $php_mailer->addAddress($receiver, $receiver);

        // Add CC
        if (!Base_Functions::IsNullOrEmpty($mail->CC))
            foreach ($mail->CC as $cc)
                $php_mailer->AddCC($cc, $cc);

        // Add BCC
        if (!Base_Functions::IsNullOrEmpty($mail->BCC))
            foreach ($mail->BCC as $bcc)
                $php_mailer->AddCC($bcc, $bcc);

        // Add Attachments
        if(property_exists($mail, "Attachments") && !Base_Functions::IsNullOrEmpty($mail->Attachments))
            foreach ($mail->Attachments as $attachment)
                $php_mailer->AddAttachment($attachment->FullPath, $attachment->FileName);

        // Send Email
        if(!$php_mailer->Send())
            $error = $php_mailer->ErrorInfo;

        // Close
        $php_mailer->SmtpClose();

        // Unset email
        unset($php_mailer);

        // Create object for update
        $obj = new stdClass();
        $obj->IdEmail = $mail->IdEmail;
        $obj->IsSent = 0;
        $obj->SentDate = null;
        $obj->Error = $error;

        // Check if success
        if (Base_Functions::IsNullOrEmpty($error)) {

            // Add success data
            $obj->IsSent = 1;
            $obj->SentDate = date("Y-m-d H:i:s");
            $obj->Error = null;

        } else {

            // Add attempts
            $obj->Attempt = $mail->Attempt + 1;

            // Check if the attempt is 6 and set as deleted
            if ($obj->Attempt == 6)
                $obj->IsDeleted = 1;
        }

        // Update the email
        $opHelper->object($obj)->table("emails")->where("IdEmail")->update();

        return $error;
    }
    public static function getAndSendMails() {

        // Initialize
        $linq = new Base_LINQHelper();

        // Get all emails that are not sent and not deleted
        $mails = $linq->fromDB("emails")->whereDB("Receiver IS NOT NULL AND Sender IS NOT NULL AND IsSent = 0 AND IsDeleted = 0 AND (Attempt < 3 OR (Attempt < 6 AND CURRENT_TIMESTAMP > DATE_ADD(UpdateDate, INTERVAL 1 HOUR))) LIMIT 10")->getResults();

        // Check if the emails are not empty
        if (Base_Functions::IsNullOrEmpty($mails))
            return;

        // Get all attachments
        $idsMails = implode(",", array_column($mails, "IdEmail"));

        // Get all attachments
        $attachments = $linq->reorder($linq->fromDB("emails_attachments")->whereDB("IdEmail IN ($idsMails) ORDER BY IdEmail ASC")->orderBy("IdEmail ")->getResults(), "IdEmail", true);

        // Cicle all the mails and send them
        foreach ($mails as $mail) {

            // Set the attachments
            $mail->Attachments = property_exists($attachments, $mail->IdEmail) ? self::buildAttachments($attachments->{$mail->IdEmail}) : [];

            // Send the mail
            self::sendMail($mail);
        }

        return true;
    }

    #region Private

        // Build body
        private static function buildBody($email, $preheader) {

            // Init response
            if(Base_Functions::IsNullOrEmpty($preheader))
                return $email;

            // Init counter for preheader
            $charactersToAdd = 150 - mb_strlen($preheader);

            // Add x amount of characters to the preheader to arrive to 100 characters
            $preheader .= " " . str_repeat('&#8203;&nbsp;', $charactersToAdd);

            // Build the response
            $response = "<span style='display:none; font-size:1px; color:#ffffff; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; mso-hide:all;'>
                            $preheader
                        </span>" . $email;
            
            // Return the response
            return $response;
        }
        // Build the attachments
        private static function buildAttachments($attachments) {
            
            // Check if the attachments are empty
            if(Base_Functions::IsNullOrEmpty($attachments))
                return [];

            // Check if the attachments are not an array
            $attachments = is_array($attachments) ? $attachments : [$attachments];

            // Cicle all attachments
            foreach ($attachments as $attachment) {
                // Set the full path
                $attachment->FullPath = !$attachment->InRoot ? OFF_ROOT . $attachment->FullPath : $_SERVER["DOCUMENT_ROOT"] . $attachment->FullPath;

                // Unset InRoot
                unset($attachment->InRoot);
            }

            // Return the attachments
            return $attachments;
        }

    #endregion

}