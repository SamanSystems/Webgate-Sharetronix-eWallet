<?php

	/*

		eWallet 1.5
		::: ZarinPal

		Persian Sharetronix
		www.sharetronix.ir
	
	*/

	class zarinpal
	{
		public function __construct()
		{
			$this->network = &$GLOBALS['network'];
			$this->user = &$GLOBALS['user'];
			$this->db2 = &$GLOBALS['db2'];
		}
		public function request($Price, $uid = "", $ReturnPath = "", $Description = "",
			$Mobile = "", $Email = "", $username = "")
		{
			global $C;
			require_once ($C->INCPATH . 'config/balance.php');
			$Price = $this->db2->e($Price); //Price By Toman
			if ($Price < $C->MIN_PAY) {
				return "err1";
			}
			$ResNumber = "DBLNC" . time() . rand(10000, 99999); // Order Id In Your System
			$ReturnPath = (empty($ReturnPath)) ? $C->SITE_URL . "settings/ewallet/" : $ReturnPath;
			$Description = (empty($Description)) ? 'افزایش اعتبار حساب کاربری' : $Description;
			$Paymenter = (empty($username)) ? $this->user->info->username : $username;
			$Email = (empty($Email)) ? $this->user->info->email : $Email;
			$Mobile = (empty($Mobile)) ? $this->user->info->numobile : $Mobile;
			$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl',
				array('encoding' => 'UTF-8'));
			$result = $client->PaymentRequest(array(
				"MerchantID" => $C->ZarinPMerchant,
				"Amount" => $Price,
				"Description" => $Description,
				"Paymenter" => $Paymenter,
				"Email" => $Email,
				"Mobile" => $Mobile,
				"CallbackURL" => $ReturnPath));
			$PayPath = 'https://www.zarinpal.com/pg/StartPay/' . $result->Authority;
			$Status = $result->Status;
			$refid = $result->Authority;
			if ($Status == 100) {
				$shop_date = time();
				$uid = (empty($uid)) ? intval($this->user->id) : $uid;
				$this->db2->query("INSERT INTO `user_payments` (`uid`,`price`,`invoice_number`,`refid`,`date`,`mob`,`mail`)VALUES('$uid','$Price','$ResNumber','$refid','$shop_date','$Mobile','$Email');");
				return "پرداخت : $Price تومان <br/> شناسه پرداخت : $ResNumber <br/>شناسه‌ی پرداخت را تا پایان خرید و شارژ حساب نگهداری نمایید!<br/> برای ورود به درگاه امن زرین پال بر روی دکمه پرداخت کلیک نمایید.<br><br><a id='signin_submit' href='$PayPath' name='cancel' >پرداخت</a><a id='tw_submit' onclick='form()' name='cancel' >بازگشت</a><br><br>";
			} else {
				return "err2";
			}
		}
		public function verify($price, $refnumber)
		{
			global $C;
			require_once ($C->INCPATH . 'config/balance.php');
			$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl',
				array('encoding' => 'UTF-8'));
			$result = $client->PaymentVerification(array(
				"MerchantID" => $C->ZarinPMerchant,
				"Authority" => $refnumber,
				"Amount" => $price));
			$Status = $result->Status;
			$RefID = $result->RefID;
			if ($Status == 100) {
				$response = array("status" => "success", "refid" => $RefID);
				return $response;
			} else {
				return false;
			}
		}
	}
