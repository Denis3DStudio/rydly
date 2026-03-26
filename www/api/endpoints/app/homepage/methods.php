<?php

    namespace App\Homepage;

    use stdClass;
    use Base_Methods;
    use Base_Functions;

    class Methods extends Base_Methods {

        private $id = "IdHomepageBanner";
        private $table_name = "homepage_banners";
        private $table_translations_name = "homepage_banners_translations";
        private $table_images_name = "homepage_banners_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion
        
        #region Public Methods

            // Get
            public function get($idHomepageBanner) {

                $idLanguage = $this->Logged->IdLanguage;

                // Check if the exists and is not null
                $sql = "SELECT tt.*, t.Name
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        WHERE t.IsValid = 1 AND t.IsDeleted = 0 AND tt.{$this->id} = $idHomepageBanner AND tt.IdLanguage = $idLanguage";
                $data = $this->__linq->queryDB($sql)->getFirstOrDefault();

                // Check if the data is null
                if (Base_Functions::IsNullOrEmpty($data))
                    return $this->Not_Found(null, "Sponsor non trovato!");

                // Get the icon
                $image = $this->__linq->fromDB($this->table_images_name)->whereDB($this->id . " = $idHomepageBanner")->getFirstOrDefault();

                $response = new stdClass();
                $response->Name = $data->Name;
                $response->ButtonLink = $data->ButtonLink;
                $response->Image = $image->FullPath;

                return $this->Success($response);
            }
            public function getAll() {

                $idLanguage = $this->Logged->IdLanguage;

                $response = array();
                $response['Sponsor'] = array();
                $response['News'] = array();

                // Get all sponsor
                $sql = "SELECT tt.*, t.OrderNumber, t.Name
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        WHERE t.IsValid = 1 AND t.IsDeleted = 0 AND tt.IdLanguage = $idLanguage
                        ORDER BY t.OrderNumber ASC";

                $all = $this->__linq->queryDB($sql)->getResults();

                // Check that $all is not null
                if (count($all) > 0) {
                    $all = $this->__linq->reorder($all, $this->id, true);
                    // Cycle all data
                    foreach($all as $sponsors) {
                        $sponsor = $sponsors[0];
                        $image = $this->__linq->fromDB($this->table_images_name)->whereDB($this->id . " = $sponsor->IdHomepageBanner")->getFirstOrDefault();

                        if (Base_Functions::IsNullOrEmpty($image))
                            continue;

                        // Create the obj for the response
                        $obj = new stdClass();
                        $obj->{$this->id} = $sponsor->{$this->id};
                        $obj->Name = $sponsor->Name;
                        $obj->ButtonLink = $sponsor->ButtonLink;
                        if ($image != null) {
                            $obj->Image = $image->FullPath;
                        } else {
                            $obj->Image = "";
                        }
                        
                        array_push($response['Sponsor'], $obj);
                    }
                }

                $response['News'] = $this->news->getAll(5);

                return $this->Success($response);
            }           

        #endregion

        #region Private Methods

        #endregion
    }

?>