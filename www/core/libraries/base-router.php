<?php

    class Base_Router extends Base_Custom_Router {

        #region Properties

            public $__API = [];
            public $__API_requests = [];

            public $__route = null;
            private $__route_custom_props = [];
        
            private $__routes = [];
            private $__layouts = [];
            private $__components = [];

            private $__styles_to_include = [];
            private $__scripts_to_include = [];
            
            private $__pages = [];

            private $Logged = null;

        #endregion

        #region Constructors-Destructors

            public function __construct($only_config = false) {
                parent::__construct();

                // Set config
                new Base_Config();

                // Check if only load config
                if($only_config)
                    return;

                // Init __route
                $this->__route = new stdClass();
                $this->__route->__base = new stdClass();

                // Init custom
                $this->callCustomRouterMethod("BeforeRouterInit");

                // Init
                $this->initProps();

            }
            public function __destruct() {}
            
        #endregion

        #region Main Methods

            private function initProps() {

                // Get routes
                $this->__routes = $this->loadRoutes(json_decode(file_get_contents(ACTIVE_FULL_PATH . "/routing/routes.json")));

                // Get layouts
                $this->__layouts = json_decode(file_get_contents(ACTIVE_FULL_PATH . "/routing/layouts.json"));

                // Get layouts
                $this->__components = json_decode(file_get_contents(ACTIVE_FULL_PATH . "/routing/components.json"));

                // Set order number and id to components
                foreach ($this->__components as $key => $component)
                    $this->__components[$key]->Id = $this->__components[$key]->OrderNumber = $key + 1;

            }

            public function Init() {

                // Get route
                $this->route();

                // Check querystring params
                $this->params();

                // Render
                $this->render();

            }

            private function loadRoutes($routes) {
                $response = [];

                // Get current language abbreviation
                $language = Base_Functions::IsNullOrEmpty($this->__route) || !property_exists($this->__route, "IdLanguage") || Base_Functions::IsNullOrEmpty($this->__route->IdLanguage) ? null : Base_Languages::ABBREVIATIONS[$this->__route->IdLanguage];

                // Loop routes
                foreach ($routes as $key => $route) {
                    
                    // Check if has Routes property
                    if(!property_exists($route, "Routes"))
                        continue;

                    // Get routes
                    $rts = $route->Routes;

                    // Remove routes
                    unset($route->Routes);

                    // Check if has Pages property
                    if(!property_exists($route, "Pages"))
                        $route->Pages = [];

                    // Check if is an object
                    if(is_object($rts)) {

                        // Check if we have language
                        if(Base_Functions::IsNullOrEmpty($language))
                            throw new Exception("Routes property of `$key` is an object so presumably it is multilingual but we do not have the language defined", 1);

                        // Search and key in rts
                        $language_key = array_values(array_filter(array_keys((array)$rts), function($item) use($language) { return strtoupper($language) == strtoupper($item); }));

                        // Check if not found
                        if(count($language_key) == 0)
                            continue;

                        // Get language routes
                        $language_route = $rts->{$language_key[0]};

                        // Set rts
                        $rts = $language_route;

                        // Check if is an object
                        if(is_object($language_route)) {

                            // Check if has Urls property
                            if(!property_exists($language_route, "Urls"))
                                throw new Exception("Property `Urls` not found for language `$language` in route `$key`", 1);

                            // Merge Pages
                            if(property_exists($language_route, "Pages"))
                                $route->Pages = array_merge($language_route->Pages, $route->Pages);

                            // Set rts with Urls
                            $rts = $language_route->Urls;

                            // Unset
                            unset($language_route->Urls);
                            unset($language_route->Pages);

                            // Merge other properties
                            $route = Base_Functions::mergeObjects($route, $language_route);

                        }

                    }

                    // Check if $rts is false
                    if($rts === false)
                        continue;
                    
                    // Check if is an array
                    if(!is_array($rts))
                        $rts = [$rts];

                    // Check if there are translations
                    foreach ($rts as $key => $rt) {
                        
                        // Count occurrences of .
                        $count = substr_count($rt, ".");

                        // Check if is a translation
                        if($count == 2)
                            $rts[$key] = __t($rt, true) ? __t($rt) : false;

                    }

                    // Set route key
                    $route->Key = $key;
                    
                    // Set routes
                    $route->Route = $rts;

                    // Add to response
                    array_push($response, $route);
                }

                return $response;
            }

        #endregion

        #region Routes Methods

            private function route() {

                // Get current
                $available = $this->getCurrentRouteByPath();

                // Set current route
                $this->__route = Base_Functions::mergeObjects($this->__route, $available);

                // Set meta title and description
                $this->getMetaTitleDescriptionFromRoute();

                // Set session cookie
                Base_Auth::sessionCookieName();

                // Get session
                $session = Base_Auth::getSession();

                // Callback for session loaded
                $this->callCustomRouterMethod("SessionLoaded", $session);
                
                // Check if need to be logged
                if(property_exists($this->__route, "Auth") && $this->__route->Auth) {

                    // Not found
                    if(Base_Functions::IsNullOrEmpty($session))
                        $this->error401();

                    // Check role
                    if(!$this->checkRole() || (class_exists("Base_Customer_Type") && property_exists($session, "Type") && $session->Type == Base_Customer_Type::ANONYMOUS))
                        $this->error403();

                    $this->callCustomRouterMethod("AccountLogged");
                }

                // Check base router custom
                else if((Base_Functions::IsNullOrEmpty($session) || count((array)Base_Auth::getSession(true)) == 0))
                    $this->callCustomRouterMethod("AccountAnonymous");

                // Remove Auth
                unset($this->__route->Auth);

                // Set Logged
                $this->setLoggedBySession();

                // Check if found
                if(Base_Functions::IsNullOrEmpty($available))
                    $this->error404();

                // Move Key to __base
                $this->__route->__base->Key = $this->__route->Key;
                unset($this->__route->Key);

                // Callback
                $this->callCustomRouterMethod("AfterSetLogged");

                // Get page from layout
                $pages = $this->getPagesByLayout();

                // Invalid layout
                if($pages === false)
                    $this->error404();

                // Set pages
                $this->__pages = $pages;

                // Get components
                $this->getComponentsByRoute();

                // Include route js
                if(property_exists($this->__route, "Js")) {
                    foreach ($this->__route->Js as $file)
                        array_push($this->__scripts_to_include, ACTIVE_PATH . "/pages/" . ltrim($file, "/"));

                    unset($this->__route->Js);
                }

                // Include route css
                if(property_exists($this->__route, "Css")) {
                    foreach ($this->__route->Css as $file)
                        array_push($this->__styles_to_include, ACTIVE_PATH . "/pages/" . ltrim($file, "/"));

                    unset($this->__route->Css);
                }
                
            }

            private function getCurrentRouteByPath() {

                // Get current and remove first and last slash
                $current = $this->removeFirstLastSlash($this->getCurrentUrl());

                // Get number of parts of the routes
                $count = count(array_filter(preg_split('/\s+/', $current)));

                // Get available routes with same parts number and remove empty
                $routes = array_values(array_filter($this->__routes, function($item) use ($count) {

                    // Check if is array
                    if(!is_array($item->Route))
                        $item->Route = [$item->Route];

                    // Check
                    foreach ($item->Route as $route) {

                        $route = $this->removeFirstLastSlash($route);

                        // Get number of parts of the routes
                        $c = count(array_filter(preg_split('/\s+/', $route)));

                        if($c == $count)
                            return $item;
                    }
                    
                    return null;
                }));

                // Create multiple routes using urls
                foreach ($routes as $index => $route) {

                    // Check if is array
                    if(!is_array($route->Route))
                        $route->Route = [$route->Route];

                    // Remove current
                    unset($routes[$index]);

                    foreach ($route->Route as $route_url) {
                        
                        // Clone
                        $clone = clone $route;

                        // Place / at the beginning and end
                        $route_url = "/" . rtrim(ltrim($route_url, "/"), "/") . "/";

                        // Check if has (
                        if(Base_Functions::HasSubstring($route_url, "/(")) {

                            // Init clone params
                            $clone->Params = [];

                            // Get params
                            $params = Base_Functions::getTextInTags($route_url, "(", ")");
                            
                            // Replace
                            foreach ($params as $param) {
                                
                                // Replace
                                $route_url = str_replace("($param)", "(*)", $route_url);

                                // Add to route
                                array_push($clone->Params, $param);

                            }

                        }

                        // Set route
                        $clone->Route = $route_url;

                        // Add
                        array_push($routes, $clone);

                    }

                }

                $routes = array_values($routes);

                // Check if the current route exists
                $available = array_filter($routes, function($item) use ($current) {
                    return $this->removeFirstLastSlash($item->Route) == $current;
                });

                // Not found - Try with dynamic
                if(count($available) == 0) {

                    // Check every element
                    $explode = explode("/", $current);

                    // Get path slices count
                    $count = count($explode);

                    // Init potential urls
                    $potential_urls = [$current];

                    // Generate all possible combinations of holes with `(*)`
                    for ($i = 0; $i < (1 << $count); $i++) {
                        $pattern = $explode;

                        // Replaces parts of the url with `(*)`.
                        for ($j = 0; $j < $count; $j++) {
                            if ($i & (1 << $j))
                                $pattern[$j] = "(*)";
                        }

                        array_push($potential_urls, implode("/", $pattern));
                    }

                    // Removes duplicates
                    $potential_urls = array_values(array_unique($potential_urls));

                    // Check if potential urls exists in routes
                    $available = array_values(array_filter($routes, function($item) use ($potential_urls) {
                        return in_array($this->removeFirstLastSlash($item->Route), $potential_urls);
                    }));

                }
                else
                    $available = array_values($available);

                if(count($available) == 0)
                    return null;

                // Explode current
                $current_exploded = explode("/", $current);

                // Init index and similar parts count
                $index = 0;
                $similar_parts = 0;

                // Compare the results with the current and get the most similar
                foreach ($available as $i => $url) {
                    
                    // Explode url
                    $url_exploded = explode("/", $this->removeFirstLastSlash($url->Route));

                    // Get common parts
                    $common_parts = array_intersect($current_exploded, $url_exploded);

                    // Check if is the most similar
                    if($similar_parts < count($common_parts)) {
                        $similar_parts = count($common_parts);
                        $index = $i;
                    }
                }

                // Check if found
                return $available[$index];

            }
            private function getPagesByLayout() {
                $layout = null;

                // Check if has property Layout
                if(property_exists($this->__route, "Layout") && !Base_Functions::IsNullOrEmpty($this->__route->Layout)) {
                    $route_layout = $this->__route->Layout;

                    unset($this->__route->Layout);

                    // Check if layout exists
                    if(property_exists($this->__layouts, $route_layout))
                        $layout = $this->__layouts->{$route_layout};

                }
                // Get default
                else {
                    $layout = array_filter(array_values((array)$this->__layouts), function($item) { 
                        return property_exists($item, "IsDefault") ? $item->IsDefault : false; 
                    });

                    // Check if found
                    if(count($layout) > 0) $layout = $layout[0];
                }

                if(!Base_Functions::IsNullOrEmpty($layout)) {
                    $layout = clone $layout;

                    // Check if has Pages
                    if(!property_exists($layout, "Pages"))
                        $layout->Pages = [];

                    // Check if has Head
                    if(!property_exists($layout, "Head"))
                        $layout->Head = [];

                    // Check if has Foot
                    if(!property_exists($layout, "Foot"))
                        $layout->Foot = [];

                }

                // Check layout
                if(Base_Functions::IsNullOrEmpty($layout))
                    return false;

                // Format layout by language
                $layout->Head = $this->formatLayoutSectionByLanguage($layout->Head);
                $layout->Pages = $this->formatLayoutSectionByLanguage($layout->Pages);
                $layout->Foot = $this->formatLayoutSectionByLanguage($layout->Foot);

                // If route has pages, merge with layout
                if(property_exists($this->__route, "Pages")) {
                    $layout->Pages = array_merge($layout->Pages, $this->__route->Pages);

                    unset($this->__route->Pages);
                }

                // Init pages
                $pages = [];

                // Set head
                $pages = array_map(function ($item) {

                    $obj = new stdClass();
                    $obj->Head = true;
                    $obj->Path = $item;

                    return $obj;
                }, $layout->Head ?? []);

                // Set pages
                $pages = array_merge($pages, array_map(function ($item) {

                    $obj = new stdClass();
                    $obj->Path = $item;

                    return $obj;
                }, $layout->Pages ?? []));

                // Set foot
                $pages = array_merge($pages, array_map(function ($item) {

                    $obj = new stdClass();
                    $obj->Foot = true;
                    $obj->Path = $item;

                    return $obj;
                }, $layout->Foot ?? []));

                return $pages;
            }
            private function formatLayoutSectionByLanguage($section) {
                $response = [];

                // Check if object
                if(is_object($section)) {

                    // Get current language abbreviation
                    // $language = Base_Functions::IsNullOrEmpty($this->__route) || !property_exists($this->__route, "IdLanguage") || Base_Functions::IsNullOrEmpty($this->__route->IdLanguage) ? null : Base_Languages::ABBREVIATIONS[$this->__route->IdLanguage];
                    $language = $this->getCurrentLanguage();

                    // Check if we have language
                    if(Base_Functions::IsNullOrEmpty($language))
                        throw new Exception("Layout section is an object so presumably it is multilingual but we do not have the language defined", 1);

                    // Search and key in section
                    $language_key = array_values(array_filter(array_keys((array)$section), function($item) use($language) { return strtoupper($language) == strtoupper($item); }));

                    // Check if not found
                    if(count($language_key) == 0)
                        return [];

                    // Set section by language
                    $section = $section->{$language_key[0]};   
                }

                // Check if string
                if(is_string($section))
                    $response = [$section];
                
                // Array
                else
                    $response = $section;

                return $response;
            }
            private function getComponentsByRoute() {

                $using_components = [];

                // Check if route has components
                if(property_exists($this->__route, "Components")) {

                    foreach ($this->__route->Components as $name) {
                        
                        // Get by name
                        $component = array_values(array_filter($this->__components, function($item) use ($name) { return in_array($name, $item->Names); }));

                        // Check if found
                        if(count($component) > 0)
                            array_push($using_components, $component[0]);

                    }

                    unset($this->__route->Components);

                }

                // Merge with components to always include
                $using_components = array_merge($using_components, array_filter($this->__components, function($item) { return property_exists($item, "AlwaysInclude") && $item->AlwaysInclude === true; }));

                // Remove duplicated components
                $using_components = $this->uniqueArrayObjects($using_components, "Id");

                // Sort by order number
                Base_Functions::osort($using_components, "OrderNumber");

                $using_components = array_values($using_components);

                // Insert in styles/scripts to include
                foreach ($using_components as $component) {
                    
                    // Merge styles
                    if(property_exists($component, "Head") && count($component->Head) > 0)
                        $this->__styles_to_include = array_merge($this->__styles_to_include, $component->Head);
                
                    // Merge scripts
                    if(property_exists($component, "Foot") && count($component->Foot) > 0)
                        $this->__scripts_to_include = array_merge($this->__scripts_to_include, $component->Foot);

                    // Merge pages
                    if(property_exists($component, "Pages") && count($component->Pages) > 0) {
                        $component->Pages = array_map(function ($item) {
                            $obj = new stdClass();
                            $obj->Foot = true;
                            $obj->Path = $item;
                            return $obj;
                        }, $component->Pages);

                        $this->__pages = array_merge($this->__pages, $component->Pages);
                    }

                }

            }
            private function setLoggedBySession() {

                // Get session
                $session = Base_Auth::getSession();

                // Check if not null
                if(!Base_Functions::IsNullOrEmpty($session))
                    $this->Logged = $session;

            }
            private function getCurrentLanguage() {

                // Set default response
                $response = Base_Languages::ABBREVIATIONS[Base_Languages::DEFAULT];

                // Get current domain
                $domain = $_SERVER['SERVER_NAME'];
        
                // Explode by .
                $domain = explode(".", $domain);
        
                // Explode request uri
                $request = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));
        
                // Check if the $request is not null
                if (count($request) > 0)
                    $response = Base_Languages::getLanguageFromAbbreviation($request[0]);
        
                return $response;
            }

        #endregion

        #region Auth Methods

            private function checkRole() {
                $valid = true;

                // Check if current route has roles
                if(!property_exists($this->__route, "Roles"))
                    return $valid;

                $this->setLoggedBySession();

                // Check id role
                if(!in_array($this->Logged->IdRole, $this->__route->Roles))
                    $valid = false;

                return $valid;
            }

        #endregion

        #region Render Methods

            private function render() {

                // Set index file
                $index = ACTIVE_FULL_PATH . "/index.php";

                if(!file_exists($index))
                    $this->error404(true);

                // Get meta title and description
                $this->getMetaTitleDescription();

                // Init render
                $this->checkRenderControllerFile();

                // Callback
                $this->callCustomRouterMethod("BeforeRenderPage");

                // Include index.php
                include_once($index);

            }

            /** Called in index.php */
            private function renderMetaTitle() {
                $response = "";

                // Check if current route has title
                if(property_exists($this->__route->__base, "MetaTitle")) {

                    if(!Base_Functions::IsNullOrEmpty($this->__route->__base->MetaTitle))
                        $response = $this->__route->__base->MetaTitle;

                    unset($this->__route->__base->MetaTitle);

                }

                // Add separator
                if(defined("SITE_SEPARATOR") && !Base_Functions::IsNullOrEmpty(SITE_SEPARATOR) && !Base_Functions::IsNullOrEmpty($response))
                    $response .= " " . SITE_SEPARATOR . " ";
                    
                return $response;
            }
            /** Called in index.php */
            private function renderMetaDescription() {
                $response = "";

                // Check if current route has title
                if(property_exists($this->__route->__base, "MetaDescription")) {

                    if(!Base_Functions::IsNullOrEmpty($this->__route->__base->MetaDescription))
                        $response = $this->__route->__base->MetaDescription;

                    unset($this->__route->__base->MetaDescription);

                }

                // Check
                if(Base_Functions::IsNullOrEmpty($response) && defined("SITE_DESCRIPTION") && !Base_Functions::IsNullOrEmpty(SITE_DESCRIPTION))
                    $response = SITE_DESCRIPTION;

                return $response;
            }

            /** Called in index.php */
            private function renderHeadPages() {

                // Get head pages
                $pages = array_filter($this->__pages, function($item) { return property_exists($item, "Head") && $item->Head; });

                // Check if found
                if(count($pages) == 0)
                    return;

                // Get pages
                $pages = array_values(array_unique(array_column($pages, "Path")));

                foreach ($pages as $screen)
                    $this->renderFileFromPath($screen);
            }
            /** Called in index.php */
            private function renderBodyPages() {

                // Get head pages
                $pages = array_values(array_filter($this->__pages, function($item) { return (!property_exists($item, "Foot") || $item->Foot == false) && (!property_exists($item, "Head") || $item->Head == false); }));

                // Check if found
                if(count($pages) == 0)
                    return;

                // Get pages
                $pages = array_values(array_unique(array_column($pages, "Path")));

                foreach ($pages as $screen)
                    $this->renderFileFromPath($screen);

            }
            /** Called in index.php */
            private function renderFootPages() {

                // Get head pages
                $pages = array_filter($this->__pages, function($item) { return property_exists($item, "Foot") && $item->Foot; });

                // Check if found
                if(count($pages) == 0)
                    return;

                // Get pages
                $pages = array_values(array_unique(array_column($pages, "Path")));

                foreach ($pages as $screen)
                    $this->renderFileFromPath($screen);
            }

            /** Called in index.php */
            private function renderStyles() {

                foreach ($this->__styles_to_include as $style) {
                    $style = $this->replaceSpecialKeywords($style);
                    
                    // Include if exists
                    if(filter_var($style, FILTER_VALIDATE_URL) !== false || file_exists($_SERVER["DOCUMENT_ROOT"] . $style)) {

                        // Check cache
                        if(defined("STYLE_SCRIPT_CACHE_ENABLED") && STYLE_SCRIPT_CACHE_ENABLED && !filter_var($style, FILTER_VALIDATE_URL))
                            $style .= "?v=" . date("YmdHis", filemtime($_SERVER["DOCUMENT_ROOT"] . $style));

                        // Sanitize if not url
                        if(filter_var($style, FILTER_VALIDATE_URL) === false)
                            $style = htmlspecialchars($style, ENT_QUOTES, 'UTF-8');

                        echo "<link rel='stylesheet' type='text/css' href='$style' />\r\t";
                    }

                }
            }
            /** Called in index.php */
            private function renderScripts() {

                foreach ($this->__scripts_to_include as $script) {
                    $script = $this->replaceSpecialKeywords($script);
                    
                    // Include if exists and is not a url
                    if(filter_var($script, FILTER_VALIDATE_URL) !== false || file_exists($_SERVER["DOCUMENT_ROOT"] . $script)) {
                        
                        // Check cache
                        if(defined("STYLE_SCRIPT_CACHE_ENABLED") && STYLE_SCRIPT_CACHE_ENABLED && !filter_var($script, FILTER_VALIDATE_URL))
                            $script .= "?v=" . date("YmdHis", filemtime($_SERVER["DOCUMENT_ROOT"] . $script));

                        // Sanitize if not url
                        if(filter_var($script, FILTER_VALIDATE_URL) === false)
                            $script = htmlspecialchars($script, ENT_QUOTES, 'UTF-8');
                        
                        echo "\t<script src='$script'></script>\r";
                    }
                }

                // Add script to remove tmp scripts
                echo "<script tmp_scripts_erasable>$('[tmp_scripts_erasable]').remove();</script>";
            }
            
            /** Called in index.php */
            private function renderBodyClass() {
                $response = [];

                // Check body class
                if(property_exists($this->__route->__base, "BodyClass")) {
                    $response = !is_array($this->__route->__base->BodyClass) ? [$this->__route->__base->BodyClass] : $this->__route->__base->BodyClass;

                    unset($this->__route->__base->BodyClass);
                }

                // Check loader props
                if(property_exists($this->__route->__base, "FullLoader")) {

                    if($this->__route->__base->FullLoader)
                        array_push($response, "is-loading is-loading-full");

                    unset($this->__route->__base->FullLoader);
                }

                elseif(property_exists($this->__route->__base, "Loader")) {
                    
                    if($this->__route->__base->Loader)
                        array_push($response, "is-loading");

                    unset($this->__route->__base->Loader);
                }

                // Remove empty
                $response = array_values(array_filter($response));

                // Show
                echo implode(" ", $response);
            }

            /** Called in index.php */
            private function renderAPIEnumsJS() {
                $response = [];

                // Init projects to keep
                $projects = [ltrim(ACTIVE_PATH, "/")];

                // Check if exists the DEFINE called SHARED_API
                if(defined("SHARED_API")) {
                    $shared = SHARED_API;
                    
                    // Check if array
                    if(!is_array(SHARED_API))
                        $shared = [$shared];

                    // Check if exists
                    foreach ($shared as $project) {
                        
                        if(file_exists($_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$project-routes.json"))
                            array_push($projects, $project);

                    }

                    // Remove duplicates
                    $projects = array_values(array_unique($projects));
                }

                // Loop projects
                foreach ($projects as $key => $project) {

                    // Check if first (the default project)
                    $isBase = $key == 0;
                 
                    // Build enum
                    $enums = $this->buildAPIEnums($isBase, $project);

                    // Init variables
                    $__API = $enums[0];

                    // Init apis
                    $apis = new stdClass();

                    // Format json to print
                    foreach ($__API as $method_apis) {

                        foreach ($method_apis as $controller => $endpoints) {
                            
                            // Check if the hash exists in the apis object
                            if(!property_exists($apis, $controller))
                                $apis->{$controller} = new stdClass();
        
                            foreach ($endpoints as $key1 => $endpoint) {
                                $apis->{$controller}->{$key1} = new stdClass();
                                $apis->{$controller}->{$key1}->Url = $isBase ? $endpoint->Url : str_replace("$project-", $projects[0] . "-", $endpoint->Url);
                                $apis->{$controller}->{$key1}->OverwriteUrl = $isBase ? null : $endpoint->Url;
                            }
                        }

                    }

                    // Add to response
                    array_push($response, $apis);
                    
                }

                // Get and remove first (the default project)
                $default = array_shift($response);
                $first = array_shift($projects);

                // Default
                $js = "
                    const API = JSON.parse(`" . json_encode($default) . "`);
                    const " . strtoupper($first) . " = JSON.parse(`" . json_encode($default) . "`);
                    ";

                // Build JS enums
                foreach ($projects as $project) {
                    $js .= "const " . strtoupper($project) . " = JSON.parse(`" . json_encode(array_shift($response)) . "`);";
                }

                echo $js;
                
            }
            /** Called in index.php */
            private function renderURLEnumsJS() {

                // Build object
                $obj = new stdClass();
                $obj->Params = property_exists($this->__route, "__base") && property_exists($this->__route->__base, "Params") ? $this->__route->__base->Params : null;
                $obj->Query = property_exists($this->__route, "__base") && property_exists($this->__route->__base, "Query") ? $this->__route->__base->Query : null;

                echo json_encode($obj);
                
            }
            /** Called in index.php */
            private function renderLoggedJS() {
                $logged = json_decode(json_encode($this->Logged));

                // Remove props
                if(!Base_Functions::IsNullOrEmpty($logged) && property_exists($logged, "Token"))
                    unset($logged->Token);

                echo json_encode($logged);
            }
            /** Called in index.php */
            private function renderEnums() {
                $response = new stdClass();

                // Get all files in enums folder
                $enums = glob($_SERVER["DOCUMENT_ROOT"] . "/enums/*.php");

                foreach ($enums as $enum) {

                    // Include file to ensure class is loaded
                    include_once $enum;
                
                    // Get file content
                    $content = file_get_contents($enum);
                
                    // Match all class names in the file
                    preg_match_all('/class\s+(\w+)(.*)?\{/', $content, $matches);
                
                    // Check if there are matches
                    if (isset($matches[1]) && count($matches[1]) > 0) {
                        foreach ($matches[1] as $className) {
                            
                            // Check if class exists
                            if (class_exists($className)) {
                
                                // Init response for the class
                                $response->{strtoupper($className)} = new stdClass();
                
                                // Get class reflection
                                $class = new ReflectionClass($className);
                
                                // Get constants
                                $constants = $class->getConstants();
                
                                // Add keys to response
                                foreach ($constants as $key => $value) {
                                    $response->{strtoupper($className)}->{$key} = $value;
                                }
                            }
                        }
                    }
                }
                
                echo json_encode($response);
            }

            private function renderFileFromPath($screen) {

                // Check if url otherwise build full path
                $fullpath = filter_var($screen, FILTER_VALIDATE_URL) !== false ? $screen : $this->buildPagesPath($screen);

                // Include if exists or is a url
                if(file_exists($fullpath) || filter_var($fullpath, FILTER_VALIDATE_URL) !== false) {

                    // Explode by dot
                    $explode = explode(".", $fullpath);
                    
                    // Get extension
                    $extension = $explode[count($explode) - 1];
                    
                    switch (strtoupper($extension)) {
                        case 'HTML':
                            echo file_get_contents($fullpath);
                            break;

                        case 'PHP':
                            include_once($fullpath);
                            break;
                    
                        default:
                            throw new Exception("Unknow file type `$extension`", 1);
                            break;
                    }

                }
                
            }
            private function checkRenderControllerFile() {

                // Get __base
                if(!property_exists($this->__route, "__base")) {
                    $this->__route->__base = new stdClass();
                    return;
                }

                // Move route to base
                $this->moveRouteToBase();

                // Check if property Render exists
                if(property_exists($this->__route->__base, "Render") && !Base_Functions::IsNullOrEmpty($this->__route->__base->Render)) {
                
                    // Check if has 3 parts
                    if(count($this->__route->__base->Render) == 3) {

                        // Get file name
                        $file_name = trim(strtolower($this->__route->__base->Render[0]));

                        // Build path
                        $path = ACTIVE_FULL_PATH . "/render/$file_name.php";

                        // Check if exists
                        if(file_exists($path)) {

                            // Set namespace
                            $namespace_name = "Render\\" . $this->__route->__base->Render[1] . "\\Methods";

                            // Include
                            include_once($path);

                            // Check if class exists
                            if(class_exists($namespace_name)) {

                                // Init class
                                $class = new $namespace_name();

                                // Set base props
                                $this->initRenderBaseProperties($class);

                                // Get method name
                                $method_name = trim(strtolower($this->__route->__base->Render[2]));

                                // Check if method exists
                                if(method_exists($class, $method_name)) {

                                    // Call method
                                    $response = $class->{$method_name}();

                                    // Check if valid
                                    if($response === false)
                                        $this->error404();

                                    // Set Logged
                                    $this->setLoggedBySession();

                                    // Merge __route with response
                                    if(!Base_Functions::IsNullOrEmpty($response)) {

                                        // Check if response is an array
                                        if(is_array($response))
                                            $this->__route->Values = $response;
                                        else
                                            $this->__route = Base_Functions::mergeObjects($this->__route, $response); 

                                    }

                                    // Overwrite url params
                                    $this->__route->__base->Params = $class->Params;
                                        
                                    // Overwrite pages
                                    $this->__pages = $class->Pages;

                                    // Overwrite JS
                                    $this->__scripts_to_include = array_values(array_unique(array_filter($class->Scripts)));

                                    // Overwrite title and description
                                    $this->__route->__base->MetaTitle = $class->MetaTitle;
                                    $this->__route->__base->MetaDescription = $class->MetaDescription;

                                }

                            }

                        }

                    }

                    unset($this->__route->__base->Render);
                }
            }
            private function buildPagesPath($partial) {

                return ACTIVE_FULL_PATH . "/pages/" . $this->replaceSpecialKeywords(ltrim($partial, "/"));

            }
            private function replaceSpecialKeywords($string) {

                $to_replace = ["{{ACTIVE_PATH}}"];
                $replace = [ltrim(ACTIVE_PATH, "/")];

                // Replace
                return str_replace($to_replace, $replace, $string);
            }

            private function initRenderBaseProperties($instance) {

                // Merge params
                foreach ($this->__route->__base->Params as $key => $value)
                    $instance->Params->{$key} = $value;

                // Set pages
                $instance->Pages = $this->__pages;

                // Init scripts
                $instance->Scripts = $this->__scripts_to_include;

                // Set title and description
                $instance->MetaTitle = property_exists($this->__route->__base, "MetaTitle") ? $this->__route->__base->MetaTitle : null;
                $instance->MetaDescription = property_exists($this->__route->__base, "MetaDescription") ? $this->__route->__base->MetaDescription : null;

                // Set logged
                $instance->Logged = $this->Logged;

                // Merge custom properties
                foreach ($this->__route_custom_props as $prop)
                    $instance->{$prop} = $this->__route->{$prop};
            }

        #endregion

        #region Params Methods

            private function params() {

                // Init route params
                $this->__route->__base->Query = (object)$_GET;

                // Check if has Params
                if(property_exists($this->__route, "Params")) {
                    
                    if(count($this->__route->Params) > 0) {

                        $params = new stdClass();
                            
                        // Get indexes
                        $indexes = array_values(array_keys(explode("/", $this->removeFirstLastSlash($this->__route->Route)), '(*)'));

                        // Get current url and explode
                        $url_exploded = explode("/", $this->removeFirstLastSlash($this->getCurrentUrl()));
                        
                        foreach ($indexes as $i => $index) {
                            $name = "querystring_$i";

                            // Check if params name is defined
                            if(count($this->__route->Params) > $i)
                                $name = $this->__route->Params[$i];

                            // Set the values from the current route
                            $params->{$name} = $url_exploded[$index];

                        }

                        // Set global variable
                        $this->__route->__base->Params = $params;
                    }
                }
                // Init as array
                else
                    $this->__route->__base->Params = array();

                unset($this->__route->Params);
                unset($this->__route->Route);

            }

        #endregion

        #region API Methods

            public function buildAPIEnums($setBaseVariables = true, $partial_path = null) {

                // Check if partial path is null
                if(Base_Functions::IsNullOrEmpty($partial_path))
                    $partial_path = ltrim(ACTIVE_PATH, "/");

                // Format partial path
                $partial_path = strtolower($partial_path);

                // Build routes file name
                $name = $partial_path . "-routes.json";

                // Build path
                $path = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$name";

                // Check if exists
                if(!file_exists($path))
                    return;
                
                // Get routes
                $routes = Base_Functions::APIRoutesReorder(json_decode(file_get_contents($path)));

                // Init object
                $__API_requests = new stdClass();
                $__API = new stdClass();

                // Get types
                $types = array_keys((array)$routes);

                foreach ($types as $type) {
                    $upper_type = strtoupper($type);

                    // Init type
                    $__API_requests->{$upper_type} = new stdClass();
                    $__API->{$upper_type} = new stdClass();

                    // Get controllers
                    $controllers = array_keys((array)$routes->{$type});

                    foreach ($controllers as $controller) {
                        $upper_controller = strtoupper($controller);
                        $endpoint_exists = false;

                        // Build path
                        $path = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/endpoints/$partial_path/" . strtolower($controller) . "/methods.php";

                        // Check if folder exists
                        if(file_exists($path))
                            $endpoint_exists = true;

                        // Build
                        $__API->{$upper_type}->{$upper_controller} = property_exists($__API->{$upper_type}, $upper_controller) ? $__API->{$upper_type}->{$upper_controller} : new stdClass();

                        // Get endpoints
                        $endpoints = array_keys((array)$routes->{$type}->{$controller});

                        foreach ($endpoints as $endpoint) {
                            $upper_endpoint = strtoupper($endpoint);

                            // Check if INDEX method
                            if(Base_Functions::IsNullOrEmpty($upper_endpoint))
                                $upper_endpoint = "INDEX";
                            
                            // Get method
                            $method = $routes->{$type}->{$controller}->{$endpoint};

                            // Build api url
                            $url = rtrim(str_replace("//", "/", implode("/", ["", $partial_path . "-bff", $controller, $endpoint])), "/");

                            // Encrypt url
                            $encrypted_url = Base_Encryption::Encrypt($url);

                            // Check method name
                            $method_name = property_exists($method, "Method") ? $method->Method : strtolower($upper_type);

                            // Build objects
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint} = new stdClass();
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Url = $url . "/";
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Method = $method_name;
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Valid = $endpoint_exists;

                            $__API_requests->{$upper_type}->{$encrypted_url} = new stdClass();
                            $__API_requests->{$upper_type}->{$encrypted_url}->Method = $method_name;
                            $__API_requests->{$upper_type}->{$encrypted_url}->Auth = property_exists($method, "Auth") ? $method->Auth : false;

                            // Check if has request
                            if(property_exists($method, "Request") && !Base_Functions::IsNullOrEmpty($method->Request)) {
                                $path = "";

                                // Full Path
                                if(Base_Functions::HasSubstring($method->Request->Path, "/"))
                                    $path = str_replace("//", "/", implode("/", ["", API_FOLDER, "endpoints", $partial_path, str_replace(".json", "", $method->Request->Path) . ".json"]));

                                // Partial
                                else
                                    $path = str_replace("//", "/", implode("/", ["", API_FOLDER, "endpoints", $partial_path, strtolower($controller), "requests", str_replace(".json", "", $method->Request->Path) . ".json"]));

                                // Set object
                                $__API_requests->{$upper_type}->{$encrypted_url}->Class = $method->Request->Class;
                                $__API_requests->{$upper_type}->{$encrypted_url}->Path = $path;

                            }

                        }
                    }
                    
                }

                // Check if set base variables
                if($setBaseVariables) {

                    // Set objects
                    $this->__API_requests = $__API_requests;
                    $this->__API = $__API;

                }
                
                return [$__API, $__API_requests];
            }

        #endregion

        #region Meta Methods

            private function getMetaTitleDescriptionFromRoute() {

                // Rename Title and Description if exists
                if(property_exists($this->__route, "Title")) {
                    $this->__route->MetaTitle = $this->getMetaValue($this->__route->Title);
                    unset($this->__route->Title);
                }
                if(property_exists($this->__route, "Description")) {
                    $this->__route->MetaDescription = $this->getMetaValue($this->__route->Description);
                    unset($this->__route->Description);
                }

            }
            private function getMetaTitleDescription() {

                // Check if current route has title and description props
                if((!property_exists($this->__route, "MetaTitle") || Base_Functions::IsNullOrEmpty($this->__route->MetaTitle)) && (!property_exists($this->__route, "MetaDescription") || Base_Functions::IsNullOrEmpty($this->__route->MetaDescription)))
                    return;

                // Get title
                if(property_exists($this->__route, "MetaTitle"))
                    $this->__route->MetaTitle = $this->getMetaValue($this->__route->MetaTitle);

                // Get description
                if(property_exists($this->__route, "MetaDescription"))
                    $this->__route->MetaDescription = $this->getMetaValue($this->__route->MetaDescription);

            }
            private function getMetaValue($meta) {

                // Check if is a translation path
                // Check if has at least 2 dots and hasn't whitespace
                if(substr_count($meta, ".") >= 2 && preg_match('/^\S.*\s.*\S$/', $meta) == 0) {

                    // Try to get the translation
                    $translation = Translations::Translation($meta, true);

                    // Check if found
                    if(!Base_Functions::IsNullOrEmpty($translation))
                        return $translation;

                }

                return $meta;
            }

        #endregion

        #region Error Methods

            /** Not authorized because not logged */
            private function error401() {

                // Set response code
                http_response_code(401);

                // Render error page
                $this->renderError(function () {
                    $this->callCustomRouterMethod("Unauthorized");
                });

            }
            /** Not authorized because of the role */
            private function error403() {

                // Set response code
                http_response_code(403);

                // Render error page
                $this->renderError(function () {
                    $this->callCustomRouterMethod("Unauthorized");
                });

            }
            private function error404($index_not_found = false) {

                // Set response code
                http_response_code(404);

                // Check if is the index.php not found
                if($index_not_found) exit;

                // Render error page
                $this->renderError();

            }

            private function renderError($not_found_callback = null) {

                // Get current response code
                $code = http_response_code();

                // Build error property
                $property = "Is$code";

                // Get from $this->__routes where Is404 is true
                $routes = array_filter($this->__routes, function($item) use($property) { return property_exists($item, $property) && $item->$property; });

                // Check if found
                if(count($routes) == 0) {

                    // Check if not found callback
                    if(!Base_Functions::IsNullOrEmpty($not_found_callback))
                        $not_found_callback();
                    else
                        exit;
                }

                // Get first
                $route = array_values($routes)[0];

                // Unset property
                unset($route->$property);

                // Set current route
                $this->__route = $route;

                if(!Base_Functions::IsNullOrEmpty($this->__route)) {
                    
                    // Get screen from layout
                    $pages = $this->getPagesByLayout();

                    // Check layout
                    if($pages !== false) {

                        // Set pages
                        $this->__pages = $pages;

                        // Clear
                        $this->__styles_to_include = [];
                        $this->__scripts_to_include = [];

                        // Get components
                        $this->getComponentsByRoute();

                        // Include route js
                        if(property_exists($this->__route, "Js")) {
                            foreach ($this->__route->Js as $file)
                                array_push($this->__scripts_to_include, ACTIVE_PATH . "/pages/" . ltrim($file, "/"));

                            unset($this->__route->Js);
                        }

                        // Format meta title and description
                        $this->getMetaTitleDescriptionFromRoute();

                        // Move route to base
                        $this->moveRouteToBase();

                        // Render error
                        $this->render();
                    }
                }

                exit;

            }

        #endregion

        #region Private Methods

            private function removeFirstLastSlash($route) {

                // Remove slash
                $route = rtrim(ltrim($route, "/"), "/");

                // Format active path
                $active = strtolower(ltrim(ACTIVE_PATH, "/"));

                // Check first part
                if(substr($route, 0, strlen($active)) == $active)
                    $route = ltrim(substr($route, strlen($active)), "/");

                return $route;

            }
            private function uniqueArrayObjects($array, $key) {
                $temp_array = array();
                $key_array = array();
                $i = 0;
            
                foreach($array as $val) {
                    if (!in_array($val->{$key}, $key_array)) {
                        $key_array[$i] = $val->{$key};
                        $temp_array[$i] = $val;
                    }

                    $i++;
                }

                return array_values($temp_array);
            }
            private function getCurrentUrl() {

                $current = $this->removeFirstLastSlash(strtok($_SERVER["REQUEST_URI"], "?"));

                return $current;
            }
            private function moveRouteToBase() {
                
                $base = new stdClass();

                if(property_exists($this->__route, "__base")) {

                    // Get base
                    $base = $this->__route->__base;

                    // Remove __base
                    unset($this->__route->__base);

                }

                // Init custom props
                $custom_props = new stdClass();
                
                // Get custom props
                foreach ($this->__route_custom_props as $prop) {

                    // Check if exists
                    if(property_exists($this->__route, $prop)) {

                        // Get prop
                        $custom_props->{$prop} = $this->__route->{$prop};

                        // Remove prop
                        unset($this->__route->{$prop});
                    }
                }

                // Move all routes properties with __base
                $base = Base_Functions::mergeObjects($this->__route, $base);

                // Init route and __base
                $this->__route = new stdClass();
                $this->__route->__base = new stdClass();

                // Merge base with __base
                $this->__route->__base = $base;

                // Set custom props
                $this->__route = Base_Functions::mergeObjects($this->__route, $custom_props);

            }
            private function callCustomRouterMethod($method, $args = []) {

                // Check if method exists
                if(!method_exists($this, $method)) {

                    if(IS_DEBUG) throw new Exception("Method `$method` does not exists", 1);

                    return;
                }
                
                // Get $this->__route props
                $props = array_keys((array)$this->__route);

                // Remove keys from $this->__route_custom_props
                $props = array_values(array_diff($props, $this->__route_custom_props));
                
                // Call method
                $this->{$method}($args);

                // Check if props has changed
                $props = array_values(array_diff(array_keys((array)$this->__route), $props));

                // Push
                $this->__route_custom_props = array_values(array_unique(array_merge($this->__route_custom_props, $props)));
            }
            
        #endregion

    }