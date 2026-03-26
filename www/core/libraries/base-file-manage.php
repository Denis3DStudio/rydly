<?php

// In the region "Private Methods", there's a method which defines the default values

class Base_File {

	#region Private Properties
		private $__path; // ex. "/backend/contents/something/;
		private $__fullPath; // ex. $_SERVER["DOCUMENT_ROOT"] . "/backend/contents/something/;
		private $__offRoot;	// If the folder path is off the root
		private $__files;
		private $__fileName;
		private $__overrideFileName; // If not null, the download filename will be this
		private $__quality;
		private $__width;
		private $__height;
		private $__autoResize;
		private $__overwrite; // If true, the file (if exists) will be overwritten
	#endregion

	#region Constructors-Destructors
		public function __construct($path = null) {
			if($path != "NONE") {
				$this->path($path);
				$this->offRoot(false);
				$this->setDefaults();
			}
		}
		public function __destruct() {   
		}
	#endregion

	#region Properties
    
		public function path($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$path = $val . "/";
				$this->__path = str_replace("//", "/", $path);
				$this->fullPath();
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function offRoot($val) {
			if(is_bool($val)) {
				$this->__offRoot = $val;
				$this->fullPath();
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		private function fullPath() {
			if($this->__offRoot) {
				$exp = explode("/", $_SERVER["DOCUMENT_ROOT"]);
				array_pop($exp);
				$path = join("/", $exp);
			} else {
				$path = $_SERVER["DOCUMENT_ROOT"];
			}

			$this->__fullPath = $path . $this->__path;

			// $fullpath = (substr($this->__fullPath, -1) == "/") ? substr($this->__fullPath, 0, -1) : $this->__fullPath;
			// if(!file_exists($fullpath)) {
			// 	$exp = explode("/", $fullpath);
			// 	array_pop($exp);
			// 	$new_full = join("/", $exp);

			// 	if(is_dir($new_full)) {
			// 		mkdir($fullpath);
			// 	}
			// }
		}
		public function file($val) {
			if($val != null) {
				$this->files($val);
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function files($val) {
			if($val != null) {
				if(is_array($val)) {
					// Is only one file
					if(array_key_exists("tmp_name", $val)) {
						array_push($this->__files, $val);
					} else {
						foreach ($val as $key => $value) {
							array_push($this->__files, $value);
						}
					}
				}
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function fileName($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$this->__fileName = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function overrideFileName($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$this->__overrideFileName = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function quality($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$this->__quality = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function width($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$this->__width = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function height($val) {
			if(!$this->IsNullOrEmpty($val)) {
				$this->__height = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function autoResize($val) {
			if(is_bool($val)) {
				$this->__autoResize = $val;
			} else {
				throw new Exception("Invalid value", 1);
			}

			return $this;
		}
		public function overwrite($val) {
			$this->__overwrite = $val;

			return $this;
		}

	#endregion
	
	#region Public Methods

		public function save() {
			if($this->checkValidity()) {
				$ret = array();

				foreach ($this->__files as $file) {
					if(is_uploaded_file($file['tmp_name']) || file_exists($file["tmp_name"])) {
						$newFile = new stdClass();
						$newFile->FileName = $this->checkFilename($file['name']);
						$newFile->OriginalFileName = $file['name'];
						$newFile->Type = $file['type'];
						$newFile->Size = $file['size'];
						$newFile->FullPath = $this->__path . $newFile->FileName;

						if($this->__autoResize && $newFile->Type != "application/pdf" && strpos($newFile->Type, "svg") === false)
							$this->resizeImage($file['tmp_name']);

						if(!file_exists($this->__fullPath))
							mkdir($this->__fullPath, 0755, true);
						
						if($this->__overwrite && file_exists($this->__fullPath . $newFile->FileName))
							unlink($this->__fullPath . $newFile->FileName);

						if(is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $this->__fullPath . $newFile->FileName)) {
							array_push($ret, $newFile);
						}
						else if(file_exists($file["tmp_name"]) && copy($file['tmp_name'], $this->__fullPath . $newFile->FileName)) {
							array_push($ret, $newFile);
						}
					}
				}

				$this->cleanProperties();

				return $ret;
			}
		}
		public function delete() {
			if($this->checkDeleteValidity()) {
				if(file_exists($this->__fullPath . $this->__fileName)) {
					unlink($this->__fullPath . $this->__fileName);
				}
			}
		}
		public function show() {
			if($this->checkDeleteValidity()) {
				$filePath = $this->__fullPath . $this->__fileName;
				if(file_exists($filePath)) {
					$mime = mime_content_type($filePath);
					
					header("Content-Type: " . $mime);

					// Show the file
					echo file_get_contents($filePath);
					exit;
				}
			}
		}
		public function download() {
			if($this->checkDeleteValidity()) {
				$filePath = $this->__fullPath . $this->__fileName;
				if(file_exists($filePath)) {
					$mime = mime_content_type($filePath);

					$fileName = (!$this->IsNullOrEmpty($this->__overrideFileName)) ? $this->__overrideFileName : $this->__fileName;
					
					header("Cache-Control: public"); // needed for internet explorer
					header("Content-Type: " . $mime);
					header("Content-Transfer-Encoding: Binary");
					header("Content-Length:" . filesize($filePath));
					header("Content-Disposition: attachment; filename=$fileName");

					// Read the file
					readfile($filePath);
					exit;
				}
			}
		}
		public function copy($pathToCopy) {
			if(!$this->IsNullOrEmpty($this->__path)) {
				if(!$this->IsNullOrEmpty($pathToCopy)) {
					if(file_exists($pathToCopy)) {
						$exp = explode("/", $pathToCopy);
						$fileName = end($exp);
						
						$newFile = new stdClass();
						$newFile->FileName = $this->checkFilename($fileName);
						$newFile->OriginalFileName = $fileName;
						$newFile->Type = mime_content_type($pathToCopy);
						$newFile->Size = filesize($pathToCopy);
						$newFile->FullPath = $this->__path . $newFile->FileName;
						
						copy($pathToCopy, $this->__fullPath . $newFile->FileName);

						$this->cleanProperties();
		
						return $newFile;
					}
				}
			} else {
				throw new Exception("Define the file's path", 1);
			}
		}
		public function checkEstension($ext) {
			$ret = false;
			$extensions = array("pdf", "xbm", "tif", "pjp", "svgz", "jpg", "jpeg", "ico", "tiff", "gif", "svg", "jfif", "webp", "png", "bmp", "pjpeg", "avif", "docx", "doc");
			
			if (!$this->IsNullOrEmpty($ext)) {
				if (in_array($ext, $extensions)) {
					$ret = true;
				}
			}

			return $ret;
		}

	#endregion


	#region Resize Image

		public function resizeImage($imagePath) {
			$newImagePath = $imagePath;

			$fileSize = getimagesize($imagePath);

			if($fileSize[0] > $this->__width || $fileSize[1] > $this->__height) {
				$size = $this->mantainAspectRatio($fileSize[0], $fileSize[1]);

				$dst = imagecreatetruecolor($size->width, $size->height);

				/* Check if this image is PNG, then set if Transparent*/  
				if(strtoupper($fileSize["mime"]) == "IMAGE/PNG") {
					$src = imagecreatefrompng($imagePath);

					imagealphablending($dst, false);
					imagesavealpha($dst,true);
					$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
					imagefilledrectangle($dst, 0, 0, $fileSize[0], $fileSize[1], $transparent);
				} else {
					$src = imagecreatefromstring(file_get_contents($imagePath));
				}

				imagecopyresampled($dst, $src, 0, 0, 0, 0, $size->width, $size->height, $fileSize[0], $fileSize[1]);

				if(strtoupper($fileSize["mime"]) == "IMAGE/PNG")
					imagepng($dst, $newImagePath);
				else
					imagejpeg($dst, $newImagePath, $this->__quality);

				imagedestroy($src);
				imagedestroy($dst);
			}
		}
		private function mantainAspectRatio($width, $height) {
			if($width != $height) {
				if($width > $height) {
					$t_width = $this->__width;
					$t_height = (($t_width * $height)/$width);
					//fix height
					if($t_height > $this->__height) {
						$t_height = $this->__height;
						$t_width = (($width * $t_height)/$height);
					}
				} 
				else {
					$t_height = $this->__height;
					$t_width = (($width * $t_height)/$height);
					//fix width
					if($t_width > $this->__width) {
						$t_width = $this->__width;
						$t_height = (($t_width * $height)/$width);
					}
				}
			}
			else {
				$t_width = $t_height = min($this->__height,$this->__width);
			}
		
			$size = new stdClass();
			$size->width = (int)$t_width;
			$size->height = (int)$t_height;
			
			return $size;
		}

	#endregion
	
	#region Check FileName

		private function checkFilename($fileName) {
			$ret = $fileName;

			if($this->__overwrite == false) {
				if(!$this->IsNullOrEmpty($fileName)) {

					// Remove extension
					$exp = explode(".", $fileName);

					// Get extension
					$extension = array_pop($exp);

					// Format name
					$new_name = Base_Functions::Slug(implode(".", $exp));

					// Check if path changed
					if(strtolower($fileName) != strtolower("$new_name.$extension"))
						$fileName = "$new_name.$extension";

					$exists = true;
					$i = 1;
					while ($exists) {
						if (file_exists($this->__fullPath . $fileName)) {
							$fileName = "$new_name-$i.$extension";
                            
							$i++;
						} else $exists = false;
					}
					
					$ret = $fileName;
				} else {
					throw new Exception("Filename is empty", 1);
				}
			}

			return $ret;
		}

	#endregion

	#region Data Validity

		private function checkValidity() {
			$ret = true;

			if($this->IsNullOrEmpty($this->__path)) {
				$ret = false;
				throw new Exception("Define the path where you want the file will be stored", 1);
			}
			if(count($this->__files) == 0) {
				$ret = false;
				throw new Exception("Define at least one file", 1);
			}

			return $ret;
		}
		private function checkDeleteValidity() {
			$ret = true;

			if($this->IsNullOrEmpty($this->__path)) {
				$ret = false;
				throw new Exception("Define the file's path", 1);
			}
			if($this->IsNullOrEmpty($this->__fileName)) {
				$ret = false;
				throw new Exception("Define the file's name", 1);
			}

			return $ret;
		}

	#endregion

	#region Dinamic Files

		// Get
		public static function getContentManager($idRow, $macro, $type = null) {

			// Get linQ
			$linq = new Base_LINQHelper();

			// Get the content
			return $linq->fromDB(self::buildDbName($macro, $type))->whereDB(Base_Files_Types::DB_IDS_TYPES[$type] . " = $idRow")->getFirstOrDefault();
		}
		public static function getContentsManager($idRow, $macro, $type = null, $extras = new stdClass()) {

			// Get linQ
			$linq = new Base_LINQHelper();

			// Check if extras is not null
			if(Base_Functions::IsNullOrEmpty($extras))
				$extras = new stdClass();

			// Build the extrasWhere
			$extrasWhere = "";

			// Cicle all the extras
			foreach($extras as $key => $value) {

				// Check if the value is not null
				if(!Base_Functions::IsNullOrEmpty($value)) {
					// Add the extrasWhere
					$extrasWhere .= " AND $key = '$value'";
				}
			}

			// Get the content
			$res = $linq->fromDB(self::buildDbName($macro, $type))->whereDB(Base_Files::IDS_DB[$macro] . " = $idRow $extrasWhere")->getResults();

			// Check if the res have OrderNumber
			if(!Base_Functions::IsNullOrEmpty($res) && property_exists($res[0], "OrderNumber"))
				// Sort by OrderNumber
                usort($res, function($a, $b) {
                    return $a->OrderNumber - $b->OrderNumber;
                });
			
			return array_values((array)$res);
		}
		public static function getContentsManagerMultiple($idRows, $macro, $type = null) {

			// Get linQ
			$linq = new Base_LINQHelper();

			// Get the content
			$res = $linq->fromDB(self::buildDbName($macro, $type))->whereDB(Base_Files::IDS_DB[$macro] . " IN (" . join(",", $idRows) . ")")->getResults();

			// Check if the res have OrderNumber
			if(!Base_Functions::IsNullOrEmpty($res) && property_exists($res[0], "OrderNumber"))
				// Reorder the res
				$res = $linq->reorder($res, Base_Files::IDS_DB[$macro], true);

			return $res;
		}

		// Post
		public static function saveContentManager($idRow, $macro, $type = null, $extras = new stdClass(), $external_files = [], $save_on_table = true) {

			$response = array();

			// Get ophelper
			$opHelper = new Base_OperationsHelper();
			$linq = new Base_LINQHelper();

			// Get the files
			$files = count($external_files) == 0 ? $_FILES : self::simulate_File($external_files, $idRow, $macro);

			// Check if the file is not null
			if(!Base_Functions::IsNullOrEmpty($files)) {

				// Test
				$file_path = self::buildPath($idRow, $macro, $type);

				// Create path if not exists
				if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $file_path))
					mkdir($_SERVER["DOCUMENT_ROOT"] . $file_path, 0755, true);

				// Get files number
				$files_number = count($files["Files"]["name"]);
				
				// Init order number
				$order_number = 1;

				// Check if the files are to save on the table
				if ($save_on_table) {

					// Get the order number of the last file
					$maxOrder = $linq->selectDB("MAX(OrderNumber) AS OrderNumber")->fromDB(self::buildDbName($macro, $type))->whereDB(Base_Files::IDS_DB[$macro] . " = $idRow")->getFirstOrDefault();
	
					// Get the order number
					$order_number = !Base_Functions::IsNullOrEmpty($maxOrder) ? $maxOrder->OrderNumber + 1 : 1;
				}

				// Upload files
				for ($i=0; $i < $files_number; $i++) { 

					$original_name = $files["Files"]["name"][$i];
					$name = in_array($macro, Base_Files::CHANGE_FILE_NAME) ? Base_Functions::FileName($original_name) : $original_name;

					// Create file
					$f = new stdClass();
					$f->name = $name;
					$f->type = $files["Files"]["type"][$i];
					$f->tmp_name = $files["Files"]["tmp_name"][$i];
					$f->error = $files["Files"]["error"][$i];
					$f->size = $files["Files"]["size"][$i];

					// Init resize
					$resize = false;

					// Check if the file is an image
					if(strpos($f->type, "zip") === false && strpos($f->type, "pdf") === false)
						$resize = true;

					// Save file
					$file_manage = new Base_File($file_path);
					$file = $file_manage->offRoot(false)
                                        ->file((array)$f)
                                        ->autoResize($resize)
                                        ->save()[0];
                                        
					// Create the obj to insert
					$file->{Base_Files::IDS_DB[$macro]} = $idRow;
					$file->OrderNumber = $i + $order_number;
					$file->OriginalFileName = $original_name;

					// Cicle all the extras
                    if(!Base_Functions::IsNullOrEmpty($extras))
                        foreach($extras as $key => $value) {

                            if (is_array($value))
                                $value = $value[$i];

                            // Add the extra in the file
                            $file->{$key} = $value;
                        }

					// Add the images in the table
					if ($save_on_table)
						$opHelper->object($file)->table(self::buildDbName($macro, $type))->insert();

					array_push($response, $file);
				}

				// Delete the temp files
				Base_Functions::deleteFiles(OFF_ROOT . "/contents/temp/$macro/$idRow/");
                
				return $response;   
			}

			return false;
		}

		// Put
		public static function updateContenOrderManager($idRow, $macro, $type, $data) {

			// Get ophelper
			$linq = new Base_LINQHelper();

			// Update the files
			foreach ($data as $row) {
					
				// Build the where
				$where = Base_Files_Types::DB_IDS_TYPES[$row->FileExtension ?? $type] . " = " . $row->IdFile . " AND " . Base_Files::IDS_DB[$macro] . " = $idRow";
				
				// Update the order number
				$sql = "UPDATE " . self::buildDbName($macro, $row->Custom ?? $type) . " SET OrderNumber = $row->OrderNumber WHERE $where";
				$linq->queryDB($sql)->getResults();
			}

			// Return
			return true;
		}

		// Delete
		public static function deleteContentManager($idRow, $macro, $type = null) {

			// Get ophelper
			$opHelper = new Base_OperationsHelper();

			// Get linq
			$linq = new Base_LINQHelper();

			// Get the content
			$content = self::getContentManager($idRow, $macro, $type);

			// Check if the content is null
			if(Base_Functions::IsNullOrEmpty($content))
				return false;

			// Create path
			$path = property_exists($content, "FullPath") ? $_SERVER["DOCUMENT_ROOT"] . $content->FullPath : null;

			// check if exists
			if($path && file_exists($path)) 
				// delete file
				unlink($path);
            
			// Delete the row
			$opHelper->object($content)->table(self::buildDbName($macro, $type))->where(Base_Files_Types::DB_IDS_TYPES[$type])->delete();

			// Build the type where
			if(!Base_Functions::IsNullOrEmpty($type) && in_array($macro, Base_Files::DB_TABLES_CAPTIONS_NAMES))
				$type_where = !Base_Functions::IsNullOrEmpty($type) ? "AND ContentType = " . Base_Files_Types::CONTENT_IDS[$type] : "";

			// Build the content ref id where
			$ref_where = "ContentRefId = " . $content->{Base_Files_Types::DB_IDS_TYPES[$type]};

			// Delete the caption
			if (in_array($macro, Base_Files::DB_TABLES_CAPTIONS_NAMES)) {

				// Create the query to delete the caption
				$sql = "DELETE FROM " . Base_Files::DB_TABLES_CAPTIONS_NAMES[$macro] . " WHERE $ref_where $type_where"; 
				$linq->queryDB($sql)->getResults();
			}

			// Return
			return true;
		}


		#region Private Methods

			public static function buildPath($idRow, $macro, $type) {

				// Get type
				$isAdvanced = !Base_Functions::IsNullOrEmpty($type);

				// Get the path
				$path = $isAdvanced ? Base_Files_Path::NAMES[Base_Files_Path::ADVANCED] : Base_Files_Path::NAMES[Base_Files_Path::BASIC];

				// Replace the idRow
				if(!Base_Functions::IsNullOrEmpty($idRow))
					$path = str_replace("{{ID_ROW}}", $idRow, $path);

				// Replace the folder name
				$path = str_replace("{{FOLDER_NAME}}", Base_Files::FOLDER_NAMES[$macro], $path);

				// Check if the type is not null
				if($isAdvanced)
					// Replace the type
					$path = str_replace("{{TYPE_NAME}}", Base_Files_Types::FOLDER_NAMES[$type], $path);
				
				return $path;
			}
			private static function buildDbName($macro, $type) {

				return Base_Files::DB_TABLES_NAMES[$macro] . "_" . Base_Files_Types::DB_TABLES_NAMES[$type];
			}
			private static function simulate_File($files, $idRow, $macro) {

				$empty = true;

				// Init the array
				$simulatedFiles = [
					'name' => [],
					'type' => [],
					'tmp_name' => [],
					'error' => [],
					'size' => []
				];
			
				// Cycle all files
				foreach ($files as $filePath) {

					// Check if the file is a base64 string
					$is_base_64 = self::getBase64String($filePath);

					if (!Base_Functions::IsNullOrEmpty($is_base_64)) {
							
						// Create the file path
						$folderPath = OFF_ROOT . "/contents/temp/$macro/$idRow/";
						// Create the folder if not exists
						if (!file_exists($folderPath))
							mkdir($folderPath, 0755, true);

						// Create the file path
						$filePath = $folderPath . uniqid() . ".jpg";

						// Save the file
						file_put_contents($filePath, base64_decode($is_base_64));
					}

					// Check if the file exists
					if (file_exists($filePath)) {

						array_push($simulatedFiles['name'], basename($filePath));         
						array_push($simulatedFiles['type'], mime_content_type($filePath));
						array_push($simulatedFiles['tmp_name'], $filePath);               
						array_push($simulatedFiles['error'], 0);                          
						array_push($simulatedFiles['size'], filesize($filePath));         

						$empty = false;
					}
				}
				
				$response = array();

				if (!$empty)
					$response['Files'] = $simulatedFiles;

				return $response;
			}
			private static function getBase64String($string) {

				// Explode base64
				$base_64_explode = explode('base64,', $string);

				// Check if the $filePath is a base64 string
				if (count($base_64_explode) > 1) {

					// Get the base64 string
					$base_64 = $base_64_explode[1];

					// Check if the $filePath is a valid base64 string
					if (base64_encode(base64_decode($base_64, true)))
						return $base_64;
				}

				return null;
			}

		#endregion

	#endregion

	#region Private Methods

		private function setDefaults() {
			$this->__files = array();
			$this->__quality = 75;
			$this->__width = 1920;
			$this->__height = 1278;
			$this->__autoResize = true;
			$this->__overwrite = false;
		}
		private function IsNullOrEmpty($string) {
			$string = strip_tags(html_entity_decode($string));
			$string = preg_replace('/\s/', '', $string);
			return ($string == null || $string == "");
		}
		private function cleanProperties() {
			foreach ($this as $key => $value) {

				// Clean all but not the path!
				if($key != "__path" && $key != "__fullPath" && $key != "__offRoot") {
					unset($this->$key);
				}
			}

			$this->setDefaults();
		}

	#endregion


}