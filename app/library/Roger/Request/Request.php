<?php
namespace Roger\Request;

class Request
{
	const POST = 'POST';
	const GET = 'GET';
	private $url;
	private $method;
	private $headers;
	private $postData;
	private $options = [
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36',
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_MAXREDIRS => 7,
		CURLOPT_SSL_VERIFYPEER => false,
	];

	/**
	 * Request constructor.
	 * @param string $url
	 * @param string $method
	 * @param mixed $postData
	 * @param array $options
	 * @param array $headers
	 * @param null $cacert_url
	 */

	public function __construct($url, $method = self::GET, $postData = null, array $options = [], $headers = [], $cacert_url = null)
	{
		$this->setUrl($url);
		$this->setMethod($method);
		$this->setPostData($postData);
		$this->setHeaders($headers);
		$this->setOptions($options);
		$this->setHeaders($headers);
		$this->setCacert($cacert_url);
	}

	/**
	 * @param array $options
	 */

	public function setOptions(array $options = [])
	{
		if (!empty($options)) {
			$this->options = $options + $this->options;
		}
	}

	/**
	 * @param $url
	 */

	public function setCacert($url)
	{
		if (!empty($url)) {
			$this->setOptions([
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_CAINFO => $url,
			]);
		}
	}

	/**
	 * @param $url
	 */

	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * @return mixed
	 */

	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param $data
	 */

	public function setPostData($data)
	{
		$this->postData = $data;
	}

	/**
	 * @return array
	 */

	public function getOptions(){
		if ($this->getMethod()==self::POST) {
			$this->setOptions([
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $this->postData
			]);
		}
		return $this->options;
	}

	/**
	 * @param array $headers
	 */

	public function setHeaders(array $headers = [])
	{
		$this->headers = $headers;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @param $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	public function __destruct()
	{
		unset($this->url, $this->method, $this->postData , $this->headers, $this->options);
	}

}