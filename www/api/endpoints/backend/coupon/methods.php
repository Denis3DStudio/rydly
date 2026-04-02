<?php

    namespace Backend\Coupon;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Order_Payment_Status;

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
            public function get($idCoupon) {

                // Get the coupon (Not deleted or paused)
                $coupon = $this->__linq->fromDB("coupons")->whereDB("IdCoupon = $idCoupon AND IsDeleted = 0")->getFirstOrDefault();

                // Check if the coupon exists
                if (!Base_Functions::IsNullOrEmpty($coupon))
                    return $this->Success($this->format($coupon));

                return $this->Not_Found();
            }
            public function getAll() {

                // Get the request
                $request = $this->Request;

                // Build the where condition
                $where = "IsDeleted = 0 AND IsValid = 1";

                // Check if the IdSponsor is set
                if (property_exists($request, "IdSponsor") && !Base_Functions::IsNullOrEmpty($request->IdSponsor))
                    $where .= " AND IdSponsor = $request->IdSponsor";

                // Get all the coupons valid and not deleted
                $coupons = $this->__linq->fromDB("coupons")->whereDB($where)->getResults();

                // Check the in not null
                if (!Base_Functions::IsNullOrEmpty($coupons)) {

                    $status = Base_Order_Payment_Status::PAID_SUCCESS . ", " . Base_Order_Payment_Status::PENDING_PAYMENT;

                    $sql = "SELECT oc.IdCoupon, COUNT(*) AS Count
                            FROM orders o
                            INNER JOIN orders_coupons oc ON o.IdOrder = oc.IdOrder
                            WHERE o.IsValid = 1 AND o.IsDeleted = 0 AND o.Status IN ($status)
                            GROUP BY oc.IdCoupon";

                    // Get the count of the used coupons
                    $coupons_used = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "IdCoupon");

                    // Cycle the coupons
                    foreach ($coupons as $coupon) {

                        // Get the count of the used coupons
                        $coupon->CountUsed = property_exists($coupons_used, $coupon->IdCoupon) ? $coupons_used->{$coupon->IdCoupon}->Count : 0;
                    }
                }

                // Return the coupons
                return $this->Success($coupons);
            }

            // Post
            public function create() {

                // Create a new coupon
                $id_coupon = $this->__opHelper->object("IdCoupon")->table("coupons")->insertIncrement();

                // Get the request
                $request = $this->Request;

                // Check that the coupon was created
                if (Base_Functions::IsNullOrEmpty($id_coupon))
                    return $this->Internal_Server_Error();

                // Check if the IdSponsor is set
                if (!Base_Functions::IsNullOrEmpty($request->IdSponsor)) {

                    // Add IdSponsor to the request
                    $request->IdCoupon = $id_coupon;

                    // Update the sponsor to set the coupon
                    $this->__opHelper->object($request)->table("coupons")->where("IdCoupon")->update();
                }

                return $this->Success($id_coupon);
            }

            // Put
            public function update() {

                // Check if the coupon exists
                $this->get($this->Request->IdCoupon);

                // Check if is success
                if ($this->Success == true) {

                    // Get the request
                    $request = $this->Request;

                    // Check if the code is already in use
                    $coupon_already_used = $this->__linq->fromDB("coupons")->whereDB("Code = '$request->Code' AND IdCoupon != $request->IdCoupon AND IsDeleted = 0")->getFirstOrDefault();

                    // Check if the coupon is already in use
                    if (!Base_Functions::IsNullOrEmpty($coupon_already_used))
                        return $this->Not_Found(null, "Il codice sconto è già in uso");

                    $request->IsValid = 1;

                    // Update the coupon
                    $this->__opHelper->object($request)->table("coupons")->where("IdCoupon")->update();

                    // Clear customers and products table for the coupon
                    $this->__opHelper->object($request)->table("coupons_customers")->where("IdCoupon")->delete();
                    $this->__opHelper->object($request)->table("coupons_products")->where("IdCoupon")->delete();

                    if (!Base_Functions::IsNullOrEmpty($request->IdsProducts)) {

                        // Create the values
                        $values = "($request->IdCoupon, " . implode("), ($request->IdCoupon, ", $request->IdsProducts) . ")";
                        // Insert massive
                        $this->__opHelper->table("coupons_products")->insertMassive("(IdCoupon, IdProduct)", $values);
                    }
                    if (!Base_Functions::IsNullOrEmpty($request->IdsCustomers)) {

                        // Create the values
                        $values = "($request->IdCoupon, " . implode("), ($request->IdCoupon, ", $request->IdsCustomers) . ")";
                        // Insert massive
                        $this->__opHelper->table("coupons_customers")->insertMassive("(IdCoupon, IdCustomer)", $values);
                    }

                    return $this->Success();
                }

                return $this->Not_Found();
            }
            public function enableDisable($idCoupon) {

                // Check if the coupon exists
                $coupon = $this->get($idCoupon, true);

                // Check if is success
                if ($this->Success == true) {

                    // Create the obj to update
                    $obj = new stdClass();
                    $obj->IdCoupon = $idCoupon;
                    $obj->Enabled = $coupon->Enabled == 1 ? 0 : 1;

                    // Enable or disable the coupon
                    $this->__opHelper->object($obj)->table("coupons")->where("IdCoupon")->update();

                    return $this->Success();
                }

                return $this->Not_Found();

            }

            // Delete
            public function delete($idCoupon) {

                // Check if the coupon exists
                $coupon = $this->get($idCoupon, true);

                // Check if the logged user is the owner of the coupon
                if (!$this->checkIfLoggedCan($coupon->IdOrganization))
                    return $this->Unauthorized();

                // Check if is success
                if (!$this->Success)
                    return $this->Not_Found();

                // Create the obj to update
                $obj = new stdClass();
                $obj->IdCoupon = $idCoupon;
                $obj->IsDeleted = 1;

                // Delete the coupon
                $this->__opHelper->object($obj)->table("coupons")->where("IdCoupon")->update();

                return $this->Success();
            }

        #endregion

        #region private Methods

            private function format($coupons) {

                // Check if the coupons is not null
                if (Base_Functions::IsNullOrEmpty($coupons))
                    return null;

                // Check if array
                $isAll = is_array($coupons);

                // If not array, convert to array
                if (!$isAll)
                    $coupons = [$coupons];

                // Get all the ids of sponsors
                $ids_sponsors = array_filter(array_unique(array_column($coupons, "IdSponsor")));

                // Get all the sponsors
                $sponsors = $ids_sponsors 
                                ? $this->__linq->reorder($this->sponsor->getAll($ids_sponsors), "IdSponsor") 
                                : new stdClass();  

                // Init the result
                $result = [];

                // Cycle the coupons
                foreach ($coupons as $coupon) {

                    // Init
                    $tmp = new stdClass();
                    $tmp->IdCoupon = $coupon->IdCoupon;
                    $tmp->IdOrganization = property_exists($sponsors, $coupon->IdSponsor) ? $sponsors->{$coupon->IdSponsor}->IdOrganization : null;
                    $tmp->Code = $coupon->Code;
                    $tmp->Type = $coupon->Type;
                    $tmp->Value = $coupon->Value;
                    $tmp->Enabled = $coupon->Enabled;
                    $tmp->IsValid = $coupon->IsValid;
                    $tmp->IdSponsor = $coupon->IdSponsor;

                    // Get the sponsor
                    $tmp->Sponsor = property_exists($sponsors, $coupon->IdSponsor) ? $sponsors->{$coupon->IdSponsor} : null;

                    // Add the tmp to the result
                    $result[] = $tmp;
                }

                // Return the result
                return $isAll ? $result : $result[0];
            }

        #endregion

    }
    
?>