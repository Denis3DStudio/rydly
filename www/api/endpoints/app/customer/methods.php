<?php

    namespace App\Customer;

    use Base_Automatic_Mail;
use Base_Customer_Type;
use Base_Encryption;
    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_JWT;
    use Base_Languages;
    use Base_OTP;
    use Mails_Labels;

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
            public function get($idCustomer = null) {

                $idCustomer = $idCustomer ?? $this->Logged->IdAccount;

                // Get the customer that is not deleted by the id
                $customer = $this->__linq->selectDB("IdCustomer, Name, Surname, Email, IsValid")->fromDB("customers")->whereDB("IdCustomer = $idCustomer AND IsDeleted = 0")->getFirstOrDefault();

                // Check that the customer is not null 
                if (!Base_Functions::IsNullOrEmpty($customer))
                    return $this->Success($customer);

                return $this->Not_Found();
            }

            // Post
            public function login($email, $password, $idLanguage) {
                
                // Hash the password
                $password = Base_Functions::Hash($password);
                $token = hash("sha256", strtolower($email) . $password);

                $where = "SHA2(CONCAT(Email, Password), 256) = '$token'";

                // Save the idAccount of the anonymous user
                $id_account_anonymous = $this->Logged->IdAccount;
                // Check customer
                $customer = $this->__linq->fromDB("customers")->whereDB("$where AND IsActive = 1 AND IsValid = 1 AND IsDeleted = 0")->getFirstOrDefault();

                if(Base_Functions::IsNullOrEmpty($customer))
                    return $this->Not_Found();

                // Add the idLanguage to the customer
                $customer->IdLanguage = $idLanguage;
                $this->notification->updateNotificationToken($id_account_anonymous, $customer->IdCustomer);

                // Get response
                $response = $this->format($customer);

                return $this->Success($response);
            }
            public function register() {

                $request = $this->Request;

                if($this->emailExists(null, $request->Email))
                    return $this->Internal_Server_Error(null, "APP.SIGNUP.ERROR_ALREADY_EXISTS");
                
                // Hash the password
                $request->Password = Base_Functions::Hash($request->Password);

                // Lowercase the email
                $request->Email = strtolower($request->Email);

                // IsValid
                $request->IsValid = 1;

                // Check customer
                $idCustomer = $this->__opHelper->object($request)->table("customers")->insert();

                if(Base_Functions::IsNullOrEmpty($idCustomer) || $idCustomer == 0)
                    return $this->Internal_Server_Error(null, "APP.SIGNUP.ERROR_GENERIC");

                return $this->Success($this->format($this->get($idCustomer)));
            }

            // Put
            public function update() {
                
                // Check the customer
                if ($this->checkCustomer($this->Logged->IdAccount) == false)
                    return $this->Not_Found();

                // Get the request obj
                $request = $this->Request;

                // Set the IdCustomer
                $request->IdCustomer = $this->Logged->IdAccount;

                // Check if the email already exists
                if ($this->emailExists($request->IdCustomer, $request->Email))
                    return $this->Internal_Server_Error(null, "APP.SIGNUP.ERROR_ALREADY_EXISTS");

                // Add IsValid to the request obj 
                $request->IsValid = 1;

                // Check that password is null
                if (Base_Functions::IsNullOrEmpty($request->Password))
                    unset($request->Password);
                else
                    $request->Password = Base_Functions::Hash($request->Password);

                // Update
                $this->__opHelper->object($request)->table("customers")->where("IdCustomer")->update();

                // Get account
                $customer = $this->get();

                // Add the idLanguage to the customer
                $customer->IdLanguage = $this->Logged->IdLanguage;

                // Return formatted
                return $this->Success($this->format($customer));
            }
            public function updateIdLanguage($idLanguage) {

                if ($this->Logged->Type == Base_Customer_Type::ANONYMOUS)
                    return $this->Success($this->utility->createAnonymousAccount($idLanguage));

                $customer = $this->get();

                // Check that the customer is not null
                if (Base_Functions::IsNullOrEmpty($customer))
                    return $this->Not_Found();

                // Update the IdLanguage
                $customer->IdLanguage = $idLanguage;

                // Create the response obj
                $response = $this->format($customer);
                return $this->Success($response);
            }

            // Delete
            public function delete() {

                $idCustomer = $this->Logged->IdAccount;

                // Check the customer
                if ($this->checkCustomer($idCustomer) == true) {

                    // Create the obj
                    $obj = new stdClass();
                    $obj->IdCustomer = $idCustomer;
                    $obj->IsDeleted = 1;

                    // Update the row
                    $this->__opHelper->object($obj)->table("customers")->where("IdCustomer")->update();

                    return $this->Success();
                }

                return $this->Not_Found();
            }

            #region Password

                // Change
                public function changePassword() {

                    // Get the request
                    $request = $this->Request;

                    // Check the customer
                    if ($this->checkCustomer($this->Logged->IdAccount) == false)
                        return $this->Not_Found();

                    // Get the customer by id
                    $customer = $this->__linq->selectDB("Password")->fromDB("customers")->whereDB("IdCustomer = {$this->Logged->IdAccount} AND Password = " . Base_Functions::Hash($request->OldPassword))->getFirstOrDefault();

                    // Not found if null or password not equals
                    if (Base_Functions::IsNullOrEmpty($customer))
                        return $this->Not_Found(1, __t("ERROR.CHANGE_PASSWORD.INVALID_OLD_PASSWORD"));

                    // Check if the two passwords are the same
                    if ($request->Password != $request->PasswordConfirm)
                        return $this->Not_Found(2, __t("ERROR.CHANGE_PASSWORD.PASSWORD_NOT_MATCH"));

                    // Build obj for update
                    $obj = new stdClass();
                    $obj->IdCustomer = $this->Logged->IdAccount;
                    $obj->Password = Base_Functions::Hash($request->Password);

                    $res = $this->__opHelper->object($obj)->table("customers")->where("IdCustomer")->update();

                    // Check the update
                    if (Base_Functions::IsNullOrEmpty($res))
                        return $this->Success($this->login('', '', $this->Logged->IdAccount));
                }

                // Reset
                public function recoverPassword() {

                    // Get the account by email
                    $account = $this->__linq->selectDB('IdCustomer')->fromDB("customers")->whereDB("Email = '{$this->Request->Email}'")->getFirstOrDefault();

                    // Check if the account is null
                    if(Base_Functions::IsNullOrEmpty($account))
                        return $this->Not_Found(null, __t("ERROR.RECOVER_PASSWORD.INVALID_EMAIL"));

                    // Ref
                    $ref = "APP_RECOVER_PASSWORD_" . $account->IdCustomer;

                    // Build obj for url
                    $otp = Base_OTP::New($ref);

                    // Send the email
                    Base_Automatic_Mail::createMail(Mails_Labels::FORGOT_PASSWORD_APP, $account->IdCustomer, $this->Request->Email, $otp);

                    // Check if success
                    if($this->Success)
                        return $this->Success($ref);
                    else
                        return $this->Not_Found(null, __t("ERROR.RECOVER_PASSWORD.EMAIL_NOT_SENT"));
                }
                public function validateOTP() {

                    // Get the request
                    $request = $this->Request;

                    // Validate the OTP
                    $otpValidity = Base_OTP::Validity($request->Code, $request->Ref);

                    // If Valid Delete OTP
                    if(!$otpValidity)
                        return $this->Not_Found(null, __t("ERROR.VALIDATE_OTP.INVALID_OTP"));

                    // Delete OTP
                    Base_OTP::DeleteByCode($request->Code);

                    // Tmp
                    $tmp = new stdClass();
                    $tmp->IdCustomer = str_replace("APP_RECOVER_PASSWORD_", "", $request->Ref); // Replace Ref "APP_RECOVER_PASSWORD_"

                    return $this->Success(Base_Encryption::Encrypt(json_encode($tmp)));
                }
                public function resetPassword() {

                    // Get the request
                    $request = $this->Request;

                    // Get the customer
                    $decrypt = json_decode(Base_Encryption::Decrypt($request->Token));

                    // Check if Customer exists
                    $account = $this->__linq->selectDB('Email')->fromDB("customers")->whereDB("IdCustomer = '{$decrypt->IdCustomer}'")->getFirstOrDefault();

                    // Not found if null or password not equals
                    if(Base_Functions::IsNullOrEmpty($account))
                        return $this->Not_Found(null, __t("ERROR.RESET_PASSWORD.ACCOUNT_NOT_FOUND"));

                    // Check if the two passwords are the same
                    if($request->Password != $request->PasswordConfirm)
                        return $this->Not_Found(null, __t("ERROR.RESET_PASSWORD.PASSWORD_NOT_MATCH"));

                    // Build obj for update
                    $obj = new stdClass();
                    $obj->IdCustomer = $decrypt->IdCustomer;
                    $obj->Password = Base_Functions::Hash($request->Password);

                    $res = $this->__opHelper->object($obj)->table("customers")->where("IdCustomer")->update();

                    // Check the update
                    if(Base_Functions::IsNullOrEmpty($res)) 
                        return $this->Success($this->login('', '', $decrypt->IdCustomer));
                }

            #endregion

            #region Favortite Places
                // post
                public function addFavoritePlace($IdPlace) {

                    // check if place exist
                    $place = $this->__linq->selectDB("*")->fromDB("places")->whereDB("IdPlace = $IdPlace")->getFirstOrDefault();

                    if($place) {

                        $obj = new stdClass();
                        $obj->IdCustomer = $this->Logged->IdAccount;
                        $obj->IdPlace = $IdPlace;

                        // check if place is already insert
                        $customer_place = $this->__linq->selectDB("*")->fromDB("customers_favorite_places")->whereDB("IdPlace = $IdPlace AND IdCustomer = $obj->IdCustomer")->getFirstOrDefault();

                        if(!$customer_place) {
                            // insert new favorite place
                            $id = $this->__opHelper->object($obj)->table("customers_favorite_places")->insert();

                            // Check if created
                            if(is_numeric($id) && $id > 0)
                                return $this->Success($id);
                        } else {
                            // remove favorite place
                            $this->__opHelper->object($customer_place)->table("customers_favorite_places")->where("IdCustomerFavoritePlace")->delete();
                            return $this->Success();
                        }
        
                    }

                    return $this->Internal_Server_Error();
                }
            #endregion

        #endregion

        #region Private Methods

            private function format($customer) {

                $response = new stdClass();
                $response->IdAccount = $customer->IdCustomer;
                $response->Name = $customer->Name;
                $response->Surname = $customer->Surname;
                $response->Email = $customer->Email;
                $response->IdLanguage = $customer->IdLanguage ?? Base_Languages::ENGLISH;
                $response->Type = Base_Customer_Type::LOGGED;

                // Add 'Backup' Token
                $response->Token = Base_Encryption::Encrypt(json_encode($response));

                // Create the JWT from the response obj
                $response->JWT = (new Base_JWT())->generateJWT($response);

                return $this->Success($response);
            }

            private function emailExists($idCustomer, $email) {

                $where = !Base_Functions::IsNullOrEmpty($idCustomer) ? "AND IdCustomer != '$idCustomer'" : "";

                // Check if the email already exists
                $customer = $this->__linq->fromDB("customers")->whereDB("Email = '$email' $where AND IsValid = 1 AND IsDeleted = 0")->getFirstOrDefault();

                return !Base_Functions::IsNullOrEmpty($customer);
            }

            private function checkCustomer($idCustomer) {

                // Get the customer by id
                $this->get($idCustomer);

                return $this->Success;
            }

        #endregion
    }

?>