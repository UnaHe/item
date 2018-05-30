<?php
namespace Roger\Weixin\Message;

class MessageContext1
{
	private $access_token;
	private $errmsg;
	private $message = [];
    private static $appId = 'wxf77bc44ac0f9e6e1';
    private static $appSecret = 'f8db53082a04d611d3eaf5d8c65bdeaf';

	public function __construct($refresh=0)
	{
		$this->requestAccessToken($refresh);
	}

	public function addMessage(\Roger\Weixin\Message\MessageAbstract $message)
	{
		$this->message[] = $message;
	}

	public function execute()
	{
		if (!empty($this->message)) {
			$request = new \Roger\Request\MultiRequest();
			foreach ($this->message as $m) {
				$m->setAccessToken($this->access_token);
				$r = new \Roger\Request\Request($m->getUrl(), $m->getRequestMethod(), $m->getRequestParams());
				$request->addRequest($r);
			}
			$request->execute();
			$result = $request->getResult();
			if (is_array($result)) {
				$resultJson = $result[0];
			} else {
				$resultJson = $result;
			}
			$resultArray = json_decode($resultJson);
//			\LogModel::log(LOG_FILE_DIR .'resultJson.txt' , serialize(get_object_vars($resultArray)));
			if (isset($resultArray->errcode) && $resultArray->errcode == '40001') {
				$this->requestAccessToken(1);
				$request = new \Roger\Request\MultiRequest();
				foreach ($this->message as $m) {
					$m->setAccessToken($this->access_token);
					$r = new \Roger\Request\Request($m->getUrl(), $m->getRequestMethod(), $m->getRequestParams());
					$request->addRequest($r);
				}
				$request->execute();
				$result = $request->getResult();
			}
			return $result;
		}
		return false;
	}

    public function reset(){
        $this->message = [];
    }

	public function getMessage()
	{
		return $this->message;
	}
	public function getAppId()
	{
		return static::$appId;
	}


	public function requestAccessToken($refresh = 0)
	{
		$settingModel = new \SettingModel();
		$accessToken = $settingModel->getDetailsByKey('setting_wx_access_token');
		$accessTokenExpired = $settingModel->getDetailsByKey('setting_wx_access_token_expired');
		$time = time();
		$remainTime = $accessTokenExpired->value - $time;
		if ($refresh || $remainTime <= 0) {
			$request = new \Roger\Request\MultiRequest();
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . static::$appId. '&secret=' . static::$appSecret;
			$r = new \Roger\Request\Request($url);
			$request->addRequest($r);
			$request->execute();
			$result = $request->getResult();
			$token = json_decode($result);
			if (!$token) {
				$this->errmsg = 'Access Token Request Error!';
				return false;
			}
			if (isset($token->errmsg)) {
				$this->errmsg = $token->errmsg;
				return false;
			}
			$accessToken->value = $token->access_token;
			$accessToken->update();
			$accessTokenExpired->value = $time + $token->expires_in - 300;
			$accessTokenExpired->update();
			$this->access_token = $token->access_token;
		} else {
			$this->access_token = $accessToken->value;
		}
	}

	public function getJsapiTicket($refresh=0)
	{
		$settingModel = new \SettingModel();
		$ticket = $settingModel->getDetailsByKey('setting_wx_jsapi_ticket');
		$ticketExpired = $settingModel->getDetailsByKey('setting_wx_jsapi_ticket_expired');
		$time = time();
		if ($refresh || is_null($ticket->value) || is_null($ticketExpired->value) || $ticketExpired->value <= $time) {
			$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $this->access_token . '&type=jsapi';
            $request = new \Roger\Request\MultiRequest();
            $r = new \Roger\Request\Request($url);
            $request->addRequest($r);
            $request->execute();
            $result = $request->getResult();
			$token = json_decode($result);
			if (!$token) {
				$this->errmsg = 'Jsapi Ticket Request Error!';
				return false;
			}
			if ($token->errcode != 0) {
				$this->errmsg = $token->errmsg;
				return false;
			}
			$ticket->value = $token->ticket;
			$ticket->update();
			$ticketExpired->value = $time + $token->expires_in - 10;
			$ticketExpired->update();
		}
		return $ticket->value;
	}

	public function getErrmsg()
	{
		return $this->errmsg;
	}
}