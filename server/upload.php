<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
include('qqfileuploader.php');

define("BASE_PATH", "/upload/tmp/");

// list of valid extensions
$allowedExtensions = isset($_REQUEST["allowedExtensions"]) ? split(',', $_REQUEST["allowedExtensions"]) : array();

// max file size in bytes
$sizeLimit = intval($_REQUEST["sizeLimit"]);

// thumbnail sizes
$thumbWidth = intval($_REQUEST["thumbWidth"]);
$thumbHeight = intval($_REQUEST["thumbHeight"]);

// upload folder
$uploadFolder = str_replace("//", "/", $_SERVER["DOCUMENT_ROOT"] . BASE_PATH);

// delete?
$delete = $_REQUEST["delete"];
$filePath = $uploadFolder . str_replace($uploadFolder, "", $_REQUEST["file"]); // sure that file deleted from uploadFolder

// options for qqFileUploader
$options = array();

if (is_array($allowedExtensions) && count($allowedExtensions) > 0) {
    $options["allowedExtensions"] = $allowedExtensions;
}

if ($sizeLimit > 0) {
    $options["sizeLimit"] = $sizeLimit;
}    
    
if (($thumbWidth + $thumbHeight) > 0) {
    $options["thumbnail"] = array("max_width" => $thumbWidth, "max_height" => $thumbHeight);
}

try {
    $uploader = new qqFileUploader($options);
    
    if ($delete) {
        $result = $uploader->handleDelete($filePath);
    } else {
        $result = $uploader->handleUpload($uploadFolder, true);
    }
    
    if (isset($result['error'])) {
        $result['fatal'] = false;
    }
} catch (Exception $e) {
    $result["error"] = $e->getMessage();
    $result["fatal"] = true;
}

$result = Oneway\Text\Encoding::convertEncodingToUTF8($result);
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
?>