<?php

class FilterModel {

    private $rules;
    private $result;
    private $errMsg = array();
    private $signTimeLimit = 1800;
    private $resultModel = null;
    private $keys = array(
        'android' => '56a8d122ec0d330d6d9f541b459e43e1',
        'iphone' => '56a8d122ec0d330d6d9f541b459e43e1'
    );

    public function __construct($rules = null, $resultModel = null) {
        if (!is_null($rules))
            $this->setRules($rules);
        if (!is_null($resultModel))
            $this->setResultModel($resultModel);
    }

    public function setRules($rules) {
        $this->rules = $rules;
    }

    public function setResultModel($resultModel) {
        $this->resultModel = $resultModel;
    }

    public function isValid(array $params) {
        if (empty($this->rules)) {
            $this->addErrMsg('no rules found');
            return false;
        }
        $result = $this->filterInput($params, $this->rules);
        if (!empty($this->errMsg)) {  //如果错误信息不为空则验证失败
            return false;
        }
        $this->result = $result; //否则验证成功，为结果复制
        return true;
    }

    public function addErrMsg($msg) {
        $this->errMsg [] = $msg;
    }

    public function getErrMsg() {
        return $this->errMsg;
    }

    public function getResult() {
        return $this->result;
    }

    private function filterInput($params, $rules) {
        if (is_array($params)) {
            $input = filter_var_array($params, $rules); //PHP内置过滤

            foreach ($rules as $var => $rule) {
                if (is_null($input [$var]) || $input [$var] === false) {
                    if (array_key_exists('default', $rule)) { //如果有默认值设置为默认值
                        $input [$var] = $rule ['default'];
                    } else {
                        $this->addErrMsg('parameter: ' . $var . ' is not valid');//如果没有设置默认值则 增加验证错误信息
                    }
                } elseif ($input[$var] === '' && in_array('required', $rule)) { //如果值为空，但设置为必填则 增加验证错误信息
                    $this->addErrMsg('parameter: ' . $var . ' required');
                } else {
                    if(is_array($input[$var])){
                        $input[$var] = array_filter($input[$var]); //去空值
                    }else{
                        $input[$var] = trim($input[$var]);
                    }
                }
            }
        } else {
            $input = filter_var($params, $rules ['filter'], $rules ['options']);
            if (is_null($input) || $input === false) {
                if (isset($rules ['default'])) {
                    $input = $rules ['default'];
                } else
                    $this->addErrMsg('parameter: ' . $params . ' is not valid');
            } else {
                if ($input == '' && isset($rules ['required'])) {
                    $this->addErrMsg('parameter: ' . $params . ' required');
                } else {
                    $input = trim($input);
                }
            }
        }
        return $input;
    }

    public function valid(array $params, $debug = false) {
        $result = $this->filterInput($params, $this->rules);
        if (!empty($this->errMsg) || !array_key_exists($result ['from'], $this->keys)) {
            $this->resultModel->setResult('101');
            return $this->resultModel->output();
        }
        $this->result = $result;
        if (!$this->verifyInput($this->keys [$result ['from']], $debug)) {
            $this->resultModel->setResult('103');
            return $this->resultModel->output();
        }
        return true;
    }

    private function verifyInput($key, $debug = false, $encryptType = 'md5') {
        $_sign = '';
        $ignoreParams = array(
            'usePage',
            'sign'
        );
        ksort($this->result);
        $debugInfo = '';
        foreach ($this->result as $k => $v) {
            if (in_array($k, $ignoreParams)) {
//                $debugInfo .= '(ignore) '.$k.'='.$v."\n";
                continue;
            }
            $debugInfo .= $k . '=' . $v . "\n";
            $_sign .= $v;
        }
        $_sign .= $key;
        if ($debug) {
            echo $debugInfo;
            echo 'from sign:' . $this->result ['sign'] . "\n";
            echo 'locale sign:' . md5($_sign) . "\n";
            echo 'sign string:' . $_sign . "\n";
            die();
        }
        if ((time() - $this->result ['timestamp']) > $this->signTimeLimit) {
            return false;
        }
        switch ($encryptType) {
            case 'md5' :
                if (strcmp($this->result ['sign'], md5($_sign)) == 0)
                    return true;
                return false;
                break;
            default :
                return false;
                break;
        }
    }

}