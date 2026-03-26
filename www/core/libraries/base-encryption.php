<?php

class Base_Encryption {

    private static $__encryptionKey = ENCRYPTION_KEY;
    private static $__originalEncryptionKey = ENCRYPTION_KEY;

    /**
     * Encrypt
     * 
     * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
     *
     * @param string or array $val string to be encrypted
     * @param string $key if $val is an array of object, search in it the relative key and encrypt it
     * @param bool $anyway if true, do the encryption even if the value is already encrypted
     */
    public static function Encrypt($val, $key = null, $anyway = false) {
        self::SetEncryptionKey($key);

        // Encrypt only if is not already encrypted
        if(!Base_Functions::IsNullOrEmpty($val)) {
            if($anyway || (!$anyway && !self::checkEncryption($val)))
                $val = self::encrypt_function($val);
        }

        // Set original key
        self::SetEncryptionKey();

        return $val;
    }

    /**
     * Decrypt
     * 
     * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string/
     *
     * @param string $string string to be decrypted
     * @param bool $anyway if true, do the decryption even if the value is already decrypted
     */
    public static function Decrypt($string, $key = null, $anyway = false) {
        self::SetEncryptionKey($key);
            
        // Decrypt only if is encrypted
        if(!Base_Functions::IsNullOrEmpty($string)) {
            if($anyway || (!$anyway && self::checkEncryption($string)))
                $string = self::decrypt_function($string);
        }

        // Set original key
        self::SetEncryptionKey();

        return $string;
    }

    /**
     * @return true if the input value is encrypted
     */
    public static function DecryptJs($string, $key) {

        // Decode from base64 then json
        $jsondata = json_decode(base64_decode($string), true);

        if(Base_Functions::IsNullOrEmpty($jsondata))
            return;

        // Get encryption settings
        $passphrase = $key . hex2bin($jsondata["s"]);
        $ct = base64_decode($jsondata["ct"]);
        $iv  = hex2bin($jsondata["iv"]);
        $encrypt_method = "AES-256-CBC";

        // Cast to binary
        $md5 = array();
        $md5[0] = md5($passphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1] . $passphrase, true);
            $result .= $md5[$i];
        }

        // Get substring
        $key = substr($result, 0, 32);

        // Decrypt
        $data = openssl_decrypt($ct, $encrypt_method, $key, true, $iv);

        // Return 
        return $data;
    }


    private static function encrypt_function($string) {
        [$encrypt_method, $key, $iv] = self::EncryptionSettings();
        
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );

        return $output;
    }

    private static function decrypt_function($string) {
        $output = false;
        [$encrypt_method, $key, $iv] = self::EncryptionSettings();
    
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        
        return $output;
    }

    /**
     * @return true if the input value is encrypted
     */
    public static function checkEncryption($string) {
        return (self::encrypt_function(self::decrypt_function($string)) == $string);
    }

    /**
     * @return settings
     */
    private static function EncryptionSettings() {
        
        $encrypt_method = "AES-256-CBC";
        $iv = substr( hash( 'sha256', self::$__encryptionKey . "_iv" ), 0, 16 );

        return [$encrypt_method, self::$__encryptionKey, $iv];
    }

    private static function SetEncryptionKey($key = null) {
        if(Base_Functions::IsNullOrEmpty($key))
            $key = self::$__originalEncryptionKey;

        // Uppercase > SHA256 > 32 characters
        self::$__encryptionKey = substr(hash('sha256', strtoupper($key)), 0, 32);
    }
}