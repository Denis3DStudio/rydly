<?php 

    class Base_Methods extends Response {

        #region Properties
        
            public $Logged;
            public $Request;
        
            public $__linq;
            public $__opHelper;
        
            public $__external_props;
            public $__partialPath;

            private $__keyword = "CONNECTOR_TO_INIT";

        #endregion

        #region Constructors-Destructors

            public function __construct() {
                parent::__construct();

                // Init
                $this->__external_props = new stdClass();
                $this->getPartialPath();

                // Init external API
                $this->initExternalAPI();
            }
            public function __destruct() { 
            }

        #endregion

        #region Set/Get

            public function __set($name, $value) {
                $this->__external_props->{$name} = $value;
            }
            public function __get($name) {

                // Check if already exists
                if(property_exists($this->__external_props, $name)) {

                    // Check if connector to init
                    if(is_string($this->__external_props->{$name}) && $this->__external_props->{$name} == $this->__keyword) {

                        // Init
                        $this->__external_props->{$name} = new Base_Methods();

                        // Set base properties
                        $this->initMethodBaseProperties($this->__external_props->{$name}, false);

                        // Set partial path
                        $this->__external_props->{$name}->__partialPath = $name;

                        // Unset
                        unset($this->__external_props->{$name}->__external_props->{$name});
                    }

                    // Set base properties
                    $this->initMethodBaseProperties($this->__external_props->{$name});

                    return $this->__external_props->{$name};
                }

                // Build path
                $path = $_SERVER["DOCUMENT_ROOT"] . "/api/endpoints/{$this->__partialPath}/" . strtolower($name) . "/methods.php";

                // Get instance
                $instance = Base_Functions::IncludeExternalMethods($path);

                // Set base properties
                $this->initMethodBaseProperties($instance);

                // Set partial path
                $instance->__partialPath = $this->__partialPath;

                // Set to external props
                $this->__external_props->{$name} = $instance;

                return $instance;
            }

        #endregion

        #region Public Methods

            public function call($request, $base = "", $method = "POST") {

                if(!Base_Functions::IsNullOrEmpty(API_ENDPOINT))
                    return $this->Bad_Request("API endpoint is not defined.");

                $curl = new Base_Curl();

                // Set default
                $curl->setUrl(API_ENDPOINT . $base)
                    ->setType($method);

                // Check body
                if($request != null)
                    $curl->setObject($request);

                // Call data
                $response = $curl->call();
                
                return $this->Success($response);
            }
    
            // Pagination
            public function getPagination($current_page, $total, $limit) {

                // Init the pagination
                $pagination = new stdClass();

                // Set the page
                $pagination->Page = $current_page;
                // Get the number of page
                $pagination->Pages = ceil($total / $limit);
                // Add if to show the next page
                $pagination->ShowNext = $current_page < $pagination->Pages;
                // Add if to show the previous page
                $pagination->ShowPrevious = $current_page > 1;

                return $pagination;
            }

            public function checkImagePath($path) {

                // Check if file exists
                if(!Base_Functions::IsNullOrEmpty($path) && file_exists($_SERVER["DOCUMENT_ROOT"] . $path))
                    return $path;

                // Return default image
                return "/assets/backend/img/img-default.png";
            }

            #region Permission

                public function checkIfLoggedCan($idOrganization) {

                    // Check if the user is logged
                    if (Base_Functions::IsNullOrEmpty($this->Logged))
                        return false;

                    // Check if the user is super admin
                    if ($this->Logged->IdRole == Base_Account::SUPERADMIN)
                        return true;

                    // Get the permission for the organization
                    $loggedIdOrganization = property_exists($this->Logged, "IdOrganization") ? $this->Logged->IdOrganization : null;

                    // Check if the user has the permission
                    return !Base_Functions::IsNullOrEmpty($loggedIdOrganization) && $loggedIdOrganization == $idOrganization;
                }
                public function buildOrganizationWhere($tableAlias = null) {

                    // Check if the user is logged
                    if (Base_Functions::IsNullOrEmpty($this->Logged))
                        return " AND 1 = 0";

                    // Check if the user is super admin
                    if (in_array($this->Logged->IdRole, Base_Account::FULL_ACCESS))
                        return '';

                    // Build the where condition
                    $alias = !Base_Functions::IsNullOrEmpty($tableAlias) ? $tableAlias . "." : "";
                    $where = " AND (" . $alias . "IdOrganization = " . $this->Logged->IdOrganization . ")";

                    return $where;
                }

            #endregion

            #region Categories

                public function buildCategoryWhere($idType, $idsCategories, $contentComparison) {

                    // Check if the categories filter is not empty
                    if (Base_Functions::IsNullOrEmpty($idsCategories))
                        return "";

                    // Convert in int
                    $idsCategories = array_map('intval', $idsCategories);

                    // Sort the array ASC
                    sort($idsCategories);

                    // Convert in string
                    $idsCategories = implode(",", $idsCategories);

                    // Build the where condition
                    $where = " AND (SELECT GROUP_CONCAT(rf.IdCategory ORDER BY rf.IdCategory)
                                            FROM refs_categories rf
                                            WHERE rf.ContentRefId = $contentComparison AND rf.IdType = $idType AND rf.IdCategory IN($idsCategories)) = '$idsCategories'";

                    return $where;
                }

            #endregion

        #endregion

        #region Private Methods

            private function initExternalAPI() {
                
                // Get api folders
                $folders = glob($_SERVER["DOCUMENT_ROOT"] . "/api/endpoints/*", GLOB_ONLYDIR);

                // Get current path
                $base = $this->__partialPath;

                // Get only the folder name and exclude itself
                $folders = array_values(array_filter(array_map(function($item) use ($base) {

                    // Get the folder name
                    $folder = basename($item);

                    // Return
                    return $folder != $base ? $folder : null;

                }, $folders)));

                // Init
                foreach ($folders as $folder)
                    $this->{$folder} = $this->__keyword;

            }

            private function initMethodBaseProperties(&$instance, $ignoreIfEmpty = true) {

                if(Base_Functions::IsNullOrEmpty($instance) && $ignoreIfEmpty)
                    return;

                // Set main
                $instance->Logged = $this->Logged;
                $instance->Request = $this->Request;

                // Utils
                $instance->__linq = new Base_LINQHelper();
                $instance->__opHelper = new Base_OperationsHelper();
                $instance->__opHelper->__setCreator(!Base_Functions::IsNullOrEmpty($instance->Logged) ? $instance->Logged->IdAccount : null);

            }

            private function getPartialPath() {

                // Get current path
                $this->__partialPath = $_REQUEST["b"];

                // Get current class name
                $class = get_class($this);

                // Check if has 3 parts (e.g. Frontend\Utility\Methods)
                if(substr_count($class, "\\") == 2) {
                    
                    // Get the folder name
                    $folder = explode("\\", $class)[0];

                    // Build path
                    $this->__partialPath = strtolower(ltrim($folder, "\\"));

                }

            }
            
        #endregion

    }