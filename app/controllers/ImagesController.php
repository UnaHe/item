<?php
/**
 * Created by PhpStorm.
 * User: 何杨涛
 * Date: 2018/6/11
 * Time: 11:33
 */

use OSS\OssClient;

/**
 * 图片控制器
 * Class ImagesController
 */
class ImagesController extends ControllerBase
{
    /**
     * 图片列表.
     */
    public function coverlistAction()
    {
        // 参数.
        $project_id = $this->user['project_id'];

        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'images_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'required',
                ],
                'images_main' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 1,
                    ],
                ],
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }

            //获取参数.
            $input = $filter->getResult();

            $Images = new ImagesModel();
            // 获取图片.
            $oldImages = $Images->getImageByProjectId($project_id, 'cover');
            $newImages = $Images->getImageByImagesId($input['images_id']);
            if (!$newImages) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }
            $cloneNewImages = $Images::cloneResult($Images, $newImages);

            $this->db->begin();
            try {
                if ($input['images_main'] == 1) {
                    // 更换主图.
                    if ($oldImages !== false) {
                        $cloneOldImages = $Images::cloneResult($Images, $oldImages);
                        $cloneOldImages->update(['images_main' => 0]);
                    }
                    $cloneNewImages->update(['images_main' => 1]);
                } else {
                    // 不设主图.
                    $cloneNewImages->update(['images_main' => 0]);
                }
                $this->db->commit();
                $this->resultModel->setResult('0');
                return $this->resultModel->output();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
        }

        // 查询.
        $imageList = (new ImagesModel())->getList($project_id, 'cover');

        $this->view->setVars([
            'ali_path' => 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/',
            'project_id' => $project_id,
            'imageList' => $imageList,
        ]);

        return true;
    }

    /**
     * 删除图片.
     */
    public function deleteimageAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $images_id = $this->request->getPost('images_id');

            if ($images_id <= 0) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }

            // 查询图片.
            $Images = new ImagesModel();
            $image = $Images->getImageByImagesId($images_id);
            if (!$image) {
                $this->resultModel->setResult('-1');
                return $this->resultModel->output();
            }

            if ($image['images_main']) {
                $this->resultModel->setResult('400', '该图片为当前主图,无法删除');
                return $this->resultModel->output();
            }

            // 删除图片.
            try {
                if (!empty($image['images_path']) && $image['images_path_source'] == self::SourceOss) {
                    $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);
                    $ossClient->deleteObject(self::$DefaultBucket, $image['images_path']);
                } else if(!empty($image['images_path']) && $image['images_path_source'] == self::SourceLocale) {
                    @unlink(APP_PATH . 'public' . $image['images_path']);
                }
            } catch (Exception $e) {
                $this->logger->addMessage('oss upload > article icon:' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                if(!empty($image['images_path']) && $image['images_path_source'] == self::SourceLocale) {
                    @unlink(APP_PATH . 'public' . $image['images_path']);
                }
            }

            // 删除记录.
            $cloneImages = $Images::cloneResult($Images, $image);
            $this->db->begin();
            try {
                $cloneImages->delete();

                $this->db->commit();
                $this->resultModel->setResult('0');
                return $this->resultModel->output();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
        }

        return false;
    }

    /**
     * 封面上传保存.
     */
    public function coveruploadAction()
    {
        // 参数.
        $project_id = $this->user['project_id'];

        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = [
                'images_type' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'required',
                ],
                'images_path' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'default' => '',
                ],
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }

            //获取参数.
            $input = $filter->getResult();

            try {
                // 上传图片.
                if ($input['images_path'] != '' && strpos($input['images_path'], '/') == 0) {
                    $ossClient = new OssClient(static::$AccessKeyId, static::$AccessKeySecret, static::$EndPoint);
                    $ossClient->setConnectTimeout(5);

                    $objectName = ltrim($input['images_path'], '/');

                    $ossClient->putObject(self::$DefaultBucket, $objectName, file_get_contents(APP_PATH . 'public/' . $objectName));
                    $input['images_path'] = $objectName;
                    $input['images_path_source'] = self::SourceOss;
                    @unlink(APP_PATH . 'public/' . $objectName);
                }
            } catch (Exception $e) {
                $this->logger->addMessage('oss upload > article icon:' . $e->getMessage(), Phalcon\Logger::CRITICAL);
                $this->resultModel->setResult('400', '图片上传失败,请重试');
                return $this->resultModel->output();
//                $input['images_path_source'] = self::SourceLocale;
            }

            // 模型.
            $Images = new ImagesModel();
            $cloneImages = $Images::cloneResult($Images, []);

            $this->db->begin();
            try {
                $input['images_project_id']= $project_id;

                //创建.
                $cloneImages->create($input);

                $this->db->commit();
                $this->resultModel->setResult('0');
                return $this->resultModel->output();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->resultModel->setResult('102');
                return $this->resultModel->output();
            }
        }

        return true;
    }

}