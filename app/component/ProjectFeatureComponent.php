<?php

use Phalcon\Mvc\User\Component;

class ProjectFeatureComponent extends Component
{
    private $user;
    private $projectFeatures = [];

    public function __construct($user)
    {
        $this->user = $user;
        if (!empty($this->user['project_id'])) {
            $projectFeatureModel = new ProjectFeatureModel();
            $projectFeatureBase = $projectFeatureModel->getListSimple(['type' => 'base']);
            $projectFeatureCustom = !empty($this->user['project_feature'])?$projectFeatureModel->getListSimple(['feature' => $this->user['project_feature']]):['data'=>[]];

            foreach ($projectFeatureCustom['data'] as $v){
                if (!empty($v['project_feature_override'])) {
                    foreach ($projectFeatureBase['data'] as $k => $b) {
                        if ($b['project_feature_id']==$v['project_feature_override']){
                            unset($projectFeatureBase['data'][$k]);
                        }
                    }
                }
            }

            $this->projectFeatures = array_merge($projectFeatureBase['data'], $projectFeatureCustom['data']);
        }
    }

    public function getHtml()
    {
        $return = '';
        if (empty($this->projectFeatures)) {
            return $return;
        }

        $translate = $this->getDI()->get('translate');

        foreach ($this->projectFeatures as $v) {
            if (!empty($v['project_feature_html'])) {
                @preg_match_all('/{{[\'|\"](.*?)[\'|\"]\|\_}}/',$v['project_feature_html'] , $matches);
                if (!empty($matches)){
                    foreach ($matches[0] as $k=>$r){
                        $v['project_feature_html'] = str_replace($r , $translate->_($matches[1][$k]) , $v['project_feature_html']);
                    }
                }
                $return .= $v['project_feature_html'];
            }
        }
        return $return;
    }

    public function getJs()
    {
        $return = '';
        if (empty($this->projectFeatures)) {
            return $return;
        }
        $return = '<script type="text/javascript">';

        foreach ($this->projectFeatures as $v) {
            if (!empty($v['project_feature_js'])) {
                $return .= $v['project_feature_js'];
            }
        }
        $return .= '</script>';
        return $return;
    }
}