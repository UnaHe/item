<?php
class ResultModel {
	const TYPE_JSON = 'json';
	private $errcode;
	private $data;
	private $output_type;
	private $response;
	public function __construct($translate= null , $output_type = self::TYPE_JSON) {
		$this->setOutputType ( $output_type );
		$this->response = new Phalcon\Http\Response ();
	}
	public function setResult($errcode, $data = []) {
		$this->errcode = $errcode;
		$this->data = $data;
	}
	public function setOutputType($output_type) {
		$this->output_type = $output_type;
	}
	public function output() {
		function filterNULL(&$item, $key) {
			if (is_array ( $item ) && empty ( $item )) {
				$item = '';
			} else if (is_array ( $item ) && ! empty ( $item )) {
				array_walk ( $item, 'filterNULL' );
			} else {
				$item = is_null ( $item ) ? [] : $item;
			}
		}
		if (! empty ( $this->data )) {
			array_walk ( $this->data, 'filterNULL' );
		}
		switch ($this->output_type) {
			case self::TYPE_JSON :
            default :
				$result = [
						'code' => intval($this->errcode),
						'msg' => $this->getMsg ( $this->errcode ),
						'data' => $this->data
				];
				$this->response->setContentType ( 'application/json', 'UTF-8' );
				$this->response->setJsonContent ( $result, JSON_UNESCAPED_UNICODE );
				return $this->response;
				break;

		}
	}
	public function getMsg($errcode) {
		$msgArray = array (
				'0' => '操作成功',
				'101' => '参数错误',
				'102' => '操作失败（数据库）',
				'103' => '验证字串对比失败',
				'104' => '开始时间不正确',
				'105' => '结束时间不正确',
				'106' => '密码错误',
				'201' => '用户已存在',
				'202' => '用户不存在',
				'203' => '密码不正确',
				'204' => '用户验证失败',
				'207' => '原始密码错误',
				'301' => '上传文件错误',
				'302' => '上传图片失败',
				'303' => '没有要删除的图片',
				'304' => '图片数量已达上限',
				'305' => '促销时间不正确',
				'306' => '特卖信息数量超限',
				'401' => '订单未支付',
				'402' => '商品信息无效',
				'403' => '优惠券已过期',
				'404' => '优惠券已使用',
				'405' => '优惠券已退款',
				'501' => '上级地图不正确',
				'601' => '资源保存失败，请重试',
				'803' => '短信发送失败',
				'-1' => '没有相关信息'
		);
		if (! array_key_exists( $errcode, $msgArray )) {
			return '未知错误';
		}
		return $msgArray [$errcode];
	}
}
