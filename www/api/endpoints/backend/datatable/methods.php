<?php

    namespace Backend\DataTable;

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

            public function serverSide($serverSideKey) {
        
                // Limit
                $this->createLimitQuery();
                // Order
                $this->createOrderQuery($serverSideKey);
                // Search
                $this->createSearchQuery($serverSideKey);

                // Call the methods
                return $this->callApi($serverSideKey);
            }

            private function callApi($serverSideKey) {

                // Check the key of the call
                switch (strtoupper($serverSideKey)) {
                        
                    case 'ALL_NEWS':

                        // set the values of the request in the news obj
                        $this->news->Request = $this->Request;

                        // Call the getall method
                        $this->news->getAll();

                        // Create the custom response obj
                        $obj = new stdClass();
                        $obj->recordsTotal = $this->news->ServerSideTotalCount;
                        $obj->recordsFiltered = $this->news->ServerSideFilteredCount;

                        // Set the custom props
                        $this->SetCustomProperties($obj);

                        // Check if the news is Success
                        if (!$this->news->Success)
                            return $this->Not_Found();

                        $this->Success($this->news->Response);
                        
                        break;
                }

                return;
            }

            private function createLimitQuery() {

                // Get the value of the limit
                $limit = $this->Request->length;
                // Get the offset
                $offset = $this->Request->start;

                // Create the sql code for the limit
                $this->Request->limit_query = "LIMIT $offset, $limit";
            }

            private function createOrderQuery($serverSideKey) {

                // Get the value of the column for the order
                $order_column = $this->Request->columns[$this->Request->order[0]->column]->name;
                // Get the direction of the order
                $order_dir = $this->Request->order[0]->dir;

                // Check the key of the call
                switch (strtoupper($serverSideKey)) {
                    
                    case 'ALL_NEWS':

                        break;

                        // Example of a custom order by for the ALL_NEWS
                            // Check the columns names for the get all of the news
                            switch (strtoupper($order_column)) {

                                case 'name':

                                    $this->Request->order_by_query = "ORDER BY CONCAT(name, ' ', surname) $order_dir";
                                    break;
                            }

                            break;
                }

                // Check if is empty
                if (!property_exists($this->Request, "order_by_query"))
                    // Create the sql code for the order by
                    $this->Request->order_by_query = "ORDER BY $order_column $order_dir";
            }

            private function createSearchQuery($serverSideKey) {

                // Get the value of the search text
                $search = property_exists($this->Request->search, "value") ? $this->Request->search->value : null;

                // Check if the value is not null
                if (!Base_Functions::IsNullOrEmpty($search)) {

                    // Get all column with searchable = "true"
                    $searchable_columns = array_filter(array_map(function ($v) { return $v->name;}, array_filter($this->Request->columns, function ($column) { if($column->searchable == "true") return true; return false;})));

                    // Check the key of the call
                    switch (strtoupper($serverSideKey)) {
                        
                        case 'ALL_NEWS':

                            // Complete the code for the search
                            $this->Request->search_query = "AND (nt.Title LIKE '%$search%' OR DATE_FORMAT(n.Date, '%d/%m/%Y') LIKE '%$search%')";

                            break;
                        
                        default:

                            // Create the sql code for the search
                            $this->Request->search_query = "AND (" . implode(" LIKE '%$search%' OR ", $searchable_columns) . " LIKE '%$search%'" . ")";

                            break;
                    }
                }
                // Set the default value of the search query
                else
                    $this->Request->search_query = "";
            }

            private function clearArrayFromName($searchable_columns, $names) {

                // Remove from searchable_columns
                $searchable_columns = array_filter($searchable_columns, function ($column) use ($names) {
                                
                    if(in_array($column, $names))
                        return false;

                    return true;
                });

                return $searchable_columns;
            }

        #endregion

    }

?>