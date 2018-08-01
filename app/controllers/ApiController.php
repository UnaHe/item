<?php

/**
 * MarkControllerName(接口管理)
 *
 */
class ApiController extends ControllerBase
{
    public function ajaxinfraredareaupdateAction()
    {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $rules = array(
                // equipment_infrared_id
                'id' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                ),
            );
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();
            $equipmentInfraredModel = new EquipmentInfraredModel();
            $equipmentInfraredDetails = $equipmentInfraredModel->getDetailsById($input['id']);
            if (!$equipmentInfraredDetails) {
                $this->resultModel->setResult('-1', 'infrared');
                return $this->resultModel->output();
            }
            if ($equipmentInfraredDetails['equipment_project_id'] != $this->user['project_id']) {
                $this->resultModel->setResult('-1', 'no access');
                return $this->resultModel->output();
            }
            $equipmentInfraredAreaModel = new EquipmentInfraredAreaModel();
            $equipmentInfraredArea = $equipmentInfraredAreaModel->getList(['equipment_infrared_id' => $equipmentInfraredDetails['equipment_infrared_id']]);
            if (empty($equipmentInfraredArea['data'])) {
                $this->resultModel->setResult('-1', 'areas');
                return $this->resultModel->output();
            }
            $staticBaseUri = $this->url->getStaticBaseUri();
            $content = [
                'type' => 'infrared',
                'cmd' => 'updateInfrared',
                'infraredAreas' => [],
                'source_update' => $equipmentInfraredDetails['equipment_source_update']
            ];
            foreach ($equipmentInfraredArea['data'] as $v) {
                if ($v['equipment_infrared_area_type'] == 2) {
                    $_content = explode(',', $v['equipment_infrared_area_content']);
                    $newContent = [];
                    foreach ($_content as $f) {
                        if ($f == '') continue;
                        $newContent[] = $staticBaseUri . $f;
                    }
                    if (empty($newContent)) continue;
                    $v['equipment_infrared_area_content'] = $newContent;
                }
                $content['infraredAreas'][] = [
                    'type' => $v['equipment_infrared_area_type'],
                    'startPoint_x' => $v['equipment_infrared_area_startPoint_x'],
                    'startPoint_y' => $v['equipment_infrared_area_startPoint_y'],
                    'endPoint_x' => $v['equipment_infrared_area_endPoint_x'],
                    'endPoint_y' => $v['equipment_infrared_area_endPoint_y'],
                    'file' => empty($v['equipment_infrared_area_file']) ? '' : $staticBaseUri . $v['equipment_infrared_area_file'],
                    'content' => empty($v['equipment_infrared_area_content'])?'':$v['equipment_infrared_area_content'],
                ];
            }

            $multiRequest = new Roger\Request\MultiRequest();
            $post_data = [
                'type' => 'tag',
                'content' => json_encode($content),
                'to' => $equipmentInfraredDetails['equipment_project_id'] . '|equipment|' . $equipmentInfraredDetails['equipment_code'],
                'project' => $equipmentInfraredDetails['equipment_project_id'],
            ];
            $multiRequest->addRequest(new Roger\Request\Request($this->view->setting['socketUrl'], Roger\Request\Request::POST,
                $post_data));
            $multiRequest->execute();
            $sendResult = $multiRequest->getResult();
            $this->logger->addMessage('result:' . $sendResult . ' ' . json_encode($post_data));
            $this->resultModel->setResult('0', $sendResult);
            return $this->resultModel->output();
        }
        echo 'xxxx';
    }

    /**
     * 获取项目列表.
     * @return string
     */
    public function wxGetProjectListAction()
    {
        if ($this->request->isPost()) {
            $rules = [
                'page' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => 1
                ],
                'psize' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'default' => 10
                ],
                'usePage' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 1
                    ],
                    'default' => 1
                ],
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $ForwardModel = new ForwardModel();
            $forwardList = $ForwardModel->wxGetProjectList($input);

            $this->resultModel->setResult('0', $forwardList);
            return $this->resultModel->output();
        }

        return 'Request Method Error';
    }

    /**
     * 获取项目URL.
     * @return string
     */
    public function wxGetForwardDetailsAction()
    {
        if ($this->request->isPost()) {
            $rules = [
                'forward_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1
                    ],
                    'required',
                ]
            ];
            $filter = new FilterModel ($rules);
            if (!$filter->isValid($this->request->getPost())) {
                $this->resultModel->setResult('101');
                return $this->resultModel->output();
            }
            $input = $filter->getResult();

            $ForwardModel = new ForwardModel();
            $forwardDetails = $ForwardModel->getDetailsById($input['forward_id']);

            $this->resultModel->setResult('0', $forwardDetails);
            return $this->resultModel->output();
        }

        return 'Request Method Error';
    }

}