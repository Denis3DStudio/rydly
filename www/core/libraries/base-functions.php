<?php

class Base_Functions {

    #region Checkers
        /**
         * Check if all the properties of the object are not null or empty
         * 
         * @return object with a new boolean field ObjIsValid
         */
        public static function MandatoryObject($obj) {
            $ret = new stdClass();
            $ret->ObjIsValid = false;

            if(!self::IsNullOrEmpty($obj)) {
                $ret = $obj;
                $objIsValid = true;

                foreach ($obj as $key => $o) {
                    if(self::IsNullOrEmpty($o)) {
                        $objIsValid = false;
                        break;
                    }
                }

                $ret->ObjIsValid = $objIsValid;
            }

            return $ret;
        }

        /**
         * Check if all the args are not null or empty
         * 
         * @example MandatoryFields($a, $b, $c);
         * 
         * @return false if something is empty
         */
        public static function MandatoryFields() {
            $ret = true;

            foreach (func_get_args() as $key => $arg) {
                if(self::IsNullOrEmpty($arg)) {
                    $ret = false;
                    break;
                }
            }

            return $ret;
        }

        /**
         * If variable is:
         * - string => null or empty
         * - array => without elements
         * - object => without properties
         * 
         * @return true if null or empty
         */
        public static function IsNullOrEmpty() {
            $isNullOrEmpty = array();

            // Get args
            $to_check = func_get_args();

            // Check if array
            if(!is_array($to_check)) $to_check = array($to_check);

            // Check fields
            foreach ($to_check as $field) {
                
                if(is_null($field))
                    array_push($isNullOrEmpty, true);

                elseif(is_int($field))
                    array_push($isNullOrEmpty, false);

                elseif(is_string($field)) {
                    $check = strip_tags(html_entity_decode($field));
                    // $check = preg_replace('/\s/', '', (empty(trim($check)) ? $field : $check));
                    $check = preg_replace('/\s/', '', $check);
                    array_push($isNullOrEmpty, $check == null || $check == "");
                }
                elseif(is_array($field))
                    array_push($isNullOrEmpty, (count($field) == 0));

                elseif(is_object($field))
                    array_push($isNullOrEmpty, (count((array)$field) == 0));

                elseif(is_bool($field))
                    array_push($isNullOrEmpty, false);

                else
                    array_push($isNullOrEmpty, false);

            }

            // If even just one element is empty
            return in_array(true, $isNullOrEmpty);
        }

        /**
         * Check if string contain substring
         * @return bool
         */
        public static function HasSubstring($string, $substring) {
            return (strpos($string, $substring) !== false);
        }

        /**
         * Check if string is a json
         * @return bool
         */
        public static function IsJson($string) {
            if(is_object($string)) {
                return false;
            }
            elseif(is_string($string) && !is_numeric($string)) {
                json_decode($string);
                return (json_last_error() == JSON_ERROR_NONE);
            }
            return false;
        }

        /**
         * Check if string is a valid email
         * @return bool
        */
        public static function IsValidEmail($string) {
            
            return filter_var($string, FILTER_VALIDATE_EMAIL);
        }

