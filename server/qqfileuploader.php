<?php
/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getMimeType() {
        return isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : $_SERVER["HTTP_CONTENT_TYPE"];   // maybe empty in fast/cgi
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Content length не поддерживается');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getMimeType() {
        return $_FILES['qqfile']['type'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    protected $file;
    protected $options;
    protected $image_objects = array();
    protected $mime_types = array(
        //applications
        "" => "application/octet-stream",
        "atom" => "application/atom+xm",
        "x12" => "application/edi-x12",
        "edifact" => "application/edifact",
        "json" => "application/json",
        "js" => array("application/javascript", "text/javascript"),
        "ogg" => "application/ogg",
        "pdf" => "application/pdf",
        "post" => "application/postscript",
        "soap" => "application/soap+xml",
        "woof" => "application/x-woff",
        "xhtml" => "application/xhtml+xml",
        "dtd" => "application/xml-dtd",
        "xop" => "application/xop+xml",
        "zip" => array("application/zip", "application/octet-stream"),
        "gz" => "application/x-gzip",
        "torrent" => "application/x-bittorrent",
        "7z" => array("application/x-7z-compressed", "application/octet-stream"),
        "rar" => array("application/x-rar-compressed", "application/x-rar", "application/octet-stream"),
        
        // audio
        "mulaw" => "audio/basic",
        "pcm" => "audio/l24",
        "mp4" => array("audio/mp4", "video/mp4"),
        "mp3" => "audio/mpeg",
        "oga" => "audio/ogg",
        "wma" => "audio/x-ms-wma",
        "wav" => "audio/vnd.wave",
        "webm" => array("audio/webm", "video/webm"),
        "rm" => "audio/vnd.rn-realaudio",
        
        // image
        "gif" => "image/gif",
        "jpeg" => array("image/jpeg", "image/pjpeg"),
        "jpg" => array("image/jpeg", "image/pjpeg"),
        "png" => "image/png",
        "svg" => "image/svg+xml",
        "tiff" => "image/tiff",
        "ico" => "image/vnd.microsoft.icon",
        "bmp" => "image/vnd.wap.wbmp",
        "djvu" => array("image/vnd.djvu", "image/x-djvu", "application/octet-stream"),
        
        // text
        "cmd" => "text/cmd",
        "css" => "text/css",
        "csv" => "text/csv",
        "html" => "text/html",
        "txt" => "text/plain",
        "xml" => "text/xml",
        "rtf" => array("text/rtf", "application/rtf"),
        
        // video
        "mpeg" => "video/mpeg",
        "mov" => "video/quicktime",
        "avi" => "video/x-ms-wmv",
        "flv" => "video/x-flv",
        
        // vnd
        "odt" => array("application/vnd.oasis.opendocument.text", "application/zip"),
        "ods" => array("application/vnd.oasis.opendocument.spreadsheet", "application/octet-stream"),
        "odp" => "application/vnd.oasis.opendocument.presentation",
        "odg" => "application/vnd.oasis.opendocument.graphics",
        "xls" => array("application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"),
        "xlsx" => array("application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/zip"),
        "ppt" => array("application/msword", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation", "application/vnd.ms-office"),
        "pptx" => array("application/vnd.ms-powerpoint", "application/zip"),
        "pps" => array("application/vnd.ms-pps", "application/msword", "application/vnd.ms-office"),
        "ppsx" => array("application/vnd.ms-pps", "application/zip"),
        "doc" => array("application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"),
        "docx" => array("application/msword", "application/zip")
        
    );
    
    function __construct($options = array()){        
        $this->options = array(
            "allowedExtensions" => array(),
            "sizeLimit" => 10485760,        // 10Mb
            "image_file_types" => '/\.(gif|jpe?g|png|tiff|bmp)$/i',
            'thumbnail' => array(
                // Uncomment the following to use a defined directory for the thumbnails
                // instead of a subdirectory based on the version identifier.
                // Make sure that this directory doesn't allow execution of files if you
                // don't pose any restrictions on the type of uploaded files, e.g. by
                // copying the .htaccess file from the files directory for Apache:
                //'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
                //'upload_url' => $this->get_full_url().'/thumb/',
                // Uncomment the following to force the max
                // dimensions and e.g. create square thumbnails:
                //'crop' => true,
                'jpeg_quality' => 95,
                'png_quality' => 95,
                'max_width' => 80,
                'max_height' => 80
            )   
        );
        
        if ($options) {
            $this->options = $options + $this->options;
        }
        
        $this->options['allowedExtensions'] = array_map("strtolower", $this->options['allowedExtensions']);
        $this->options['allowedMimeTypes'] = array_map("strtolower", $this->options['allowedMimeTypes']);
        $this->options['sizeLimit'] = intval($this->options['sizeLimit']);
        
        //$this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }

    public function getFileName()
    {
        return $this->file->getName();
    }

    private function checkServerSettings($fileSize) {        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $fileSize || $uploadSize < $fileSize) {
            $size = Oneway\IO\CExtendFile::HumanBytes($fileSize);
            $postSize = Oneway\IO\CExtendFile::HumanBytes($postSize);
            $uploadSize = Oneway\IO\CExtendFile::HumanBytes($uploadSize);
            throw new Exception("Превышен лимит на размер загружаемого файла ($size) в настройках PHP: post_max_size ($postSize) или upload_max_filesize ($uploadSize)");    
        }      
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    protected function gd_get_image_object($file_path, $func, $no_cache = false) {
        if (empty($this->image_objects[$file_path]) || $no_cache) {
            $this->gd_destroy_image_object($file_path);
            $this->image_objects[$file_path] = $func($file_path);
        }
        return $this->image_objects[$file_path];
    }

    protected function gd_set_image_object($file_path, $image) {
        $this->gd_destroy_image_object($file_path);
        $this->image_objects[$file_path] = $image;
    }

    protected function gd_destroy_image_object($file_path) {
        $image = @$this->image_objects[$file_path];
        return $image && imagedestroy($image);
    }

    protected function gd_imageflip($image, $mode) {
        if (function_exists('imageflip')) {
            return imageflip($image, $mode);
        }
        $new_width = $src_width = imagesx($image);
        $new_height = $src_height = imagesy($image);
        $new_img = imagecreatetruecolor($new_width, $new_height);
        $src_x = 0;
        $src_y = 0;
        switch ($mode) {
            case '1': // flip on the horizontal axis
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                break;
            case '2': // flip on the vertical axis
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;
            case '3': // flip on both axes
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;
            default:
                return $image;
        }
        imagecopyresampled(
            $new_img,
            $image,
            0,
            0,
            $src_x,
            $src_y,
            $new_width,
            $new_height,
            $src_width,
            $src_height
        );
        return $new_img;
    }

    protected function gd_orient_image($file_path, $src_img) {
        if (!function_exists('exif_read_data')) {
            return false;
        }
        $exif = @exif_read_data($file_path);
        if ($exif === false) {
            return false;
        }
        $orientation = intval(@$exif['Orientation']);
        if ($orientation < 2 || $orientation > 8) {
            return false;
        }
        switch ($orientation) {
            case 2:
                $new_img = $this->gd_imageflip(
                    $src_img,
                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                );
                break;
            case 3:
                $new_img = imagerotate($src_img, 180, 0);
                break;
            case 4:
                $new_img = $this->gd_imageflip(
                    $src_img,
                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                );
                break;
            case 5:
                $tmp_img = $this->gd_imageflip(
                    $src_img,
                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                );
                $new_img = imagerotate($tmp_img, 270, 0);
                imagedestroy($tmp_img);
                break;
            case 6:
                $new_img = imagerotate($src_img, 270, 0);
                break;
            case 7:
                $tmp_img = $this->gd_imageflip(
                    $src_img,
                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                );
                $new_img = imagerotate($tmp_img, 270, 0);
                imagedestroy($tmp_img);
                break;
            case 8:
                $new_img = imagerotate($src_img, 90, 0);
                break;
            default:
                return false;
        }
        $this->gd_set_image_object($file_path, $new_img);
        return true;
    }

    protected function get_scaled_image_file_paths($file_name) {
        $file_path = $this->get_upload_path($file_name);
        $new_file_path = $file_path;
        
        return array($file_path, $new_file_path);
    }
    
    /**
     * Create thumbnail image
     * @param type $file_name
     * @param type $options = array(max_width, max_height)
     * @return boolean
     * @throws Exception
     */
    protected function gd_create_scaled_image($file_path, $new_file_path, $type, $options) {
        if (!function_exists('imagecreatetruecolor')) {
            throw new Exception('Не найдена функция: imagecreatetruecolor');
            return false;
        }
        
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                $src_func = 'imagecreatefromjpeg';
                $write_func = 'imagejpeg';
                $image_quality = isset($options['jpeg_quality']) ? $options['jpeg_quality'] : 95;
                break;
            case 'gif':
                $src_func = 'imagecreatefromgif';
                $write_func = 'imagegif';
                $image_quality = null;
                break;
            case 'png':
                $src_func = 'imagecreatefrompng';
                $write_func = 'imagepng';
                $image_quality = isset($options['png_quality']) ? $options['png_quality'] : 9;
                break;
            default:
                return false;
        }
        $src_img = $this->gd_get_image_object(
            $file_path,
            $src_func,
            !empty($options['no_cache'])
        );
        $image_oriented = false;
        if (!empty($options['auto_orient']) && $this->gd_orient_image(
                $file_path,
                $src_img
            )) {
            $image_oriented = true;
            $src_img = $this->gd_get_image_object(
                $file_path,
                $src_func
            );
        }
        $max_width = $img_width = imagesx($src_img);
        $max_height = $img_height = imagesy($src_img);
        if (!empty($options['max_width'])) {
            $max_width = $options['max_width'];
        }
        if (!empty($options['max_height'])) {
            $max_height = $options['max_height'];
        }
        $scale = min(
            $max_width / $img_width,
            $max_height / $img_height
        );
        if ($scale >= 1) {
            if ($image_oriented) {
                return $write_func($src_img, $new_file_path, $image_quality);
            }
            if ($file_path !== $new_file_path) {
                return copy($file_path, $new_file_path);
            }
            return true;
        }
        if (empty($options['crop'])) {
            $new_width = $img_width * $scale;
            $new_height = $img_height * $scale;
            $dst_x = 0;
            $dst_y = 0;
            $new_img = imagecreatetruecolor($new_width, $new_height);
        } else {
            if (($img_width / $img_height) >= ($max_width / $max_height)) {
                $new_width = $img_width / ($img_height / $max_height);
                $new_height = $max_height;
            } else {
                $new_width = $max_width;
                $new_height = $img_height / ($img_width / $max_width);
            }
            $dst_x = 0 - ($new_width - $max_width) / 2;
            $dst_y = 0 - ($new_height - $max_height) / 2;
            $new_img = imagecreatetruecolor($max_width, $max_height);
        }
        // Handle transparency in GIF and PNG images:
        switch ($type) {
            case 'gif':
            case 'png':
                imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
            case 'png':
                imagealphablending($new_img, false);
                imagesavealpha($new_img, true);
                break;
        }
        $success = imagecopyresampled(
            $new_img,
            $src_img,
            $dst_x,
            $dst_y,
            0,
            0,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        ) && $write_func($new_img, $new_file_path, $image_quality);
        $this->gd_set_image_object($file_path, $new_img);
        return $success;
    }
    
    protected function is_valid_image_file($file_path) {
        if (!preg_match($this->options['image_file_types'], $file_path)) {
            return false;
        }
        $image_info = $this->get_image_size($file_path);
        return $image_info && $image_info[0] && $image_info[1];
    }
    
    protected function get_image_size($file_path) {
        if (!function_exists('getimagesize')) {
            throw new Exception('PHP функция не определена в настройках PHP: getimagesize');
            return false;
        }
        return @getimagesize($file_path);
    }
    
    protected function get_file_url($file_name)
    {
        $root = $_SERVER["DOCUMENT_ROOT"];
        $url = "/" . str_replace($root, "", $file_name);
        return str_replace("//", "/", $url);
    }
    
    protected function check_mime_type($ext, $mime)
    {
        $ext = strtolower($ext);
        $realMime = $this->mime_types[$ext];
        $validMime = false;
        
        if (is_array($realMime)) {
            $validMime = in_array($mime, $realMime);
        }
        
        if (is_string($realMime)) {
            $validMime = $realMime == $mime;
        }
        
        return $validMime;
    }
    
    public function handleDelete($file_path) {
        $result = false;
        if (file_exists($file_path)) {
            $result = @unlink($file_path);
        }
        
        if ($result) {
            $result = array("success" => true);
        } else {
            $result = array("error" => "Файл не найден или защищен от удаления");
        }
        
        return $result;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    public function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        if (!file_exists($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0755, true)) {
                return array("error" => "Не достаточно прав на создание директории ".$this->get_file_url($uploadDirectory));
            }
        }
        
        if (!is_writable($uploadDirectory)){
            return array("error" => "Директория загрузки ".$this->get_file_url($uploadDirectory)." защищена от записи");
        }
        
        if (!$this->file){
            return array("error" => "Файл не выбран");
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array("error" => "Вы пытаетесь загрузить пустой файл");
        }
        
        if ($size > $this->options['sizeLimit']) {
            $sizeLimit = Oneway\IO\CExtendFile::HumanBytes($this->options['sizeLimit']);
            return array("error" => "Размер файла превышает установленный лимит $sizeLimit");
        }
        
        $this->checkServerSettings($size);       
        
        $pathinfo = pathinfo($this->file->getName());
        //$mimetype = $this->file->getMimeType();
        $filename = md5(uniqid());  //$filename = $pathinfo['filename'];
        $ext = strtolower($pathinfo['extension']);
        $fullFileName = $uploadDirectory . $filename . "." . $ext;
        
        if($this->options['allowedExtensions'] && !in_array(strtolower($ext), $this->options['allowedExtensions'])){
            $these = implode(', ', $this->options['allowedExtensions']);
            return array("error" => "Файл должен иметь формат ". $these);
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($fullFileName)) {
                $filename = md5($filename . rand(10, 99));
                $fullFileName = $uploadDirectory . $filename . "." . $ext;
            }
        }
        
        if ($this->file->save($fullFileName)){
            
            $mimetype = strtolower(mime_content_type($fullFileName));
            
            $result = array(
                "file" => array(
                    "path" => $fullFileName,
                    "url" => $this->get_file_url($fullFileName),
                    "type" => $mimetype,
                    "size" => $size
                )
            );
            
            /*if(!$this->check_mime_type($ext, $mimetype)) {
                @unlink($fullFileName);
                return array("error" => "Неверный тип файла (mime)");
            }*/
                
            if ($this->is_valid_image_file($fullFileName)) {
                
                $result["success"] = true;
                $result["type"] = "image";
                
                $imagesize = getimagesize($fullFileName);
                $result["file"]["width"] = $imagesize[0];
                $result["file"]["height"] = $imagesize[1];
                
                if (!empty($this->options["thumbnail"])) {
                    
                    $uploadDirectoryThumbnail = str_replace("//", "/", dirname($fullFileName) . "/thumbnail/");
                    
                    if (!file_exists($uploadDirectoryThumbnail)) {
                        if (!mkdir($uploadDirectoryThumbnail, 755)) {
                            return array("error" => "Не достаточно прав на создание директории thumbnail в папке " . $this->get_file_url($uploadDirectory));
                        }
                    }

                    $thumbFileName = str_replace("//", "/", $uploadDirectoryThumbnail . md5(uniqid()) . '.' . $ext);
                    
                    if ($this->gd_create_scaled_image($fullFileName, $thumbFileName, $ext, $this->options["thumbnail"])) {
                        $imagesize = getimagesize($thumbFileName);
                        $result["thumbnail"] = array(
                            "path" => $thumbFileName,
                            "url" => $this->get_file_url($thumbFileName),
                            "type" => mime_content_type($thumbFileName),
                            "width" => $imagesize[0],
                            "height" => $imagesize[1]
                        );
                    } else {
                        unset($result["success"]);
                        $result["error"] = "Не удалось создать миниатюру для картинки";
                    }
                }
                
            } else { // other file types (docs, archiv, audio, video, text, etc)
                $result["success"] = true;
                $result["type"] = "other";
            }
            
            return $result;
        } else {
            return array("error" => "Не удалось сохранить файл. Загрузка остановлена или на сервере возникла ошибка");
        }
    }    
}

?>