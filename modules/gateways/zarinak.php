<?php
/**
 * @author Masoud Amini
 * @copyright 2013
 */
function zarinak_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"زرینک"),
     "merchantID" => array("FriendlyName" => "merchantID", "Type" => "text", "Size" => "50", ),
     "Currencies" => array("FriendlyName" => "Currencies", "Type" => "dropdown", "Options" => "Rial,Toman", ),
	 "MirrorName" => array("FriendlyName" => "نود اتصال", "Type" => "dropdown", "Options" => "آلمان,ایران,خودکار", "Description" => "چناانچه سرور شما در ایران باشد ایران دا انتخاب کنید و در غیر اینصورت آلمان و یا خودکار را انتخاب کنید", ),
     "afp" => array("FriendlyName" => "افزودن کارمزد به قیمت ها", "Type" => "yesno", "Description" => "در صورت انتخاب 1 درصد به هزینه پرداخت شده افزوده می شود.", ),
     );
	return $configarray;
}

function zarinak_link($params) {

	# Gateway Specific Variables
	$merchantID = $params['merchantID'];
    $currencies = $params['Currencies'];
    $afp = $params['afp'];
	$mirrorname = $params['MirrorName'];
    
	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];

	# Enter your code submit to the gateway...

	
	$code = '
    <form method="post" action="">
        <input type="hidden" name="merchantID" value="'. $merchantID .'" />
        <input type="hidden" name="invoiceid" value="'. $invoiceid .'" />
        <input type="hidden" name="amount" value="'. $amount .'" />
        <input type="hidden" name="currencies" value="'. $currencies .'" />
        <input type="hidden" name="afp" value="'. $afp .'" />
        <input type="hidden" name="systemurl" value="'. $systemurl .'" />
		<input type="hidden" name="email" value="'. $email .'" />
		<input type="hidden" name="cellnum" value="'. $phone .'" />
		<input type="hidden" name="mirrorname" value="'. $mirrorname .'" />
        <input type="submit" name="pay" value=" پرداخت " />
    </form>
    ';

	if(isset($_POST['pay']) OR strpos($_SERVER['PHP_SELF'],'cart.php') > 0)
	{
		$Amount = intval($amount);
		if($currencies == 'Rial'){
			$Amount = round($Amount/10);
		}
		
		if($afp=='on'){
			$Fee = round($Amount*0.01);
		} else {
			$Fee = 0;
		}
		
		switch($mirrorname){
			case 'آلمان': 
				$mirror = 'de';
				break;
			case 'ایران':
				$mirror = 'ir';
				break;
			default:
				$mirror = 'de';
				break;
		}
		
		$CallbackURL = 'http://' . $_SERVER['SERVER_NAME'] .'/modules/gateways/callback/zarinak.php?invoiceid='. $invoiceid;
		try {
			$client = new SoapClient('https://'. $mirror .'.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));
		
			$result = $client->PaymentRequest(
												array(
														'MerchantID' 	=> $merchantID,
														'Amount' 		=> $Amount+$Fee,
														'Description' 	=> 'Invoice ID: '. $invoiceid,
														'Email' 		=> $email,
														'Mobile' 		=> $phone,
														'CallbackURL' 	=> $CallbackURL
													)
											);
		} catch (Exception $e) {
			$code =  '<h2>وقوع وقفه!</h2>';
			$code .= $e->getMessage();
		}
		if($result->Status == 100){ 
			$Authority = $result->Authority;
			
			mysql_query("INSERT INTO `tblZarinPalLog` (`orderId`,`Amount`,`Authority`) VALUES ('".$invoiceid."','".$Amount."','".$Authority."') ");
			$url = 'https://www.zarinpal.com/pg/StartPay/' . $result->Authority;
			$code = '
				<script type="text/javascript" src="https://cdn.zarinpal.com/zarinak/v1/checkout.js"></script>
				<script>
				Zarinak.setAuthority( ' . $Authority . ');
				Zarinak.open();
				</script>';
				
			if(! strpos($_SERVER['PHP_SELF'],'cart.php') > 0)
				$code .= '<form method="post" action="">
        <input type="hidden" name="merchantID" value="'. $merchantID .'" />
        <input type="hidden" name="invoiceid" value="'. $invoiceid .'" />
        <input type="hidden" name="amount" value="'. $amount .'" />
        <input type="hidden" name="currencies" value="'. $currencies .'" />
        <input type="hidden" name="afp" value="'. $afp .'" />
        <input type="hidden" name="systemurl" value="'. $systemurl .'" />
		<input type="hidden" name="email" value="'. $email .'" />
		<input type="hidden" name="cellnum" value="'. $phone .'" />
		<input type="hidden" name="mirrorname" value="'. $mirrorname .'" />
        <input type="submit" name="pay" value=" پرداخت " />
    </form>';
		} else {
			$code = "<h2>وقوع خطا در ارتباط!</h2>"
				.'کد خطا'. $result->Status;
		}
	}

	return $code;
}
?>
