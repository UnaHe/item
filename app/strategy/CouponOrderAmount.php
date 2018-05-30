<?php
class CouponOrderAmount extends CouponContext{
	protected function process(){
		$logFileName = APP_PATH.'log/'.date('Y-m-d').'/CouponOrderAmount.txt';
		$couponDetails = $this->getParam('couponDetails');
		if ($couponDetails===false){
			$couponId = $this->getParam('couponId');
			LogModel::log( $logFileName, '[CouponOrderAmount] getParam(\'couponDetails\') Failed!');
			if ($couponId===false){
				LogModel::log( $logFileName, '[CouponOrderAmount] getParam(\'couponId\') Failed!');
				return false;
			}
		}
		$totalPrice = $this->getParam('totalPrice');
		if ($totalPrice===false){
			LogModel::log( $logFileName, '[CouponOrderAmount] getParam(\'totalPrice\') Failed!');
			return false;
		}

		if (isset($couponId)){
			$couponModel = new CouponModel();
			$couponDetails = $couponModel->getDetailsByCouponId($couponId);
		}

		switch ($couponDetails->coupon_type){
			case 'quota':
				if (!$this->checkMustPay($totalPrice,$couponDetails->coupon_must_pay)){
					return false;
				}
				$topPrice = $this->getParam('topPrice');
				if ($topPrice===false){
					LogModel::log( $logFileName, '[CouponOrderAmount] getParam(\'topPrice\') Failed!');
					return false;
				}

				break;
			case 'discount':
				break;
			default:
				return false;
		}

		$couponRulesModel = new CouponRulesModel();
		$couponRulesDetails = $couponRulesModel->getDetailsByCouponRulesId($rulesId);

		if (!$couponRulesDetails){
			LogModel::log( $logFileName, '[CouponRegister] couponRulesModel->getDetailsByCouponRulesId Failed! rulesId: ' . $rulesId);
			return;
		}
		$userId = $this->getParam('userId');
		if (empty($userId)){
			LogModel::log( $logFileName, '[CouponRegister] getParam(\'userId\') Failed!');
			return;
		}

//		$couponModel = new CouponModel();
		$time = time();
		$cardModel = new CardModel();
		$cardModel->card_name = CardModel::genCardName($userId);
		$cardModel->start_time = $time;
		$cardModel->end_time = $couponRulesDetails->coupon_rules_expired_days > 0 ? ($time + $couponRulesDetails->coupon_rules_expired_days * 86400) : null;
		$cardModel->card_fee = $couponRulesDetails->coupon_rules_fee;
		$cardModel->free_money = $couponRulesDetails->coupon_rules_must_pay;
		$cardModel->status = 0;
		$cardModel->user_id = $userId;
		$cardModel->card_desc = $couponRulesDetails->coupon_rules_remark;
//		$couponParams = array(
//			'coupon_name' => $couponRulesDetails->coupon_rules_name,
//			'coupon_start_time' => $time,
//			'coupon_end_time' => $couponRulesDetails->coupon_rules_expired_days>0?$time+$couponRulesDetails->coupon_rules_expired_days*86400:null,
//			'coupon_fee' => $couponRulesDetails->coupon_rules_fee,
//			'coupon_must_pay' => empty($couponRulesDetails->coupon_rules_must_pay)?0:$couponRulesDetails->coupon_rules_must_pay,
//			'coupon_remark' => $couponRulesDetails->coupon_rules_name,
//			'coupon_status' => 0,
//			'user_id' => $params['userId'],
//		);
		if ($cardModel->create()==false){
			LogModel::log( $logFileName, '[CouponRegister] cardModel->create Failed! params: ' . serialize($cardModel->toArray()).' ErrMsg:' . LogModel::parsePhalconErrMsg($cardModel->getMessages()));
			return;
		}
		LogModel::log( $logFileName, '[CouponRegister] Create User Coupon Successful! params: ' . serialize($couponRulesDetails->toArray()));
	}

	private function checkMustPay($goodsAmount,$mustPay){
		if (!empty($mustPay) && $goodsAmount<$mustPay){
			return false;
		}
		return true;
	}
}