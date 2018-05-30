<?php

use OSS\OssClient;

class UploadController extends ControllerBase
{
    private function genUploadFileName($prefix = '')
    {
        return md5(uniqid($prefix, true) . microtime() . mt_rand());
    }

    public function uploadAction()
    {
        $rules = array(
            'part' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'required'
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid(array_merge($this->request->getQuery(), $this->request->getPost()))) {
            $this->resultModel->setResult('101');
            return $this->resultModel->output();
        }
        $input = $filter->getResult();
        set_time_limit(0);
        $POST_MAX_SIZE = ini_get('post_max_size');
        $unit = strtoupper(substr($POST_MAX_SIZE, -1));
        $multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
        if (( int )@$_SERVER ['CONTENT_LENGTH'] > $multiplier * ( int )$POST_MAX_SIZE && $POST_MAX_SIZE) {
            $this->HandleError($this->translate->_('UploadPOSTExceededMaximumAllowedSize'));
        }
        $part = $input['part'];
        $ext_arr = array(
            "image" => array(
                "gif",
                "jpg",
                "jpeg",
                "png",
                "bmp"
            ),
            "flash" => array(
                "swf",
                "flv"
            ),
            "media" => array(
                "swf",
                "flv",
                "mp3",
                "wav",
                "wma",
                "wmv",
                "mid",
                "avi",
                "mpg",
                "asf",
                "rm",
                "rmvb"
            ),
            "file" => array(
                "apk",
                "doc",
                "docx",
                "xls",
                "xlsx",
                "ppt",
                "htm",
                "html",
                "txt",
                "zip",
                "rar",
                "gz",
                "bz2"
            )
        );
        switch ($part) {
            case 'voice':
                $_allow_arr = $ext_arr['media'];
                break;
            case 'musicFile':
                $_allow_arr = ['mp3'];
                break;
            case 'videoFile':
            case 'point_resource_video':
                $_allow_arr = ['mp4'];
                break;
            default:
                $_allow_arr = $ext_arr['image'];
                break;
        }

        // Settings
        $save_path = APP_PATH . 'public/upload/' . $part . '/';
        if (!file_exists($save_path)) {
            umask(0);
            mkdir($save_path, 0777, true);
        }
        switch ($part) {
            case 'client_events':
            case 'client_article':
            case 'client_spots':
                $upload_name = 'upload';
                break;
            default:
                $upload_name = 'Filedata';
                break;
        }
        $max_file_size_in_bytes = 2147483647; // 2GB in bytes

        if (!isset ($_FILES [$upload_name])) {
            $this->HandleError($this->translate->_('UploadNoUploadFound'));
        } else {
            if (isset ($_FILES [$upload_name] ['error']) && $_FILES [$upload_name] ['error'] != 0) {
                $this->HandleError($this->translate->_('UploadFileError'));
            } else {
                if (!isset ($_FILES [$upload_name] ['tmp_name']) || !@is_uploaded_file($_FILES [$upload_name] ['tmp_name'])) {
                    $this->HandleError($this->translate->_('UploadFileError'));
                } else {
                    if (!isset ($_FILES [$upload_name] ['name'])) {
                        $this->HandleError($this->translate->_('UploadFileError'));
                    }
                }
            }
        }

        $file_size = @filesize($_FILES [$upload_name] ['tmp_name']);

        if (!$file_size || $file_size > $max_file_size_in_bytes) {
            $this->HandleError($this->translate->_('UploadPOSTExceededMaximumAllowedSize'));
        }

        if ($file_size <= 0) {
            $this->HandleError($this->translate->_('UploadFileError'));
        }

        $path_info = pathinfo($_FILES [$upload_name] ['name']);
        $file_extension = strtolower($path_info ['extension']);
        if (!in_array($file_extension, $_allow_arr)) {
            $this->HandleError($this->translate->_('UploadFileNotAllowd'));
        }
        $file_name = $this->genUploadFileName($part);

        if (!@move_uploaded_file($_FILES [$upload_name] ['tmp_name'],
            $save_path . $file_name . '.' . $file_extension)
        ) {
            $this->HandleError($this->translate->_('UploadFileSaveFail'));
        }
        $upFile = 'upload/' . $part . '/' . $file_name . '.' . $file_extension;
        switch ($part) {
            case 'device_qrcode':
            case 'map':
            case 'avatar':
                echo '<img src="/' . $upFile . '"><input type="hidden" value="' . $upFile . '" id="' . $part . '" />';
                break;
            case 'dataimport':
            case 'crossdata':
            case 'oss':
                echo '<a href="/' . $upFile . '" target="_blank">' . $upFile . '</a><input type="hidden" value="' . $upFile . '" id="' . $part . '" />';
                break;
            case 'client_events':
            case 'client_article':
            case 'client_spots':
                echo '<script>window.parent.CKEDITOR.tools.callFunction("1", "/' . $upFile . '", "");</script>';
                break;
            default:
                die('/' . $upFile);
                break;
        }
        exit ();
    }

