<?php

    namespace Backend\Customer;

    use stdClass;
    use Base_Methods;
    use Base_Functions;

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
            public function get($idCustomer) {

                // Get the customer that is not deleted by the id
                $customer = $this->__linq->selectDB("IdCustomer, Name, Surname, Email, IsValid, IsActive")->fromDB("customers")->whereDB("IdCustomer = $idCustomer AND IsDeleted = 0")->getFirstOrDefault();

                // Check that the customer is not null 
                if (!Base_Functions::IsNullOrEmpty($customer))
                    return $this->Success($customer);

                return $this->Not_Found();
            }
            public function getAll() {

                // Get all customers valid and not deleted
                $customers = $this->__linq->fromDB("customers")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults();

                return $this->Success($customers);
            }

            // Post
            public function create() {

                // Create a new customer
                $idCustomer = $this->__opHelper->object("IdCustomer")->table("customers")->insertIncrement();

                // Check if the customer is not null
                if (!Base_Functions::IsNullOrEmpty($idCustomer))
                    return $this->Success($idCustomer);

                return $this->Internal_Server_Error();
            }

            // Put
            public function update() {
                
                // Check the customer
                if ($this->checkCustomer() == true) {

                    // Get the request obj
                    $request = $this->Request;

                    // Create error
                    $errors = new stdClass();
                    $errors->EmailNotUsed = $this->emailExists($request->IdCustomer, $request->Email);

                    if ($errors->EmailNotUsed == true) {

                        // Add IsValid to the request obj 
                        $request->IsValid = 1;
        
                        // Check that password is null
                        if (Base_Functions::IsNullOrEmpty($request->Password))
                            unset($request->Password);
                        else
                            $request->Password = Base_Functions::Hash($request->Password);
        
                        // Update
                        $this->__opHelper->object($request)->table("customers")->where("IdCustomer")->update();
        
                        return $this->Success();
                    }
                    else
                        return $this->Internal_Server_Error($errors);
                }
                    
                return;
            }

            // Delete
            public function delete($idCustomer) {

                // Check the customer
                if ($this->checkCustomer() == true) {

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

            #region Newsletter

                public function getNewsletter() {

                    // Get the newsletter of the customers
                    $newsletters = $this->__linq->selectDB("IdCustomer, Email")->fromDB("customers")->whereDB("IsValid = 1 AND IsDeleted = 0 AND Newsletter = 1")->getResults();

                    // Get general newsletter
                    $generalNewsletter = $this->__linq->selectDB("IdNewsletter, Email")->fromDB("newsletters")->getResults();

                    // Merge the newsletters
                    $newsletters = array_merge($newsletters, $generalNewsletter);

                    // Return all newsletters
                    return $this->Success($newsletters);
                }

            #endregion

        #endregion

        #region Private function

            private function emailExists($idCustomer, $email) {

                // Check if the email already exists
                $customer = $this->__linq->fromDB("customers")->whereDB("Email = '$email' AND IdCustomer != $idCustomer AND IsValid = 1 AND IsDeleted = 0")->getFirstOrDefault();

                return Base_Functions::IsNullOrEmpty($customer);
            }
            private function checkCustomer() {

                // Get the request
                $request = $this->Request;

                // Get the customer by id
                $this->get($request->IdCustomer);

                return $this->Success;
            }

        #endregion
    }

?>