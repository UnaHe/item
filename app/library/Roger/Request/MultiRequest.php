<?php
namespace Roger\Request;

class MultiRequest
{
    private $requests = [];

    private $result = [];

	public function __construct()
	{
	}

    /**
     * @param \Roger\Request\Request $request
     * @throws \Exception
     */

	public function addRequest(\Roger\Request\Request $request)
    {
        if (is_array($request) && !empty($request)){
            foreach ($request as $r) {
                if ($r instanceof \Roger\Request\Request){
                    $this->requests[] = $request;
                }
            }
        }elseif ($request instanceof \Roger\Request\Request){
            $this->requests[] = $request;
        }else{
            throw new \Exception('Error Request');
        }

    }

    public function reset(){
	    $this->requests = [];
	    $this->result = [];
    }

    /**
     * @param \Roger\Request\Request $request
     */

    private function singleRequest(\Roger\Request\Request $request)
    {
        $ch = curl_init($request->getUrl());
        curl_setopt_array($ch, $request->getOptions());
        $responseText = curl_exec($ch);
//        if ($debug) {
//            var_dump(curl_error($curl)); // 如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
//            die();
//        }
        curl_close($ch);

        $this->result = $responseText;
    }

    /**
     * @return array
     */

    public function getResult()
    {
        return $this->result;
    }

    public function execute()
    {
        if (empty($this->requests)) {
            $this->result = 'No Request Found!';
            return;
        }
        if (count($this->requests) == 1) {
            $this->singleRequest($this->requests[0]);
        } else {
            $data = array();
            $handle = array();
            $mh = curl_multi_init(); // multi curl handler

            $i = 0;
            foreach ($this->requests as $request) {
                $ch = curl_init($request->getUrl());
                curl_setopt_array($ch, $request->getOptions());
                curl_multi_add_handle($mh, $ch); // 把 curl resource 放進 multi curl handler 裡
                $handle[$i++] = $ch;
            }

			do {
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);


			while ($active && $mrc == CURLM_OK) {
				// add this line
				while (curl_multi_exec($mh, $active) === CURLM_CALL_MULTI_PERFORM) ;

				if (curl_multi_select($mh) != -1) {
					do {
						$mrc = curl_multi_exec($mh, $active);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				}
			}
            /* 讀取資料 */
            foreach ($handle as $i => $ch) {
                $content = curl_multi_getcontent($ch);
                $data[$i] = (curl_errno($ch) == 0) ? $content : false;
                curl_multi_remove_handle($mh, $ch);
            }

            /* 移除 handle*/
//            foreach ($handle as $ch) {
//                curl_multi_remove_handle($mh, $ch);
//            }
            curl_multi_close($mh);
            $this->result = $data;
        }
    }
}