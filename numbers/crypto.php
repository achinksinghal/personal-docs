<?php 
// run the script like root$ for j in {1..1000000}; do php crypto.php; sleep 60; done;

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
		$max = floatval($val->{"max"});
		$min = floatval($val->{"min"});
		$name = $val->{"name"};
		$gdaxKey = $val->{"gdax-key"};
		$krakenKey = isset($val->{"kraken-key"}) ? $val->{"kraken-key"} : FALSE;
                $coinBaseRate = 0;

		echo "checking ".$name."=<".$min.",".$max.">\n";
		if ($coinbaseJson != FALSE && isset($coinbaseJson->{"data"}->{"rates"}->{$gdaxKey})) {
			$coinBaseRate = floatval(floatval($coinbaseJson->{"data"}->{"rates"}->{"USD"}) / floatval($coinbaseJson->{"data"}->{"rates"}->{$gdaxKey}));
			echo "coinbase->".$coinBaseRate."\n";
		}

		$url = "https://api.gdax.com/products/$gdaxKey-USD/ticker";
		$data = curl_file_get_contents($url);
                $rate = 0;
		if ($data != FALSE) {
			$json = json_decode($data);
                        if (isset($json->{"price"})) {
				$rate = floatval($json->{"price"});
				echo "gdax->".$rate."\n";
			} else if ($krakenKey != FALSE) {
				$url = "https://api.kraken.com/0/public/Ticker?pair=$krakenKey";
				$data = curl_file_get_contents($url);
				if ($data != FALSE) {
					$json = json_decode($data);
					if (isset($json->{"result"}) && isset($json->{"result"}->{"$krakenKey"}->{"c"})) {
						$rate = floatval($json->{"result"}->{"$krakenKey"}->{"c"}[0]);
						echo "kraken->".$rate."\n";
					}
				}
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
			'+1987654320',
			array(
				// A Twilio phone number you purchased at twilio.com/console
				'from' => '+1234567890',
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
