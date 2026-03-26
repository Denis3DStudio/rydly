<?php

    namespace Render\AttributeValue;

    use Base_Countries;
    use Base_Functions;
    use Base_Render;
    use stdClass;

    class Methods extends Base_Render {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function index() {   

                return $this->common();
            }
            public function manage() {   

                return $this->common();
            }

        #endregion

        #region Private Methods

            private function common() {

                // Create the request
                $obj = new stdClass();
                $obj->IdAttribute = $this->Params->IdAttribute;

                // Get the attribute value
                $attribute = $this->api("Attribute", $obj);

                // Check if Success
                if ($this->Success == true) {

                    // Set the from
                    $attribute->From = property_exists($this->Params, "from") ? $this->Params->from : null;
                    return $attribute;
                }

                return false;
            }

        #endregion
    }

?>