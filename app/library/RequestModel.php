<?php

class RequestModel
{
	/**
	 * 远程获取数据，POST模式
	 * @param $url 指定URL完整路径地址
	 * @param $para 请求的数据
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * @param $debug 调试开关
	 * @param $input_charset 编码格式。默认值：空值
	 *            return 远程输出的数据
	 */
	public static function getHttpResponsePOST($url, $para, $cacert_url = null, $debug = false)
	{
		$curl = curl_init($url);
		if (!is_null($cacert_url)) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
			curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
		}
		curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
		curl_setopt($curl, CURLOPT_POST, true); // post传输数据
		curl_setopt($curl, CURLOPT_POSTFIELDS, $para); // post传输数据
//		curl_setopt($curl, CURLOPT_TIMEOUT, 1);
		$responseText = curl_exec($curl);
		if ($debug) {
			var_dump(curl_error($curl)); // 如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
			die();
		}
		curl_close($curl);
		return $responseText;
	}

	/**
	 * 远程获取数据，GET模式
	 * @param $url 指定URL完整路径地址
	 * @param $cacert_url 指定当前工作目录绝对路径
	 * @param $debug 调试开关
	 *            return 远程输出的数据
	 */
	public static function getHttpResponseGET($url, $cacert_url = null, $debug = false)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
		if (!is_null($cacert_url)) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // SSL证书认证
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 严格认证
			curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); // 证书地址
		}
		$responseText = curl_exec($curl);
		if ($debug) {
			var_dump(curl_error($curl)); // 如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
			die();
		}
		curl_close($curl);
		return $responseText;
	}
}