        /**
         * Check if string is a IBAN
         * @return bool
         */
        public static function checkIBAN($iban) {
            // Normalize input (remove spaces and make upcase)
            $iban = strtoupper(str_replace(' ', '', $iban));
        
            if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            $country = substr($iban, 0, 2);
            $check = intval(substr($iban, 2, 2));
            $account = substr($iban, 4);
        
            // To numeric representation
            $search = range('A','Z');
            foreach (range(10,35) as $tmp)
                $replace[]=strval($tmp);
            $numstr=str_replace($search, $replace, $account.$country.'00');
        
            // Calculate checksum
            $checksum = intval(substr($numstr, 0, 1));
            for ($pos = 1; $pos < strlen($numstr); $pos++) {
                $checksum *= 10;
                $checksum += intval(substr($numstr, $pos,1));
                $checksum %= 97;
            }
        
            return ((98-$checksum) == $check);
            } else
            return false;
        }
    #endregion

    #region String
        /**
         * Split the given sentence into words and return only the count
         * @param $sentence the full sentence
         * @param $words the number of words to show
         * 
         * @example ("Lorem ipsum dolor sit amet", 3) => "Lorem ipsum dolor..."
         * 
         * @return string the new sentence
         */
        public static function SplitByWord($sentence, $words) {
            $ret = $sentence;

            $split = preg_split('/\s+/', $sentence);

            if(count($split) > $words)
                $ret = implode(" ", array_slice($split, 0, $words)) . "...";

            return $ret;
        }

        /**
         * Convert the given string to sha hash
         * @param $string to hash
         * 
         * @return string the hash
         */
        public static function Hash($string) {
            return hash('sha256', '__' . hash('sha256', $string));
        }

        /**
         * Generate unique token
         * 
         * @return string the token
         */
        public static function UniqueToken() {
            return md5(uniqid(rand(), true));
        }

        /**
         * Generate a GUID
         * 
         * @return string the Guid string
         */
        public static function Guid() {

            return strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)));
        }


        /**
         * Change the special chars with the normal one
         * @param $string to convert
         * 
         * @example però perché => per&ograve; perch&eacute;
         * 
         * @return string the converted string
         */
        public static function FormatSpecialChars($string, $htmlspch = true, $remove_leading_zero = false) {

            if ($htmlspch)
                $string = htmlspecialchars($string, ENT_QUOTES);

            $special = array('À', 'Á', 'È', 'É', 'Ì', 'Í', 'Ò', 'Ó', 'Ù', 'Ú', 'à', 'á', 'è', 'é', 'ì', 'í', 'ò', 'ó', 'ù', 'ú');
            $normal = array('&Agrave;', '&Aacute;','&Egrave;', '&Eacute;', '&Igrave;', '&Iacute;', '&Ograve;', '&Oacute;', '&Ugrave;', '&Uacute;', '&agrave;', '&aacute;', '&egrave;', '&eacute;', '&igrave;', '&iacute;', '&ograve;', '&oacute;', '&ugrave;', '&uacute;');

            if ($remove_leading_zero)
                $string = str_replace("&#0", "&#", $string);
            
            return str_replace($special, $normal, $string);
        }

        /**
         * Concatenate string removing empty
         * @param $strings to convert
         * 
         * @return string concat
         */
        public static function ConcatStrings($glue) {
            $response = "";

            // Get args
            $args = func_get_args();

            // Remove the first (the glue)
            array_shift($args);

            // Create string
            $response = implode($glue, array_filter(array_values($args)));

            return $response;
        }

        /**
         * Get text values between start char and end char
         * @param $text string to search in
         * @param $start ex. @@
         * @param $end ex. @@
         * 
         * @return Array strings
         */
        public static function getTextInTags($text, $start, $end) {

            $res = array();
            $ini = 0;

            // Get all $this-> properties
            while (is_numeric($ini)) {
                $ini = strpos($text, $start, $ini);

                if (is_numeric($ini)) {
                    $ini += strlen($start);
                    $len = strpos($text, $end, $ini) - $ini;

                    $sub = substr($text, $ini, $len);

                    // If has not spaces, is less than 15 chars and not already in res
                    if ($sub != false)
                        array_push($res, $sub);
                    
                    $ini += $len + 2;
                }

                // Check if ini > strlen
                if ($ini > strlen($text))
                    $ini = null;
            }

            return $res;
        }

        /**
         * Cast UTF8 to ANSI
         * @param $text in UTF8
         * 
         * @return string ANSI
         */
        public static function UTF82ANSI($text) {

            $utf8_ansi2 = array(
                "\u00c0" =>"À",
                "\u00c1" =>"Á",
                "\u00c2" =>"Â",
                "\u00c3" =>"Ã",
                "\u00c4" =>"Ä",
                "\u00c5" =>"Å",
                "\u00c6" =>"Æ",
                "\u00c7" =>"Ç",
                "\u00c8" =>"È",
                "\u00c9" =>"É",
                "\u00ca" =>"Ê",
                "\u00cb" =>"Ë",
                "\u00cc" =>"Ì",
                "\u00cd" =>"Í",
                "\u00ce" =>"Î",
                "\u00cf" =>"Ï",
                "\u00d1" =>"Ñ",
                "\u00d2" =>"Ò",
                "\u00d3" =>"Ó",
                "\u00d4" =>"Ô",
                "\u00d5" =>"Õ",
                "\u00d6" =>"Ö",
                "\u00d8" =>"Ø",
                "\u00d9" =>"Ù",
                "\u00da" =>"Ú",
                "\u00db" =>"Û",
                "\u00dc" =>"Ü",
                "\u00dd" =>"Ý",
                "\u00df" =>"ß",
                "\u00e0" =>"à",
                "\u00e1" =>"á",
                "\u00e2" =>"â",
                "\u00e3" =>"ã",
                "\u00e4" =>"ä",
                "\u00e5" =>"å",
                "\u00e6" =>"æ",
                "\u00e7" =>"ç",
                "\u00e8" =>"è",
                "\u00e9" =>"é",
                "\u00ea" =>"ê",
                "\u00eb" =>"ë",
                "\u00ec" =>"ì",
                "\u00ed" =>"í",
                "\u00ee" =>"î",
                "\u00ef" =>"ï",
                "\u00f0" =>"ð",
                "\u00f1" =>"ñ",
                "\u00f2" =>"ò",
                "\u00f3" =>"ó",
                "\u00f4" =>"ô",
                "\u00f5" =>"õ",
                "\u00f6" =>"ö",
                "\u00f8" =>"ø",
                "\u00f9" =>"ù",
                "\u00fa" =>"ú",
                "\u00fb" =>"û",
                "\u00fc" =>"ü",
                "\u00fd" =>"ý",
                "\u00ff" =>"ÿ");

            return strtr($text, $utf8_ansi2);
            
        }

        /**
         * Merge the querystring with the new
         * @param $querystring to insert
         * @param $ignore array of querystring to ignore
         * @param $force if true, returns the url without
         * 
         * @return string the new querystring
         */
        public static function MergeQuerystring($querystring, $ignore = array(), $force = false) {

            // Check if ignore is array
            if(!is_array($ignore))
                $ignore = array($ignore);

            // Remove ? from querystring
            $querystring = self::IsNullOrEmpty($querystring) ? "" : ltrim($querystring, '?');
           
            // Get current querystring
            $response = array_values(array_filter(explode("&", $_SERVER['QUERY_STRING'])));

            // Check if ignore
            if(!self::IsNullOrEmpty($ignore))
                foreach ($ignore as $value)
                    $response = array_filter($response, function($item) use ($value) {
                        return strpos($item, "$value=") === false;
                    });

            // Format
            $response = array_values(array_filter(array_merge($response, array($querystring))));

            // Check double params
            if(count($response) > 1) {

                // Init keys and new response
                $keys = array();
                $newResponse = array();
                
                // Explode each response by = and get the first element
                foreach ($response as $value) {
                    $param = explode("=", $value);

                    if(!in_array($param[0], $keys))
                        array_push($keys, $param[0]);
                }

                // Reverse order of response
                $response = array_reverse($response);

                // Get only last inserted
                foreach ($response as $value) {
                    $param = explode("=", $value);

                    // Check if key is already in keys
                    if(in_array($param[0], $keys)) {
                        array_push($newResponse, $value);

                        // Remove from keys
                        $keys = array_filter($keys, function($item) use ($param) {
                            return $item != $param[0];
                        });
                    }
                }

                $response = $newResponse;

            }

            // Merge
            return count($response) > 0 ? "?" . implode("&", $response) : ($force ? strtok($_SERVER["REQUEST_URI"], "?") : "");
        }
        
        /**
         * Generate a random password
         * @param $minlegnt int
         * @param $maxLength int
         * @param $lower boolean
         * @param $upepr boolean
         * @param $numer boolean
         * @param $specia boolean
         * @return string
         */
        public static function randomPWD($minLength = 8, $maxLength = 12, $lower = true, $upper = true, $number = true, $special = true) {

            // Set return string
            $ret = "";

            // Check how many array are used
            $checkArrayUsed = 0;
            if($lower)
                $checkArrayUsed++;
            if($upper)
                $checkArrayUsed++;
            if($number)
                $checkArrayUsed++;
            if ($special)
                $checkArrayUsed++;

            $length = rand($minLength, $maxLength);
            
            // Check if the lenght if valid
            if ($length >= 8 && $checkArrayUsed >= 2) {
                
                // Set the array fro the password generator
                $lower_a = range('a', 'z');
                $upper_a = range('A', 'Z');
                $number_a = range('0', '9');
                $special_a = array('!','@','#','$','%','&','*','(',')','{','}','[',']',',','.','/','?');
        
                $to_draw = array();
                
                // Create the merge of all selected array
                if ($lower)
                    $to_draw = array_merge($to_draw, $lower_a);
        
                if ($upper)
                    $to_draw = array_merge($to_draw, $upper_a);
        
                if ($number)
                    $to_draw = array_merge($to_draw, $number_a);
        
                if ($special)
                    $to_draw = array_merge($to_draw, $special_a);
        
                
                for ($i=0; $i < $length; $i++) { 
                    
                    $ret .= $to_draw[rand(0, count($to_draw) - 1)];
                }

            }
            return $ret;
        }

        /**
         * Generate the name of the session
         * @param $is_remember_me boolean
         * @return string
        */
        public static function getCookieName($is_remember_me = false) {

            // Get the http host
            $http_host = $_SERVER['HTTP_HOST'];

            // Get the active path (to upper)
            $active_path = strtoupper(ltrim(ACTIVE_PATH, "/"));

            // Set the tmp name
            $tmp_name = ($is_remember_me == false ? constant($active_path . "_SESSION") : "REMEMBER_ME_COOKIE_" . $active_path) . "_" . $http_host;

            if ($is_remember_me == false)
                $name = "S" . substr(strtoupper(sha1($tmp_name)), 0, 6) . "S";
            else
                $name = "RM" . substr(strtoupper(sha1($tmp_name)), 0, 10);

            return $name;
        }

        public static function convertForMassive($item, $check_image_tags = false) {

            $response = new stdClass();

            foreach ($item as $key => $value) {

                $string_has_images_tag = false;

                // Check if the value is an html string
                if (!Base_Functions::IsNullOrEmpty($value) && $value != strip_tags($value) && $check_image_tags == true)
                    // Check if the string has images tag
                    $string_has_images_tag = self::HasSubstring($value, "<img");

                // Get value
                if(!Base_Functions::IsNullOrEmpty($value) || $string_has_images_tag) {
                    $value = trim($value);

                    $value = str_replace("\\", "\\\\", trim($value));
                    $value = str_replace("'", "\'", trim($value));
                    $value = str_replace("\\\'", "\\'", trim($value));
                    $value = str_replace("\\\\'", "\\'", trim($value));
                }

                // Check if empty
                if((Base_Functions::IsNullOrEmpty($value) && !$string_has_images_tag) || $value == "0000-00-00" || $value == "0000-00-00 00:00:00")
                    $value = "NULL";

                // Check if string and not number
                elseif(is_string($value) && preg_match("/^\-?[0-9]*\.?[0-9]+\z/", $value) == 0 && (!Base_Functions::IsNullOrEmpty($value) || $string_has_images_tag))
                    $value = "'" . $value . "'";

                $response->{$key} = $value;
            }

            return $response;
        }
        public static function convertForSql($string) {
                
            // Get value
            if(!Base_Functions::IsNullOrEmpty($string)) {
                $string = trim($string);

                $string = str_replace("'", "''", trim($string));
                // $string = str_replace('"', "\"", trim($string));
            }
            

            return $string;
        }

    #endregion

    #region SEO Chars
        /**
         * Strip all special chars
         * @param string the text to transform
         * @return string valid url
         */
        public static function Slug($txt) {
            $return = '';

            if(self::IsNullOrEmpty($txt))
                return $return;

            // Get initial encoding
            $encoding = mb_detect_encoding($txt);

            // Remove emoji and special chars
            $txt = self::RemoveEmojiSpecialChars($txt);

            if(self::IsNullOrEmpty($txt))
                return $return;

            // Check if not UTF-8
            if($encoding != 'UTF-8') {

                // Convert to UTF-8
                $cast = iconv("UTF-8", "ASCII//TRANSLIT", $txt);

                // Check if cast is not empty
                if($cast !== false) $txt = $cast;

            }

            // Replace special chars
            $return = html_entity_decode($txt);
            $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
            $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
            $return = str_replace(' ', '-', $return);
            $return = str_replace('\\', '-', $return);
            $return = str_replace('?', '-', $return);
            $return = str_replace('!', '-', $return);
            $return = str_replace('™', '', $return);
            $return = str_replace('°', '', $return);
            $return = str_replace('®', '', $return);
            $return = str_replace('(', '-', $return);
            $return = str_replace(')', '-', $return);
            $return = str_replace('[', '-', $return);
            $return = str_replace(']', '-', $return);
            $return = str_replace('{', '-', $return);
            $return = str_replace('}', '-', $return);
            $return = str_replace('/', '-', $return);
            $return = str_replace(',', '-', $return);
            $return = str_replace(';', '-', $return);
            $return = str_replace('&#x2f;', '-', $return);
            $return = str_replace('&#x2c;', '-', $return);
            $return = str_replace('&amp;', '-', $return);
            $return = str_replace('&deg;', '', $return);
            $return = str_replace('&trade;', '', $return);
            $return = str_replace('&', '-', $return);
            $return = str_replace('%', '-', $return);
            $return = str_replace(':', '', $return);
            $return = str_replace("'", '-', $return);
            $return = str_replace("’", '-', $return);
            $return = str_replace('"', '', $return);
            $return = str_replace('..', '-', $return);
            $return = str_replace('...', '-', $return);
            $return = str_replace('.', '-', $return);
            $return = str_replace('«', '-', $return);
            $return = str_replace('»', '-', $return);
            $return = str_replace('€', '-', $return);
            $return = str_replace('#', '-', $return);
            $return = str_replace('“', '-', $return);
            $return = str_replace('”', '-', $return);
            
            $return = str_replace('–', '-', $return);
            $return = str_replace('----', '-', $return);
            $return = str_replace('---', '-', $return);
            $return = str_replace('--', '-', $return);
            
            $return = str_replace($a, $b, $return);

            // Remove first -
            $return = ltrim($return, '-');

            // Remove last -
            $return = rtrim($return, '-');

            return strtolower($return);
        }
        private static function RemoveEmojiSpecialChars($string) {
            $string = urldecode($string);

            // Match Enclosed Alphanumeric Supplement
            $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
            $clear_string = preg_replace($regex_alphanumeric, '', $string);

            // Match Miscellaneous Symbols and Pictographs
            $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
            $clear_string = preg_replace($regex_symbols, '', $clear_string);

            // Match Emoticons
            $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
            $clear_string = preg_replace($regex_emoticons, '', $clear_string);

            // Match Transport And Map Symbols
            $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
            $clear_string = preg_replace($regex_transport, '', $clear_string);
            
            // Match Supplemental Symbols and Pictographs
            $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
            $clear_string = preg_replace($regex_supplemental, '', $clear_string);

            // Match Miscellaneous Symbols
            $regex_misc = '/[\x{2600}-\x{26FF}]/u';
            $clear_string = preg_replace($regex_misc, '', $clear_string);

            // Match Dingbats
            $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
            return preg_replace($regex_dingbats, '', $clear_string);
        }
    #endregion

    #region Others

        /**
         * Currency Format
         */
        public static function formatAsCurrency($value, $decimals = '.', $thousands = '', $numberDecimals = 2) {

            if(Base_Functions::IsNullOrEmpty($value))
                $value = "0";
            
            // remove , from 1,000.00
            $value = str_replace(",", "", $value);

            // format number without 
            return number_format($value, $numberDecimals, $decimals, $thousands);
        }

        /**
         * Format the date
         * 
         * @param string $format The format, ex. d/m/Y
         * @param datetime $date The date to convert
         * @param string $interval Ex. +1 days
         * 
         * @return date The new date
        */
        public static function FormatDate($format, $date, $interval = null) {
            $ret = null;

            if (!self::IsNullOrEmpty($date)) {

                // Remove slash
                $date = str_replace("/", "-", $date);

                // Check interval
                if(self::IsNullOrEmpty($interval))
                    $ret = date($format, strtotime($date));

                else
                    $ret = date($format, strtotime(strtolower($interval), strtotime($date)));
            }

            return $ret;
        }

        /**
         * Merge two or more object
         * @param objects to merge
         * 
         * @return object
         */
        public static function mergeObjects() {
            $ret = new stdClass();

            $arg_list = func_get_args();
            foreach ($arg_list as $arg) {
                $ret = (object)array_merge((array)$ret, (array)$arg);
            }

            return $ret;
        }

        /**
         * Get client ip
         */
        public static function get_client_ip() {
            $ipaddress = '';
            if (isset($_SERVER['HTTP_CLIENT_IP']))
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_X_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            else if(isset($_SERVER['HTTP_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            else if(isset($_SERVER['REMOTE_ADDR']))
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            else
                $ipaddress = 'UNKNOWN';
            return $ipaddress;
        }

        /**
         * Example usages:
         *
         * osort($items, 'size');
         * osort($items, ['size', ['time' => SORT_DESC, 'user' => SORT_ASC]]);
         * osort($items, ['size', ['user', 'forname']])
         *
         * @param array $array
         * @param string|array $properties
         */
        public static function osort(&$array, $properties) {
            if (is_string($properties)) {
                $properties = array($properties => SORT_ASC);
            }
            uasort($array, function($a, $b) use ($properties) {
                foreach($properties as $k => $v) {
                    if (is_int($k)) {
                        $k = $v;
                        $v = SORT_ASC;
                    }
                    $collapse = function($node, $props) {
                        if (is_array($props)) {
                            foreach ($props as $prop) {
                                $node = (!isset($node->$prop)) ? null : $node->$prop;
                            }
                            return $node;
                        } else {
                            return (!isset($node->$props)) ? null : $node->$props;
                        }
                    };
                    if (class_exists('\Normalizer')) {
                        $aProp = \Normalizer::normalize($collapse($a, $k), \Normalizer::FORM_D);
                        $bProp = \Normalizer::normalize($collapse($b, $k), \Normalizer::FORM_D);
                    } else {
                        $aProp = $collapse($a, $k);
                        $bProp = $collapse($b, $k);
                    }
                    if ($aProp != $bProp) {
                        return ($v == SORT_ASC)
                            ? strnatcasecmp($aProp, $bProp)
                            : strnatcasecmp($bProp, $aProp);
                    }
                }
                return 0;
            });
        }

        public static function createUniqueOrderNumber($idRef, $type = "order", $payment_status = null) {

            $linq = new Base_LINQHelper();
            $opHelper = new Base_OperationsHelper();

            switch (strtoupper($type)) {

                case 'ORDER':
                    
                    // Set Table name
                    $table = "orders";
                    // Set IdRef name
                    $idRefName = "IdOrder";

                    $numberName = "OrderNumber";

                    break;

                case 'RETURN':
                    
                    // Set Table name
                    $table = "returns";
                    // Set IdRef name
                    $idRefName = "IdReturn";

                    $numberName = "ReturnNumber";

                    break;
                
                default:
                    # code...
                    break;
            }

            // Get data by idRef from table
            $data = $linq->fromDB($table)->whereDB("$idRefName = $idRef")->getFirstOrDefault();

            // Check that the numberName is empty
            if(Base_Functions::IsNullOrEmpty($data->{$numberName})) {

                // Create the format for the orderNumber (Years - number of the day -)
                $format = "#" . date("Y") . '-' . str_pad(date('z') + 1, 3, 0, STR_PAD_LEFT) . '-';

                // Search in the table
                $last_order = $linq->fromDB($table)->whereDB("$numberName like '$format%' ORDER BY $numberName DESC")->getFirstOrDefault();

                // Check if is not empty
                if(!Base_Functions::IsNullOrEmpty($last_order)) {

                    // Get the last number 
                    $last_number = str_replace($format, '', $last_order->{$numberName});
                    // Calculate new number (last + 1)
                    $new_order_number = str_pad($last_number + 1, 5, 0, STR_PAD_LEFT);
                }
                else 
                    $new_order_number = "00001";

                // Creat object
                $obj = new stdClass();
                $obj->IsValid = 1;
                $obj->{$idRefName} = $idRef;
                $obj->{$numberName} = $format . $new_order_number;

                if (!Base_Functions::IsNullOrEmpty($payment_status))
                    $obj->PaymentStatus = $payment_status;

                // Update table
                $opHelper->object($obj)->table($table)->where($idRefName)->update();
            }
        }

    #endregion

    #region Includes/Routes

        /**
         * Autoload files from given path, start and end
         * @param $path to search in
         * @param $fileStartWith starting file name
         * @param $fileEndWith ending file name
         * @param $exclude array of files to exclude 
         */
        public static function autoload_modules($path, $fileStartWith, $fileEndWith, $exclude = []) {

            // Get all files
            $files = glob($path . "*");

            foreach ($files as $file) {

                $filename = basename($file);

                // Check if is a file and start/end with right param
                if (is_file($file) && self::startsWith($filename, $fileStartWith) == true && self::endsWith($filename, $fileEndWith) == true && !in_array($filename, $exclude))
                    include_once($file);

            }
        }

        private static function startsWith($haystack, $needle) {
            return self::IsNullOrEmpty($needle) || strrpos($haystack, $needle, -strlen($haystack)) !== false;
        }

        private static function endsWith($haystack, $needle) {
            return self::IsNullOrEmpty($needle) || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
        }

        /**
         * Used in base-router and base-api
         */
        public static function APIRoutesReorder($routes) {

            // Get object keys and uppercase
            $keys = array_map('strtoupper', array_keys((array)$routes));

            // Set methods
            $methods = ["GET", "POST", "PUT", "DELETE"];

            // Check if keys are methods
            if(count(array_intersect($keys, $methods)) == count($methods))
                return $routes;

            // Init new object
            $new_routes = new stdClass();

            // Loop methods and init object
            foreach ($methods as $method)
                $new_routes->{$method} = new stdClass();

            // Loop routes
            foreach ($routes as $controller => $types) {

                // Loop types
                foreach ($types as $type => $endpoints)
                    $new_routes->{strtoupper($type)}->{$controller} = $endpoints;

            }

            return $new_routes;

        }

        public static function IncludeExternalMethods($path) {

            if (file_exists($path)) {
                include_once($path);

                // Get file contents
                $methods_contents = file_get_contents($path);

                $namespace = null;

                // Get namespace
                if (preg_match('#(namespace)(\\s+)([A-Za-z0-9_\\\\]+?)(\\s*);#sm', $methods_contents, $m))
                    $namespace = $m[3];

                // Format class name with namespace
                $className = "$namespace\Methods";

                // Check if class exists
                if($namespace && class_exists($className))
                    return new $className();
            }

            return null;

        }

    #endregion

    #region Files

        /**
         * Recoursive remove all files in a folder
         */
        public static function deleteFiles($path) {

            if(file_exists($path)) {

                $path = rtrim($path, "/") . "/";

                // get all files in folder
                $files = scandir($path);
              
                // Remove useless paths
                if (($key = array_search(".", $files)) !== false) { unset($files[$key]); }
                if (($key = array_search("..", $files)) !== false) { unset($files[$key]); }
    
                foreach ($files as $key => $file) {
                      
                    // if folder delete files in it
                    if (is_dir($path . $file)) {
                        self::deleteFiles($path . $file . "/");
    
                        if(is_dir($path . $file . "/"))
                            rmdir($path . $file . "/");
                    }
                    else
                        unlink($path . $file);
                }
    
                if(is_dir($path) && count(scandir($path)) == 2)
                    rmdir($path);
            }

        }

        public static function getPlaceholders($text, $start = "@@", $end = "@@") {

            $res = array();
            $ini = 0;

            // Get all $this-> properties
            while (is_numeric($ini)) {
                $ini = strpos($text, $start, $ini);

                if (is_numeric($ini)) {
                    $ini += strlen($start);
                    $len = strpos($text, $end, $ini) - $ini;

                    $sub = substr($text, $ini, $len);

                    // If has not spaces, is less than 15 chars and not already in res
                    if ($sub != false && !preg_match('/\s/',$sub) && !in_array($sub, $res))
                        array_push($res, $sub);
                    
                    $ini+= $len + 2;
                }
            }

            return $res;
        }

        /**
         * Convert MIME to file extension
         * @param $mime to convert
         * 
         * @return string file extension
         */
        public static function Mime2Ext($mime) {
            $mime_map = [
                'video/3gpp2'                                                               => '3g2',
                'video/3gp'                                                                 => '3gp',
                'video/3gpp'                                                                => '3gp',
                'application/x-compressed'                                                  => '7zip',
                'audio/x-acc'                                                               => 'aac',
                'audio/ac3'                                                                 => 'ac3',
                'application/postscript'                                                    => 'ai',
                'audio/x-aiff'                                                              => 'aif',
                'audio/aiff'                                                                => 'aif',
                'audio/x-au'                                                                => 'au',
                'video/x-msvideo'                                                           => 'avi',
                'video/msvideo'                                                             => 'avi',
                'video/avi'                                                                 => 'avi',
                'application/x-troff-msvideo'                                               => 'avi',
                'application/macbinary'                                                     => 'bin',
                'application/mac-binary'                                                    => 'bin',
                'application/x-binary'                                                      => 'bin',
                'application/x-macbinary'                                                   => 'bin',
                'image/bmp'                                                                 => 'bmp',
                'image/x-bmp'                                                               => 'bmp',
                'image/x-bitmap'                                                            => 'bmp',
                'image/x-xbitmap'                                                           => 'bmp',
                'image/x-win-bitmap'                                                        => 'bmp',
                'image/x-windows-bmp'                                                       => 'bmp',
                'image/ms-bmp'                                                              => 'bmp',
                'image/x-ms-bmp'                                                            => 'bmp',
                'application/bmp'                                                           => 'bmp',
                'application/x-bmp'                                                         => 'bmp',
                'application/x-win-bitmap'                                                  => 'bmp',
                'application/cdr'                                                           => 'cdr',
                'application/coreldraw'                                                     => 'cdr',
                'application/x-cdr'                                                         => 'cdr',
                'application/x-coreldraw'                                                   => 'cdr',
                'image/cdr'                                                                 => 'cdr',
                'image/x-cdr'                                                               => 'cdr',
                'zz-application/zz-winassoc-cdr'                                            => 'cdr',
                'application/mac-compactpro'                                                => 'cpt',
                'application/pkix-crl'                                                      => 'crl',
                'application/pkcs-crl'                                                      => 'crl',
                'application/x-x509-ca-cert'                                                => 'crt',
                'application/pkix-cert'                                                     => 'crt',
                'text/css'                                                                  => 'css',
                'text/x-comma-separated-values'                                             => 'csv',
                'text/comma-separated-values'                                               => 'csv',
                'application/vnd.msexcel'                                                   => 'csv',
                'application/x-director'                                                    => 'dcr',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
                'application/x-dvi'                                                         => 'dvi',
                'message/rfc822'                                                            => 'eml',
                'application/x-msdownload'                                                  => 'exe',
                'video/x-f4v'                                                               => 'f4v',
                'audio/x-flac'                                                              => 'flac',
                'video/x-flv'                                                               => 'flv',
                'image/gif'                                                                 => 'gif',
                'application/gpg-keys'                                                      => 'gpg',
                'application/x-gtar'                                                        => 'gtar',
                'application/x-gzip'                                                        => 'gzip',
                'application/mac-binhex40'                                                  => 'hqx',
                'application/mac-binhex'                                                    => 'hqx',
                'application/x-binhex40'                                                    => 'hqx',
                'application/x-mac-binhex40'                                                => 'hqx',
                'text/html'                                                                 => 'html',
                'image/x-icon'                                                              => 'ico',
                'image/x-ico'                                                               => 'ico',
                'image/vnd.microsoft.icon'                                                  => 'ico',
                'text/calendar'                                                             => 'ics',
                'application/java-archive'                                                  => 'jar',
                'application/x-java-application'                                            => 'jar',
                'application/x-jar'                                                         => 'jar',
                'image/jp2'                                                                 => 'jp2',
                'video/mj2'                                                                 => 'jp2',
                'image/jpx'                                                                 => 'jp2',
                'image/jpm'                                                                 => 'jp2',
                'image/jpeg'                                                                => 'jpeg',
                'image/jpg'                                                                 => 'jpg',
                'image/pjpeg'                                                               => 'jpeg',
                'image/webp'                                                               => 'webp',
                'application/x-javascript'                                                  => 'js',
                'application/json'                                                          => 'json',
                'text/json'                                                                 => 'json',
                'application/vnd.google-earth.kml+xml'                                      => 'kml',
                'application/vnd.google-earth.kmz'                                          => 'kmz',
                'text/x-log'                                                                => 'log',
                'audio/x-m4a'                                                               => 'm4a',
                'application/vnd.mpegurl'                                                   => 'm4u',
                'audio/midi'                                                                => 'mid',
                'application/vnd.mif'                                                       => 'mif',
                'video/quicktime'                                                           => 'mov',
                'video/x-sgi-movie'                                                         => 'movie',
                'audio/mpeg'                                                                => 'mp3',
                'audio/mpg'                                                                 => 'mp3',
                'audio/mpeg3'                                                               => 'mp3',
                'audio/mp3'                                                                 => 'mp3',
                'video/mp4'                                                                 => 'mp4',
                'video/mpeg'                                                                => 'mpeg',
                'application/oda'                                                           => 'oda',
                'audio/ogg'                                                                 => 'ogg',
                'video/ogg'                                                                 => 'ogg',
                'application/ogg'                                                           => 'ogg',
                'application/x-pkcs10'                                                      => 'p10',
                'application/pkcs10'                                                        => 'p10',
                'application/x-pkcs12'                                                      => 'p12',
                'application/x-pkcs7-signature'                                             => 'p7a',
                'application/pkcs7-mime'                                                    => 'p7c',
                'application/x-pkcs7-mime'                                                  => 'p7c',
                'application/x-pkcs7-certreqresp'                                           => 'p7r',
                'application/pkcs7-signature'                                               => 'p7s',
                'application/pdf'                                                           => 'pdf',
                'application/octet-stream'                                                  => 'pdf',
                'application/x-x509-user-cert'                                              => 'pem',
                'application/x-pem-file'                                                    => 'pem',
                'application/pgp'                                                           => 'pgp',
                'application/x-httpd-php'                                                   => 'php',
                'application/php'                                                           => 'php',
                'application/x-php'                                                         => 'php',
                'text/php'                                                                  => 'php',
                'text/x-php'                                                                => 'php',
                'application/x-httpd-php-source'                                            => 'php',
                'image/png'                                                                 => 'png',
                'image/x-png'                                                               => 'png',
                'application/powerpoint'                                                    => 'ppt',
                'application/vnd.ms-powerpoint'                                             => 'ppt',
                'application/vnd.ms-office'                                                 => 'ppt',
                'application/msword'                                                        => 'doc',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'application/x-photoshop'                                                   => 'psd',
                'image/vnd.adobe.photoshop'                                                 => 'psd',
                'audio/x-realaudio'                                                         => 'ra',
                'audio/x-pn-realaudio'                                                      => 'ram',
                'application/x-rar'                                                         => 'rar',
                'application/rar'                                                           => 'rar',
                'application/x-rar-compressed'                                              => 'rar',
                'audio/x-pn-realaudio-plugin'                                               => 'rpm',
                'application/x-pkcs7'                                                       => 'rsa',
                'text/rtf'                                                                  => 'rtf',
                'text/richtext'                                                             => 'rtx',
                'video/vnd.rn-realvideo'                                                    => 'rv',
                'application/x-stuffit'                                                     => 'sit',
                'application/smil'                                                          => 'smil',
                'text/srt'                                                                  => 'srt',
                'image/svg+xml'                                                             => 'svg',
                'application/x-shockwave-flash'                                             => 'swf',
                'application/x-tar'                                                         => 'tar',
                'application/x-gzip-compressed'                                             => 'tgz',
                'image/tiff'                                                                => 'tiff',
                'text/plain'                                                                => 'txt',
                'text/x-vcard'                                                              => 'vcf',
                'application/videolan'                                                      => 'vlc',
                'text/vtt'                                                                  => 'vtt',
                'audio/x-wav'                                                               => 'wav',
                'audio/wave'                                                                => 'wav',
                'audio/wav'                                                                 => 'wav',
                'application/wbxml'                                                         => 'wbxml',
                'video/webm'                                                                => 'webm',
                'audio/x-ms-wma'                                                            => 'wma',
                'application/wmlc'                                                          => 'wmlc',
                'video/x-ms-wmv'                                                            => 'wmv',
                'video/x-ms-asf'                                                            => 'wmv',
                'application/xhtml+xml'                                                     => 'xhtml',
                'application/excel'                                                         => 'xl',
                'application/msexcel'                                                       => 'xls',
                'application/x-msexcel'                                                     => 'xls',
                'application/x-ms-excel'                                                    => 'xls',
                'application/x-excel'                                                       => 'xls',
                'application/x-dos_ms_excel'                                                => 'xls',
                'application/xls'                                                           => 'xls',
                'application/x-xls'                                                         => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
                'application/vnd.ms-excel'                                                  => 'xlsx',
                'application/xml'                                                           => 'xml',
                'text/xml'                                                                  => 'xml',
                'text/xsl'                                                                  => 'xsl',
                'application/xspf+xml'                                                      => 'xspf',
                'application/x-compress'                                                    => 'z',
                'application/x-zip'                                                         => 'zip',
                'application/zip'                                                           => 'zip',
                'application/x-zip-compressed'                                              => 'zip',
                'application/s-compressed'                                                  => 'zip',
                'multipart/x-zip'                                                           => 'zip',
                'text/x-scriptzsh'                                                          => 'zsh',
            ];

            return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
        }

        /**
         * Convert file extension to MIME
         * @param $extension to convert
         * 
         * @return string MIME
         */
        public static function Ext2Mime($ext) {
            $mime_map = [
                '3g2' => 'video/3gpp2',
                '3gp' => 'video/3gp',
                '3gp' => 'video/3gpp',
                '7zip' => 'application/x-compressed',
                'aac' => 'audio/x-acc',
                'ac3' => 'audio/ac3',
                'ai' => 'application/postscript',
                'aif' => 'audio/x-aiff',
                'aif' => 'audio/aiff',
                'au' => 'audio/x-au',
                'avi' => 'video/x-msvideo',
                'avi' => 'video/msvideo',
                'avi' => 'video/avi',
                'avi' => 'application/x-troff-msvideo',
                'bin' => 'application/macbinary',
                'bin' => 'application/mac-binary',
                'bin' => 'application/x-binary',
                'bin' => 'application/x-macbinary',
                'bmp' => 'image/bmp',
                'bmp' => 'image/x-bmp',
                'bmp' => 'image/x-bitmap',
                'bmp' => 'image/x-xbitmap',
                'bmp' => 'image/x-win-bitmap',
                'bmp' => 'image/x-windows-bmp',
                'bmp' => 'image/ms-bmp',
                'bmp' => 'image/x-ms-bmp',
                'bmp' => 'application/bmp',
                'bmp' => 'application/x-bmp',
                'bmp' => 'application/x-win-bitmap',
                'cdr' => 'application/cdr',
                'cdr' => 'application/coreldraw',
                'cdr' => 'application/x-cdr',
                'cdr' => 'application/x-coreldraw',
                'cdr' => 'image/cdr',
                'cdr' => 'image/x-cdr',
                'cdr' => 'zz-application/zz-winassoc-cdr',
                'cpt' => 'application/mac-compactpro',
                'crl' => 'application/pkix-crl',
                'crl' => 'application/pkcs-crl',
                'crt' => 'application/x-x509-ca-cert',
                'crt' => 'application/pkix-cert',
                'css' => 'text/css',
                'csv' => 'text/x-comma-separated-values',
                'csv' => 'text/comma-separated-values',
                'csv' => 'application/vnd.msexcel',
                'dcr' => 'application/x-director',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'dvi' => 'application/x-dvi',
                'eml' => 'message/rfc822',
                'exe' => 'application/x-msdownload',
                'f4v' => 'video/x-f4v',
                'flac' => 'audio/x-flac',
                'flv' => 'video/x-flv',
                'gif' => 'image/gif',
                'gpg' => 'application/gpg-keys',
                'gtar' => 'application/x-gtar',
                'gzip' => 'application/x-gzip',
                'hqx' => 'application/mac-binhex40',
                'hqx' => 'application/mac-binhex',
                'hqx' => 'application/x-binhex40',
                'hqx' => 'application/x-mac-binhex40',
                'html' => 'text/html',
                'ico' => 'image/x-icon',
                'ico' => 'image/x-ico',
                'ico' => 'image/vnd.microsoft.icon',
                'ics' => 'text/calendar',
                'jar' => 'application/java-archive',
                'jar' => 'application/x-java-application',
                'jar' => 'application/x-jar',
                'jp2' => 'image/jp2',
                'jp2' => 'video/mj2',
                'jp2' => 'image/jpx',
                'jp2' => 'image/jpm',
                'jpeg' => 'image/jpeg',
                'jpeg' => 'image/pjpeg',
                'webp' => 'image/webp',
                'jpg' => 'image/jpg',
                'js' => 'application/x-javascript',
                'json' => 'application/json',
                'json' => 'text/json',
                'kml' => 'application/vnd.google-earth.kml+xml',
                'kmz' => 'application/vnd.google-earth.kmz',
                'log' => 'text/x-log',
                'm4a' => 'audio/x-m4a',
                'm4u' => 'application/vnd.mpegurl',
                'mid' => 'audio/midi',
                'mif' => 'application/vnd.mif',
                'mov' => 'video/quicktime',
                'movie' => 'video/x-sgi-movie',
                'mp3' => 'audio/mpeg',
                'mp3' => 'audio/mpg',
                'mp3' => 'audio/mpeg3',
                'mp3' => 'audio/mp3',
                'mp4' => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'oda' => 'application/oda',
                'ogg' => 'audio/ogg',
                'ogg' => 'video/ogg',
                'ogg' => 'application/ogg',
                'p10' => 'application/x-pkcs10',
                'p10' => 'application/pkcs10',
                'p12' => 'application/x-pkcs12',
                'p7a' => 'application/x-pkcs7-signature',
                'p7c' => 'application/pkcs7-mime',
                'p7c' => 'application/x-pkcs7-mime',
                'p7r' => 'application/x-pkcs7-certreqresp',
                'p7s' => 'application/pkcs7-signature',
                'pdf' => 'application/pdf',
                'pdf' => 'application/octet-stream',
                'pem' => 'application/x-x509-user-cert',
                'pem' => 'application/x-pem-file',
                'pgp' => 'application/pgp',
                'php' => 'application/x-httpd-php',
                'php' => 'application/php',
                'php' => 'application/x-php',
                'php' => 'text/php',
                'php' => 'text/x-php',
                'php' => 'application/x-httpd-php-source',
                'png' => 'image/png',
                'png' => 'image/x-png',
                'ppt' => 'application/powerpoint',
                'ppt' => 'application/vnd.ms-powerpoint',
                'ppt' => 'application/vnd.ms-office',
                'doc' => 'application/msword',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'psd' => 'application/x-photoshop',
                'psd' => 'image/vnd.adobe.photoshop',
                'ra' => 'audio/x-realaudio',
                'ram' => 'audio/x-pn-realaudio',
                'rar' => 'application/x-rar',
                'rar' => 'application/rar',
                'rar' => 'application/x-rar-compressed',
                'rpm' => 'audio/x-pn-realaudio-plugin',
                'rsa' => 'application/x-pkcs7',
                'rtf' => 'text/rtf',
                'rtx' => 'text/richtext',
                'rv' => 'video/vnd.rn-realvideo',
                'sit' => 'application/x-stuffit',
                'smil' => 'application/smil',
                'srt' => 'text/srt',
                'svg' => 'image/svg+xml',
                'swf' => 'application/x-shockwave-flash',
                'tar' => 'application/x-tar',
                'tgz' => 'application/x-gzip-compressed',
                'tiff' => 'image/tiff',
                'txt' => 'text/plain',
                'vcf' => 'text/x-vcard',
                'vlc' => 'application/videolan',
                'vtt' => 'text/vtt',
                'wav' => 'audio/x-wav',
                'wav' => 'audio/wave',
                'wav' => 'audio/wav',
                'wbxml' => 'application/wbxml',
                'webm' => 'video/webm',
                'wma' => 'audio/x-ms-wma',
                'wmlc' => 'application/wmlc',
                'wmv' => 'video/x-ms-wmv',
                'wmv' => 'video/x-ms-asf',
                'xhtml' => 'application/xhtml+xml',
                'xl' => 'application/excel',
                'xls' => 'application/msexcel',
                'xls' => 'application/x-msexcel',
                'xls' => 'application/x-ms-excel',
                'xls' => 'application/x-excel',
                'xls' => 'application/x-dos_ms_excel',
                'xls' => 'application/xls',
                'xls' => 'application/x-xls',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xlsx' => 'application/vnd.ms-excel',
                'xml' => 'application/xml',
                'xml' => 'text/xml',
                'xsl' => 'text/xsl',
                'xspf' => 'application/xspf+xml',
                'z' => 'application/x-compress',
                'zip' => 'application/x-zip',
                'zip' => 'application/zip',
                'zip' => 'application/x-zip-compressed',
                'zip' => 'application/s-compressed',
                'zip' => 'multipart/x-zip',
                'zsh' => 'text/x-scriptzsh',
            ];

            return isset($mime_map[$ext]) === true ? $mime_map[$ext] : false;
        }

        /**
         * Get file extension from path
         * @param $path
         * 
         * @return string file extension
         */
        public static function Path2Ext($path) {
            
            // Get filename
            $filename = basename($path);

            // Get index of last .
            $index = strrpos($filename, ".");

            // Get last
            return substr($filename, $index);

        }

        /**
         * Generate file name
         * @param $name current file name
         * 
         * @return string new filename
         */
        public static function FileName($name) {

            // Get extension
            $ext = self::Path2Ext($name);
            
            // Get current datetime
            $datetime = self::FormatDate("YmdHis", date("Y-m-d H:i:s"));

            // Get UniqueToken
            $token = substr(self::UniqueToken(), 0, 10);

            // Return new filename
            return "$datetime$token$ext";

        }

    #endregion

}