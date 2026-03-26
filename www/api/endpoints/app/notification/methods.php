<?php

    namespace App\Notification;

    use Base_Encryption;
    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_JWT;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion
        
        #region Public Methods

            // Post
            public function setNotificationToken($token, $deviceId) {

                // Check if the tokens exists in the table (accounts_notifications_tokens)
                $all_tokens = $this->__linq->fromDB("accounts_notifications_tokens")->whereDB("IdAccount = {$this->Logged->IdAccount}")->getResults();

                // Check if the token exists
                if (Base_Functions::IsNullOrEmpty($all_tokens)) {

                    $obj = new stdClass();
                    $obj->IdAccount = $this->Logged->IdAccount;
                    $obj->Token = $token;
                    $obj->DeviceId = $deviceId;

                    // Insert
                    $this->__opHelper->object($obj)->table("accounts_notifications_tokens")->insert();
                }
                else {

                    // Reorder by DeviceId
                    $all_tokens = $this->__linq->reorder($all_tokens, "DeviceId");
                    // Check if exists the token with the same DeviceId
                    if (property_exists($all_tokens, $deviceId)) {

                        // Update the token
                        $obj = new stdClass();
                        $obj->IdAccountNotificationToken = $all_tokens->{$deviceId}->IdAccountNotificationToken;
                        $obj->Token = $token;

                        // Update
                        $this->__opHelper->object($obj)->table("accounts_notifications_tokens")->where("IdAccountNotificationToken")->update();
                    }
                }

                // Get duplicate token
                $sql = "SELECT Token, MAX(InsertDate) as Date FROM accounts_notifications_tokens GROUP BY Token HAVING COUNT(Token) > 1";
                $duplicates = $this->__linq->queryDB($sql)->getResults();

                // Check if there are duplicates
                if(count($duplicates) > 0) {

                    foreach ($duplicates as $duplicate) {
                    
                        // Leave only the last
                        $sql = "DELETE FROM accounts_notifications_tokens WHERE Token = '{$duplicate->Token}' AND InsertDate < '{$duplicate->Date}'";
                        $this->__linq->queryDB($sql)->getResults();
                    }

                }
            }

            // Put
            public function updateNotificationToken($old_id_account, $id_account) {

                // Set the sql
                $sql = "UPDATE accounts_notifications_tokens SET IdAccount = '$id_account' WHERE IdAccount = '$old_id_account'";
                // Update the token
                $this->__linq->queryDB($sql)->getResults();
            }

        #endregion
    }

?>