    private function HandleError($message)
    {
        header('HTTP/1.1 500 Internal Server Error');
        die($message);
    }

    public function uploadjsonAction()
    {
        $rules = array(
            "part" => array(
                "filter" => FILTER_SANITIZE_STRING,
                'required'
            ),
            "type" => array(
                "filter" => FILTER_SANITIZE_STRING,
                "default" => "image"
            )
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getQuery())) {
            die ('参数错误');
        }
        $input = $filter->getResult();
        // 文件保存目录路径
        $save_path = APP_PATH . "public/upload/" . $input ['part'] . "/";
        if (!file_exists($save_path)) {
            umask(0);
            mkdir($save_path, 0777, true);
        }

        // 文件保存目录URL
        $save_url = "/upload/" . $input ['part'] . "/";
        // 定义允许上传的文件扩展名
        $ext_arr = array(
            "image" => array(
                "gif",
                "jpg",
                "jpeg",
                "png",
                "bmp"
            ),
            "flash" => array(
                "swf",
                "flv"
            ),
            "media" => array(
                "swf",
                "flv",
                "mp3",
                "wav",
                "wma",
                "wmv",
                "mid",
                "avi",
                "mpg",
                "asf",
                "rm",
                "rmvb"
            ),
            "file" => array(
                "apk",
                "doc",
                "docx",
                "xls",
                "xlsx",
                "ppt",
                "htm",
                "html",
                "txt",
                "zip",
                "rar",
                "gz",
                "bz2"
            )
        );
        // 最大文件大小
        $max_size = 10000000;
        // PHP上传失败
        if (!empty ($_FILES ["imgFile"] ["error"])) {
            switch ($_FILES ["imgFile"] ["error"]) {
                case "1" :
                    $error = "超过配置允许上传的大小。";
                    break;
                case "2" :
                    $error = "超过表单允许的大小。";
                    break;
                case "3" :
                    $error = "图片只有部分被上传。";
                    break;
                case "4" :
                    $error = "请选择图片。";
                    break;
                case "6" :
                    $error = "找不到临时目录。";
                    break;
                case "7" :
                    $error = "写文件到硬盘出错。";
                    break;
                case "8" :
                    $error = "File upload stopped by extension。";
                    break;
                case "999" :
                default :
                    $error = "未知错误。";
            }
            die ($error);
        }

