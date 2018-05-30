<?php

namespace Roger\Weixin\Message;

class MessageContextClient
{
    private $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }


    public function getAppId()
    {
        switch ($this->projectId) {
            case "0":
                $appId = 'wxf77bc44ac0f9e6e1';
                break;
            case "45":
                $appId = 'wxa19bf0c7194803eb';
                break;
            default:
                $appId = "";
                break;
        }
        return $appId;
    }
}