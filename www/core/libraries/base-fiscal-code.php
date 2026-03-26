<?php

    class Base_FiscalCode {
        
        /**
         * Build fiscal code
         * @param $name string
         * @param $surname string
         * @param $year int
         * @param $month int
         * @param $day int
         * @param $sex string M/F
         * @param $municipality string
         */
        public static function getFiscalCode($name, $surname, $year, $month, $day, $sex, $municipality) {

            // Calcolo del codice per il surname
            $codeSurname = self::getCodeSurname($surname);
            
            // Calcolo del codice per il name
            $codeName = self::getCodeName($name);
            
            // Calcolo del codice per l'anno di nascita
            $codeYear = substr($year, -2);
            
            // Calcolo del codice per il month di nascita
            $codeMonth = self::getCodeMonth($month);
            
            // Calcolo del codice per il giorno di nascita
            $codeDay = self::getCodeDay($day, $sex);
            
            // Calcolo del carattere di controllo
            $codeControl = self::getCodeControl($codeSurname . $codeName . $codeYear . $codeMonth . $codeDay . $municipality);
            
            // Costruzione del codice fiscale completo
            $codiceFiscale = $codeSurname . $codeName . $codeYear . $codeMonth . $codeDay . $municipality . $codeControl;
            
            return $codiceFiscale;
        }
        /**
         * Build the surname Code from the surname
         * @param $surname string
         */
        private static function getCodeSurname($surname) {
            $vocali = "AEIOUaeiou";
            $consonanti = "BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxyz";
            $listaVocali = [];
            $listaConsonanti = [];
        
            // Separazione delle vocali e consonanti
            for ($i = 0; $i < strlen($surname); $i++) {
                $char = $surname[$i];
                if (strpos($vocali, $char) !== false) {
                    array_push($listaVocali, $char);
                } elseif (strpos($consonanti, $char) !== false) {
                    array_push($listaConsonanti, $char);
                }
            }
        
            $codice = '';
        
            if (count($listaConsonanti) >= 3) {
                $codice .= $listaConsonanti[0] . $listaConsonanti[1] . $listaConsonanti[2];
            } elseif (count($listaConsonanti) == 2) {
                $codice .= $listaConsonanti[0] . $listaConsonanti[1] . $listaVocali[0];
            } elseif (count($listaConsonanti) == 1 && count($listaVocali) >= 2) {
                $codice .= $listaConsonanti[0] . $listaVocali[0] . $listaVocali[1];
            } elseif (count($listaConsonanti) == 1 && count($listaVocali) == 1) {
                $codice .= $listaConsonanti[0] . $listaVocali[0] . 'X';
            } elseif (strlen($surname) == 2 && ctype_alpha($surname) && strcspn($surname, $consonanti) == strlen($surname)) {
                $codice = $surname[0] . $surname[1] . 'X';
            } else {
                $codice = str_pad("", 3, "X"); // Se il surname non rientra in nessuna delle categorie precedenti
            }
        
            return strtoupper($codice);
        }
        /**
         * Build the name Code from the name
         * @param $name string
         */
        private static function getCodeName($name) {
            $vocali = "AEIOUaeiou";
            $consonanti = "BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxyz";
            $listaVocali = [];
            $listaConsonanti = [];
        
            // Separazione delle vocali e consonanti
            for ($i = 0; $i < strlen($name); $i++) {
                $char = $name[$i];
                if (strpos($vocali, $char) !== false) {
                    array_push($listaVocali, $char);
                } elseif (strpos($consonanti, $char) !== false) {
                    array_push($listaConsonanti, $char);
                }
            }
        
            $codice = '';
            if (count($listaConsonanti) >= 4) {
                $codice .= $listaConsonanti[0] . $listaConsonanti[2] . $listaConsonanti[3];
            } elseif (count($listaConsonanti) == 3) {
                $codice .= implode('', array_slice($listaConsonanti, 0, 3));
            } elseif (count($listaConsonanti) == 2) {
                $codice .= $listaConsonanti[0] . $listaConsonanti[1] . $listaVocali[0];
            } elseif (count($listaConsonanti) == 1) {
                if (count($listaVocali) > 0) {
                    $codice .= $listaConsonanti[0] . $listaVocali[0];
                    $codice .= count($listaVocali) > 1 ? $listaVocali[1] : 'X';
                } else {
                    $codice .= $listaConsonanti[0] . 'XX';
                }
            } else {
                $codice = str_pad("", 3, "X"); // Se il name non rientra in nessuna delle categorie precedenti
            }
        
            return strtoupper($codice);
        }
        /**
         * Build the year Code from the year
         * @param $year int
         */
        private static function getCodeYear($year) {
            return substr($year, -2);
        }
        private static function getCodeMunicipality($municipality) {
            return  true;
        }
        /**
         * Build the month Code from the month
         * @param $month int
         */
        private static function getCodeMonth($month) {
            $months = [
                1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'H',
                7 => 'L', 8 => 'M', 9 => 'P', 10 => 'R', 11 => 'S', 12 => 'T'
            ];
            
            return $months[$month];
        }
        /**
         * Build the day Code from the day and sex
         * @param $day int
         * @param $sex string M/F
         */
        private static function getCodeDay($day, $sex) {

            // Calculate the day code

            // Check if day have 0...
            $codeDay = ($day < 10) ? '0' . $day : $day;

            // Add 40 if women
            $codeDay = $sex === 'M' ? $codeDay : $codeDay + 40;

            return $codeDay;

        }
        /**
         * Build the contol Code from the semiFiscalCode
         * @param $codePartial int
         */
        private static function getCodeControl($codePartial) {
            $tabellaC = ['0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
                        'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9,
                        'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18,
                        'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25];
            
            $tabellaD = ['0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
                        'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
                        'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12,
                        'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23];
            
            // Conversione dei caratteri in valori numerici
            $valori = [];
            for ($i = 0; $i < strlen($codePartial); $i++) {
                if ($i % 2 == 0) {
                    $valori[] = $tabellaC[$codePartial[$i]];
                } else {
                    $valori[] = $tabellaD[$codePartial[$i]];
                }
            }
            
            // Calcolo del carattere di controllo
            $somma = array_sum($valori);
            $resto = $somma % 26;
            
            $tabellaE = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
            $codeControl = $tabellaE[$resto];
            
            return $codeControl;
        }
        /**
         * Slipt the fiscal code into codes
         * @param $fiscalCode string(16)
         */
        private static function getFiscalCodeSplit($fiscalCode) {
            
            // Build the response 
            $response = new stdClass();

            // Get all the parts of the fiscal code
            $response->CodeSurname = substr($fiscalCode, 0, 3);
            $response->CodeName = substr($fiscalCode, 3, 3);
            $response->CodeYear = substr($fiscalCode, 6, 2);
            $response->CodeMonth = substr($fiscalCode, 8, 1);
            $response->CodeDay = substr($fiscalCode, 9, 2);
            $response->CodeMunicipality = substr($fiscalCode, 11, 4);
            $response->CodeCheck = substr($fiscalCode, 15, 1);

            return $response;

        }
        /**
         * Check the fiscal code with the infos of the person
         * @param $fiscalCode string(16)
         * @param $name string
         * @param $surname string
         * @param $year int
         * @param $month int
         * @param $day int
         * @param $sex string M/F
         * @param $municipality string
         */
        public static function checkFiscalCode($fiscalCode = null, $name = null, $surname = null, $year = null, $month = null,  $day = null, $sex = null, $municipality = null) {

            // Check if fiscal code null or empty
            if(Base_Functions::IsNullOrEmpty($fiscalCode) || strlen($fiscalCode) < 16) 
                return false;

            // Get fiscal code splitted
            $fc = self::getFiscalCodeSplit($fiscalCode);   

            // Check the name 
            if(!Base_Functions::IsNullOrEmpty($name)) 
                if(self::getCodeName($name) != $fc->CodeName)
                    return false;

            // Check the surname
            if(!Base_Functions::IsNullOrEmpty($surname)) 
                if(self::getCodeSurname($surname) != $fc->CodeSurname)
                    return false;

            // Check the year
            if(!Base_Functions::IsNullOrEmpty($year)) 
                if(self::getCodeYear($year) != $fc->CodeYear)
                    return false;

            // Check the month            
            if(!Base_Functions::IsNullOrEmpty($month)) 
                if(self::getCodeMonth($month) != $fc->CodeMonth)
                    return false;

            // Check the day            
            if(!Base_Functions::IsNullOrEmpty($day)) 
                if(self::getCodeDay($day, $sex) != $fc->CodeDay)
                    return false;

            // Check the municipality
            if(!Base_Functions::IsNullOrEmpty($municipality)) 
                if(self::getCodeMunicipality($municipality) != $fc->CodeMunicipality)
                    return false;
            
            // If ok return success
            return true;
        }

    }