        // 有上传文件时
        if (empty ($_FILES) === false) {
            // 原文件名
            $file_name = $_FILES ["imgFile"] ["name"];
            // 服务器上临时文件名
            $tmp_name = $_FILES ["imgFile"] ["tmp_name"];
            // 文件大小
            $file_size = $_FILES ["imgFile"] ["size"];
            // 检查文件名
            if (!$file_name) {
                die ("请选择文件。");
            }
            // 检查目录
            if (@is_dir($save_path) === false) {
                die ("上传目录不存在。");
            }
            // 检查目录写权限
            if (@is_writable($save_path) === false) {
                die ("上传目录没有写权限。");
            }
            // 检查是否已上传
            if (@is_uploaded_file($tmp_name) === false) {
                die ("上传失败。");
            }
            // 检查文件大小
            if ($file_size > $max_size) {
                die ("上传文件大小超过限制。");
            }

            // 检查目录名
//            if (empty ($ext_arr [$input ['type']])) {
//                die ("目录名不正确。");
//            }
            // 获得文件扩展名
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            // 检查扩展名
//            if (in_array($file_ext, $ext_arr [$input ['type']]) === false) {
//                die ("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr [$input ['type']]) . "格式。");
//            }
            // 新文件名
            $new_file_name = $this->genImageName($input['part']) . "." . $file_ext;
            // 移动文件
            $file_path = $save_path . $new_file_name;
            if (move_uploaded_file($tmp_name, $file_path) === false) {
                die ("上传文件失败。");
            }
            @chmod($file_path, 0644);
            $file_url = $save_url . $new_file_name;

            header("Content-type: text/html; charset=UTF-8");
            echo json_encode(array(
                "error" => 0,
                "url" => $file_url
            ));
        }
    }

    public function uploadtoossAction()
    {
        set_time_limit(0);
        $POST_MAX_SIZE = ini_get('post_max_size');
        $unit = strtoupper(substr($POST_MAX_SIZE, -1));
        $multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
        if (( int )@$_SERVER ['CONTENT_LENGTH'] > $multiplier * ( int )$POST_MAX_SIZE && $POST_MAX_SIZE) {
            $this->HandleError($this->translate->_('UploadPOSTExceededMaximumAllowedSize'));
        }
        $part = !empty($_GET['part']) ? $_GET['part'] : 'oss';
        $message = '上传成功';
        $ext_arr = array(
            "image" => array(
                "gif",
                "jpg",
                "jpeg",
                "png",
                "bmp"
            ),
        );
        switch ($part) {
            default:
                $_allow_arr = $ext_arr['image'];
                break;
        }

        // Settings
        $save_path = APP_PATH . 'public/upload/' . $part . '/';
        if (!file_exists($save_path)) {
            umask(0);
            mkdir($save_path, 0777, true);
        }
        $upload_name = 'upload';
        $max_file_size_in_bytes = 2147483647; // 2GB in bytes

        if (!isset ($_FILES [$upload_name])) {
            $this->HandleError($this->translate->_('UploadNoUploadFound'));
        } else {
            if (isset ($_FILES [$upload_name] ['error']) && $_FILES [$upload_name] ['error'] != 0) {
                $this->HandleError($this->translate->_('UploadFileError'));
            } else {
                if (!isset ($_FILES [$upload_name] ['tmp_name']) || !@is_uploaded_file($_FILES [$upload_name] ['tmp_name'])) {
                    $this->HandleError($this->translate->_('UploadFileError'));
                } else {
                    if (!isset ($_FILES [$upload_name] ['name'])) {
                        $this->HandleError($this->translate->_('UploadFileError'));
                    }
                }
            }
        }

        $file_size = @filesize($_FILES [$upload_name] ['tmp_name']);

        if (!$file_size || $file_size > $max_file_size_in_bytes) {
            $this->HandleError($this->translate->_('UploadPOSTExceededMaximumAllowedSize'));
        }

        if ($file_size <= 0) {
            $this->HandleError($this->translate->_('UploadFileError'));
        }

        $path_info = pathinfo($_FILES [$upload_name] ['name']);
        $file_extension = strtolower($path_info ['extension']);
        if (!in_array($file_extension, $_allow_arr)) {
            $this->HandleError($this->translate->_('UploadFileNotAllowd'));
        }
        $file_name = $this->genUploadFileName($part);

        if (!@move_uploaded_file($_FILES [$upload_name] ['tmp_name'],
            $save_path . $file_name . '.' . $file_extension)
        ) {
            $this->HandleError($this->translate->_('UploadFileSaveFail'));
        }

        $upFile = 'upload/' . $part . '/' . $file_name . '.' . $file_extension;

        $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
        $ossClient->setConnectTimeout(5);
        try {
            $ossClient->putObject(self::$DefaultBucket, $upFile,
                file_get_contents(APP_PATH . 'public/' . $upFile));
            @unlink(APP_PATH . 'public/' . $upFile);
            $file_full_src = 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/' . $upFile;
        } catch (Exception $e) {
            $this->logger->addMessage('oss upload > article: ' . $e->getMessage(),
                Phalcon\Logger::CRITICAL);
            $file_full_src = "/{$upFile}";
        }
        switch ($part) {
            case 'article':
                $fn = $_GET['CKEditorFuncNum'];
                die('<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(' . $fn . ',"' . $file_full_src . '","' . $message . '");</script>');
            default:
                exit ($part);
        }
    }

    public function removeAction()
    {
        $rules = array(
            'url' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'required'
            ),
        );
        $filter = new FilterModel ($rules);
        if (!$filter->isValid($this->request->getPost())) {
            $this->resultModel->setResult('101');
            return $this->resultModel->output();
        }
        $input = $filter->getResult();
        $fileUrl = APP_PATH . 'public/' . ltrim($input['url'], '\/');
        if (!file_exists($fileUrl)) {
            $this->resultModel->setResult('-1');
            return $this->resultModel->output();
        }
        @unlink($fileUrl);
        $this->resultModel->setResult('0');
        return $this->resultModel->output();
    }
}