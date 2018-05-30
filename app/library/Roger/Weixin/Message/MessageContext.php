<?php

namespace Roger\Weixin\Message;

class MessageContext
{
    private $access_token;
    private $errmsg;
    private $message = [];
    private static $appId = 'wxf77bc44ac0f9e6e1';
    private static $key = 'vUkzxmvgUY5DDSf9MAvrCT1QkXM2ubNIAKC0XvuEw5I=&f=tAiUyGSnPOCT9vW0sg553eMB/COUd3P5j3OVGb8klvg=';
    private static $authUrl = 'https://auth.signp.cn';
    private $projectId;

//    private static $authUrl = 'http://127.0.0.1:9012';
    public function __construct($projectId = 0)
    {
        $this->projectId = $projectId;
    }

    public function getAccessToken()
    {
        $timestamp = time();
        $sign = md5($this->projectId . $timestamp . self::$key);
        $data = ['timestamp' => $timestamp, 'sign' => $sign, 'project_id' => $this->projectId];
        $multiRequest = new \Roger\Request\MultiRequest();
        $request = new \Roger\Request\Request(sprintf('%s/%s', self::$authUrl, 'wx/token'),
            \Roger\Request\Request::POST, $data);
        $multiRequest->addRequest($request);
        $multiRequest->execute();
        $result = $multiRequest->getResult();
        $token = json_decode($result);
        if (!empty($token->errmsg)) {
            return "";
        }
        return $token->accessToken;
    }

    public function getAppId()
    {
        $timestamp = time();
        $sign = md5($this->projectId . $timestamp . self::$key);
        $data = ['timestamp' => $timestamp, 'sign' => $sign, 'project_id' => $this->projectId];
        $multiRequest = new \Roger\Request\MultiRequest();
        $request = new \Roger\Request\Request(sprintf('%s/%s', self::$authUrl, 'wx/app'),
            \Roger\Request\Request::POST, $data);
        $multiRequest->addRequest($request);
        $multiRequest->execute();
        $result = $multiRequest->getResult();
        $token = json_decode($result);
        if (!empty($token->errmsg)) {
            return "";
        }
        return $token->appId;
    }

    public function getApp()
    {
        $timestamp = time();
        $sign = md5($this->projectId . $timestamp . self::$key);
        $data = ['timestamp' => $timestamp, 'sign' => $sign, 'project_id' => $this->projectId];
        $multiRequest = new \Roger\Request\MultiRequest();
        $request = new \Roger\Request\Request(sprintf('%s/%s', self::$authUrl, 'wx/app'),
            \Roger\Request\Request::POST, $data);
        $multiRequest->addRequest($request);
        $multiRequest->execute();
        $result = $multiRequest->getResult();
        $token = json_decode($result);
        if (!empty($token->errmsg)) {
            return "";
        }
        return $token;
    }

        public function reset()
    {
        $this->message = [];
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getJsapiTicket()
    {
        $timestamp = time();
        $sign = md5($this->projectId . $timestamp . self::$key);
        $data = ['timestamp' => $timestamp, 'sign' => $sign, 'project_id' => $this->projectId];
        $multiRequest = new \Roger\Request\MultiRequest();
        $request = new \Roger\Request\Request(sprintf('%s/%s', self::$authUrl, 'wx/jsapiticket'),
            \Roger\Request\Request::POST, $data);
        $multiRequest->addRequest($request);
        $multiRequest->execute();
        $result = $multiRequest->getResult();
        $token = json_decode($result);
        if (!empty($token->errmsg)) {
            return "";
        }
        return $token->ticket;
    }

    public function getErrmsg()
    {
        return $this->errmsg;
    }
}