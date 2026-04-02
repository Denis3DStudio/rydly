<?php

    namespace Backend\Account;

use Base_Account;
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
            public function get($idAccount, $isValid = 0) {

                // Init
                $where = "";

                // Check if only valid or not
                if($isValid == 1)
                    $where = "AND IsValid = 1";

                // Get the account
                $account = $this->__linq->fromDB("accounts")->whereDB("IdAccount = $idAccount AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Check if Logged can see the account
                if(!$this->checkIfLoggedCan($account->IdOrganization) || !$this->canUpdate($account->IdRole, $account->IdAccount))
                    return $this->Unauthorized(null, "You don't have permission to see this account");

                // Check if found
                if(Base_Functions::IsNullOrEmpty($account))
                    return $this->Not_Found(null, "Account not found");

                // Format account
                $account = $this->format($account);

                // Format account
                return $this->Success($account);
            }
            public function getAll() {
                
                // Build where
                $where = "IsValid = 1 AND IsDeleted = 0";

                // Add filter for roles
                $where .= " AND IdRole IN (" . implode(",", Base_Account::ROLES_CAN_SEE[$this->Logged->IdRole]) . ")";

                // Add filter for organization
                if(in_array($this->Logged->IdRole, Base_Account::ROLES_WITH_ORGANIZATION))
                    $where .= $this->buildOrganizationWhere();

                // Get accounts
                $accounts = $this->__linq->fromDB("accounts")->whereDB($where)->getResults();

                // Format accounts
                return $this->Success($this->format($accounts));
            }

            // Post
            public function login($username, $password, $idAccount = null) {

                $where = "IdAccount = $idAccount";

                // Get from username and password
                if(Base_Functions::IsNullOrEmpty($idAccount)) {

                    // Hash password
                    $password = Base_Functions::Hash($password);
                    $token = hash("sha256", $username . $password);

                    // Build where
                    $where = "SHA2(CONCAT(Username, Password), 256) = '$token'";
                }

                // Check account
                $account = $this->__linq->fromDB("accounts")->whereDB("$where AND IsValid = 1 AND IsDeleted = 0")->getFirstOrDefault();

                // Check if found
                if(Base_Functions::IsNullOrEmpty($account))
                    return $this->Not_Found(null, "Credenziali errate");

                return $this->Success($this->format($account));
            }
            public function create() {

                // Get new id
                $idAccount = $this->__opHelper->object("IdAccount")->table("accounts")->insertIncrement();

                // Check if created
                if(!is_numeric($idAccount))
                    return $this->Internal_Server_Error(null, "Account not created");

                // Check if created by an user with organization role and assign the same organization to the new account
                if(!in_array($this->Logged->IdRole, Base_Account::ROLES_WITH_ORGANIZATION))
                    return $this->Success($idAccount);

                // Update with default values
                $obj = new stdClass();
                $obj->IdAccount = $idAccount;
                $obj->IdOrganization = $this->Logged->IdOrganization ?? null;

                // Update
                $this->__opHelper->object($obj)->table("accounts")->where("IdAccount")->update();

                // Return id
                return $this->Success($idAccount);

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

                // Get request
                $obj = $this->Request;

                // Check if account exists
                $account = $this->get($obj->IdAccount);

                // Check if found
                if(!$this->Success)
                    return $this->Not_Found(null, "Account not found");

                // Check if Logged can update the account
                if(!$this->checkIfLoggedCan($account->IdOrganization) || !$this->canUpdate($account->IdRole, $account->IdAccount))
                    return $this->Unauthorized(null, "You don't have permission to update this account");

                // Add IsValid 
                $obj->IsValid = 1;

                // Check if Logged is from Organization
                if(in_array($this->Logged->IdRole, Base_Account::ROLES_WITH_ORGANIZATION))
                    $obj->IdOrganization = $this->Logged->IdOrganization;

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

            private function format($accounts) {

                // Check if array
                $isAll = is_array($accounts);

                // Format response
                $accounts = $isAll ? $accounts : [$accounts];

                // Get all ids
                $idsOrganizations = array_unique(array_filter(array_column($accounts, "IdOrganization")));

                // Get organizations
                $organizations = $idsOrganizations
                                ? $this->__linq->reorder($this->__linq->fromDB("organizations")->whereDB("IdOrganization", $idsOrganizations)->getResults(), "IdOrganization")
                                : new stdClass();

                // Init response
                $response = [];

                // Cycle accounts
                foreach ($accounts as $account) {

                    // Init tmp
                    $tmp = new stdClass();
                    $tmp->IdAccount = $account->IdAccount;
                    $tmp->Name = $account->Name;
                    $tmp->Surname = $account->Surname;
                    $tmp->Username = $account->Username;
                    $tmp->IdRole = $account->IdRole;
                    $tmp->Type = class_exists("Base_Customer_Type") ? \Base_Customer_Type::LOGGED : 2;
                    $tmp->IdLanguage = Base_Languages::ITALIAN;
                    
                    // Add organization
                    $tmp->IdOrganization = $account->IdOrganization;

                    // Add sensitive data only for the single account
                    if(!$isAll) {
                        
                        // Add IsValid
                        $tmp->IsValid = $account->IsValid;

                        // Add token
                        $tmp->Token = Base_Encryption::Encrypt(json_encode($tmp));
                    } else {

                        // Add FullName
                        $tmp->FullName = trim(($account->Name ?? '') . " " . ($account->Surname ?? ''));

                        // Get organization
                        $tmp->Organization = $account->IdOrganization && property_exists($organizations, $account->IdOrganization) ? $organizations->{$account->IdOrganization} : null;
                    }

                    // Push to response
                    array_push($response, $tmp);
                }

                // Return only one if not array
                return $this->Success($isAll ? $response : $response[0]);
            }
            private function canUpdate($idRole, $idAccount = null) {

                // Check if Logged has full access
                if((in_array($this->Logged->IdRole, Base_Account::FULL_ACCESS)))
                    return true;

                // Check if account is the same of the logged user
                if($idAccount && $this->Logged->IdAccount == $idAccount)
                    return true;

                // Check if same role
                if($this->Logged->IdRole == $idRole)
                    return false;

                // Check if the account is from the same role of the logged user
                return true;
            }

        #endregion

    }

?>