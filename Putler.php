<?php

/**
 * Putler Inbound API JSON V1 for Yii Framework
 * @author twitter.com/andreslee
 */
class Putler
{
	private function currencyExchange($currency) {
		$string = file_get_contents('http://www.getexchangerates.com/api/latest.json');
		$json_a = json_decode($string, true);

		foreach ($json_a as $person_name => $person_a) {
		    return $person_a[$currency];
		}
		
	}

	private function putlerValidations() {
		$output = Yii::app()->curl->setOption(CURLOPT_USERPWD, "" . Yii::app()->params['putler']['email'] . ":" . Yii::app()->params['putler']['token'] . "")->post(Yii::app()->params['putler']['url'], $data, array('action' => 'validate'));
		return json_decode($output);
	}

	public function putlerPost($oid, $transaction_fee) {
		$validation = $this->putlerValidations();
		if(!empty($oid) && is_numeric($oid)) {
			$cartOrder = CartOrders::model()->findByPk($oid);
			$cartClient = CartClients::model()->findByPk($cartOrder->clients_id);
		}

		$timestamp = CDateTimeParser::parse($cartOrder->payment_date, 'yyyy-MM-dd hh:mm:ss');
		$dateInGMT = gmdate ( 'm/d/Y', ( int ) $timestamp );
		$timeInGMT = gmdate ( 'H:i:s', ( int ) $timestamp );

		if ($validation->ACK == "Success") {
			if(!empty($oid) && is_numeric($oid)) {
				$date 	= $dateInGMT;
				$time 	= $timeInGMT;
				$timezone = 'GMT';
				$type 	= "Shopping Cart Payment Received";
				$transaction_id = $cartOrder->gateways_transaction_id;
				$item_title = "Order: " . $cartOrder->id . "";
				$quantity = 1;
				$source = "Credit Card";
				$name 	= $cartClient->name;
				$status = "Completed";
				$currency = "USD";
				$gross 	= $cartOrder->amount_total;
				$fee   	= round((($transaction_fee/100)/$this->currencyExchange('MXN')), 2);
				$net   	= round($gross - $fee, 2);
				$email  = $cartClient->email;
				$item_id = $cartOrder->id;
				$address = $cartClient->street1;
				$address2 = $cartClient->street2;
				$city 	= $cartClient->city;
				$phone 	= $cartClient->phone;

				$json = '[{"Date":"' . $date . '","Time":"' . $time . '","Time_Zone":"' . $timezone . '","Type":"' . $type . '","Transaction_ID":"' . $transaction_id . '","Item_Title":"' . $item_title . '","Quantity":' . $quantity . ',"Source":"' . $source . '","Name":"' . $name . '","Status":"' . $status . '","Currency":"' . $currency . '","Gross":' . $gross . ',"Fee":-' . $fee . ',"Net":' . $net . ',"From_Email_Address":"' . $email . '","Item_ID":"' . $item_id . '","Address_Line_1":"' . $address . '","Address_Line_2":"' . $address2 . '","Town_City":"' . $city . '","Contact_Phone_Number":"' . $phone . '"}]';

				$ch = curl_init(Yii::app()->params['putler']['url']);                                                                      
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
				curl_setopt($ch, CURLOPT_USERPWD, "" . Yii::app()->params['putler']['email'] . ":" . Yii::app()->params['putler']['token'] . "");                                                                    
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                  
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
				    'Content-Type: application/json',                                                                           
				    'Content-Length: ' . strlen($json))                                                                       
				);                                                                                                                   
				 
				$result = curl_exec($ch);
			}
		}
	}
}