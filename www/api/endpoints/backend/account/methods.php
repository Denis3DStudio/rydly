<?php

    namespace Backend\Account;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Languages;
    use Base_Encryption;

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
            public function getAll() {
                
                // Get accounts
                $accounts = $this->__linq->fromDB("accounts")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults();

                // Format accounts
                $response = array();
                foreach ($accounts as $account) {

                    if ($this->Logged->IdRole >= $account->IdRole)
                        array_push($response, $this->format($account));
                }

                return $this->Success($response);
            }
            public function get($idAccount, $isValid = 0) {

                $where = "";

                // Check if only valid or not
                if($isValid == 1)
                    $where = "AND IsValid = 1";

                // Get the account
                $account = $this->__linq->fromDB("accounts")->whereDB("IdAccount = $idAccount AND IsDeleted = 0 $where")->getFirstOrDefault();

                if(Base_Functions::IsNullOrEmpty($account))
                    return $this->Not_Found(null, "Account not found");

                // Format account
                $account = $this->format($account);

                // Format account
                return $this->Success($account);
            }

            // Post
            public function login($username, $password, $idAccount = null) {

                $where = "IdAccount = $idAccount";

                // Get from username and password
                if(Base_Functions::IsNullOrEmpty($idAccount)) {

                    $password = Base_Functions::Hash($password);
                    $token = hash("sha256", $username . $password);

                    $where = "SHA2(CONCAT(Username, Password), 256) = '$token'";

                }

                // Check account
                $account = $this->__linq->fromDB("accounts")->whereDB("$where AND IsValid = 1 AND IsDeleted = 0")->getFirstOrDefault();

                if(Base_Functions::IsNullOrEmpty($account))
                    return $this->Not_Found(null, "Credenziali errate");

                // Get response
                $response = $this->format($account);

                return $this->Success($response);
            }
            public function create() {
                $idAccount = $this->__opHelper->object("IdAccount")->table("accounts")->insertIncrement();

                // Check if created
                if(is_numeric($idAccount) && $idAccount > 0)
                    return $this->Success($idAccount);

                return $this->Internal_Server_Error(null, "Account not created");

            }
            public function impersonate($idAccount) {

                // Check account
                $this->get($idAccount, 1);

                // Check if found
                if($this->Success == false)
                    return;

                // Return payload
                return $this->login(null, null, $idAccount);
            }


            // Put
            public function update() {
                $obj = $this->Request;

                // Check if account exists
                $this->get($obj->IdAccount);

                if($this->Success == false)
                    return;

                // Add IsValid 
                $obj->IsValid = 1;

                // Check password
                if(Base_Functions::IsNullOrEmpty($obj->Password))
                    unset($obj->Password);
                
                else
                    $obj->Password = Base_Functions::Hash($obj->Password);

                // Update
                $this->__opHelper->object($obj)->table("accounts")->where("IdAccount")->update();

                return $this->Success();

            }

            // Delete
            public function delete($idAccount) {

                // Check if account exists
                $this->get($idAccount);

                if($this->Success == false)
                    return;

                $obj = new stdClass();
                $obj->IdAccount = $idAccount;
                $obj->IsDeleted = 1;

                // Update
                $this->__opHelper->object($obj)->table("accounts")->where("IdAccount")->update();

                return $this->Success();
            }

            #region RM

                public function getEncrypted() {

                    return $this->Success(Base_Encryption::Encrypt(json_encode($this->Request)));

                }
                public function getDecrypted() {

                    return $this->Success(json_decode(Base_Encryption::Decrypt(($this->Request->Crypted_string))));

                }

            #endregion

        #endregion
            
        #region Private Methods

            private function format($account) {

                $response = new stdClass();
                $response->IdAccount = $account->IdAccount;
                $response->Name = $account->Name;
                $response->Surname = $account->Surname;
                $response->Username = $account->Username;
                $response->IdRole = $account->IdRole;
                $response->IsValid = $account->IsValid;
                $response->Type = class_exists("Base_Customer_Type") ? \Base_Customer_Type::LOGGED : 2;
                $response->IdLanguage = Base_Languages::ITALIAN;

                // Add 'Backup' Token
                $response->Token = Base_Encryption::Encrypt(json_encode($response));

                return $this->Success($response);
            }

        #endregion

    }

?>