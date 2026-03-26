<?php

class Base_OTP {

    /**
     * Create new 6 chars otp token
     * 
     * @param $ref Username / ID / Other
     * 
     * @return object Code & ExpirationDate
     */
    public static function New($ref = null, $times = "+2 minutes") {
        // Delete expired
        self::Clear();

        $linq = new Base_LINQHelper();
        $opHelper = new Base_OperationsHelper();

        // Get already used OTP
        $used_otp = array_column($linq->fromDB("one_time_passwords")->getResults(), "Code");

        // Create the new otp code
        $code = self::GenerateCode();

        // Only unique code
        while (in_array($code, $used_otp)) {
            $code = self::GenerateCode();
        }

        // Check if the ref is not null
        if (!Base_Functions::IsNullOrEmpty($ref)) {

            // Create the obj to delete the rows linked to the ref
            $obj = new stdClass();
            $obj->Ref = $ref;

            // Delete the otp linked with the ref
            $opHelper->object($obj)->table("one_time_passwords")->where("Ref")->delete();
        }

        // Insert
        $obj = new stdClass();
        $obj->Code = $code;
        $obj->InsertDate = date("Y-m-d H:i:s");
        $obj->ExpirationDate = date("Y-m-d H:i:s", strtotime($times, strtotime($obj->InsertDate)));

        if(!Base_Functions::IsNullOrEmpty($ref)) $obj->Ref = $ref;

        // Check if insered
        if(is_numeric($opHelper->object($obj)->table("one_time_passwords")->insert())) {
            // Remove useless properties
            unset($obj->InsertDate);
            unset($obj->UpdateDate);

            return $obj;
        }
        
        return null;
    }

    /**
     * Check if token is still valid
     * 
     * @param $token the token to check
     * @param $ref Username / ID / Other
     * 
     * @return bool 
     */
    public static function Validity($token, $ref = null) {
        // Delete expired
        self::Clear();

        $linq = new Base_LINQHelper();

        // Get the OTP by code
        $where = (Base_Functions::IsNullOrEmpty($ref)) ? '' : " AND Ref = '$ref'";

        $otp = $linq->fromDB("one_time_passwords")->whereDB("Code = '$token' AND ExpirationDate > CURRENT_TIMESTAMP $where")->getFirstOrDefault();

        // Check if exists
        return (!Base_Functions::IsNullOrEmpty($otp));
    }

    /**
     * Get token by ref
     * 
     * @param $ref Username / ID / Other
     * 
     * @return object Code 
     */
    public static function Get($ref) {
        // Delete expired
        self::Clear();

        return (new Base_LINQHelper())->fromDB("one_time_passwords")->whereDB("Ref = '$ref' AND ExpirationDate > CURRENT_TIMESTAMP ORDER BY InsertDate DESC")->getFirstOrDefault();
    }

    /**
     * Delete OTP by code
     * 
     * @param $otp The code
     * 
     * @return bool 
     */
    public static function DeleteByCode($otp) {
        $obj = new stdClass();
        $obj->Code = $otp;
        (new Base_OperationsHelper())->object($obj)->table("one_time_passwords")->where("Code")->delete();
    }

    /**
     * Delete the expired token
     * A token expire after 2 minutes
     */
    public static function Clear() {
        $sql = "DELETE FROM `one_time_passwords` WHERE ExpirationDate <= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MINUTE)";

        (new Base_LINQHelper())->queryDB($sql)->getResults();
    }

    private static function GenerateCode() {
        return rand(10000, 99999);
    }
}