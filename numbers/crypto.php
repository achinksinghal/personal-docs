<?php 
require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

$sms_body = "";
$url = "https://api.coinbase.com/v2/exchange-rates?currency=BTC&ran=".time();
$data = curl_file_get_contents($url);
$coinbaseJson = FALSE;
if ($data != FALSE) {
	$coinbaseJson = json_decode($data);
	$arr = $coinbaseJson->{"data"}->{"rates"};
	foreach ($arr as $curr => $val) {
		if ($curr == "XRP" || $curr == "DASH" || $curr == "STRAT" || $curr == "RPP") {
			$sms_body = $sms_body."BTC-".$curr."=".$val.",";
		}
	}
}

$input = curl_file_get_contents("https://raw.githubusercontent.com/achinksinghal/personal-docs/master/numbers/crypto.json?rand=".time());
if ($input != FALSE) {
	$inputJson = json_decode($input);
	foreach ($inputJson->{"inputs"} as $val) {
		$max = intval($val->{"max"});
		$min = intval($val->{"min"});
		$name = $val->{"name"};
		$key = $val->{"key"};
                $coinBaseRate = 0;

		echo "checking ".$name."=<".$min.",".$max.">\n";
		if ($coinbaseJson != FALSE && isset($coinbaseJson->{"data"}->{"rates"}->{$key})) {
			$coinBaseRate = intval(intval($coinbaseJson->{"data"}->{"rates"}->{"USD"}) / intval($coinbaseJson->{"data"}->{"rates"}->{$key}));
			echo "coinbase->".$coinBaseRate."\n";
		}

		$url = "https://api.gdax.com/products/$key-USD/ticker";
		$data = curl_file_get_contents($url);
                $rate = 0;
		if ($data != FALSE) {
			$json = json_decode($data);
                        if (isset($json->{"price"})) {
				$rate = intval($json->{"price"});
				echo "gdax->".$rate."\n";
                        }

			if ($rate == 0 && $coinBaseRate != 0) {
				$rate = $coinBaseRate;
			}

			if ($rate > 0 && $rate <= $min) {
				$sms_body = $sms_body.$name." Drop ".$rate.",";
			}
			if ($rate >= $max) {
				$sms_body = $sms_body.$name." Rise ".$rate.",";
			}
		}
	}
}


if ($sms_body != "") {
	echo $sms_body."\n";
	send_sms($sms_body);
}

function send_sms($body ) {
	$client = new Client("Account_Sid", "Auth_Token");
	// Use the client to do fun stuff like send text messages!
	$client->messages->create(
			// the number you'd like to send the message to
			'+1 213-509-2030',
			array(
				// A Twilio phone number you purchased at twilio.com/console
				'from' => '+14243532161',
				// the body of the text message you'd like to send
				'body' => $body
			     )
			);
}

function curl_file_get_contents($URL) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $URL);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'User-Agent: curl/7.29.0',
				'Accept: application/json'
				));

	$contents = curl_exec($c);
	curl_close($c);

	if ($contents) return $contents;
	else return FALSE;
}
