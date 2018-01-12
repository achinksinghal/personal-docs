<?php 

echo "\n\n".`date`;

$sms_body = "";
$input = curl_file_get_contents("https://raw.githubusercontent.com/achinksinghal/personal-docs/master/numbers/crypto.json?rand=".time());
if ($input != FALSE) {
	$inputJson = json_decode($input);
        $currencies = array();
	foreach ($inputJson->{"coinbase-currencies"} as $c1) {
		$currencies[$c1] = $c1;
	}

	$url = "https://api.coinbase.com/v2/currencies?ran=".time();
	$data = curl_file_get_contents($url);
	if ($data != FALSE) {
		$coinbaseJson = json_decode($data);
		$arr = $coinbaseJson->{"data"};
		$total = 0;
		$tmp = "";
		foreach ($arr as $curr) {
			$total++;
		}
		if ($total != intval($inputJson->{"coinbase-currencies-count"})) {
			$sms_body = $sms_body.$total." on coinbase,";
		}
	}

	$url = "https://api.coinbase.com/v2/exchange-rates?currency=BTC&ran=".time();
	$data = curl_file_get_contents($url);
	$coinbaseJson = FALSE;
	if ($data != FALSE) {
		$coinbaseJson = json_decode($data);
		$arr = $coinbaseJson->{"data"}->{"rates"};
		$total = 0;
		foreach ($arr as $curr => $val) {
			$total++;
			if (!isset($currencies[$curr])) {
				$sms_body = $sms_body."BTC-".$curr."=".$val.",";
			}
		}
		if ($total != count($currencies)) {
			$sms_body = $sms_body.$total." on coinbase,";
		}
	}


	foreach ($inputJson->{"inputs"} as $val) {
		$max = floatval($val->{"max"});
		$min = floatval($val->{"min"});
		$name = $val->{"name"};
		$gdaxKey = $val->{"gdax-key"};
		$krakenKey = isset($val->{"kraken-key"}) ? $val->{"kraken-key"} : FALSE;
                $coinBaseRate = 0;

		echo $name."=<".$min.",".$max.">\n";
		if ($coinbaseJson != FALSE && isset($coinbaseJson->{"data"}->{"rates"}->{$gdaxKey})) {
			$coinBaseRate = floatval(floatval($coinbaseJson->{"data"}->{"rates"}->{"USD"}) / floatval($coinbaseJson->{"data"}->{"rates"}->{$gdaxKey}));
		}

		$url = "https://api.gdax.com/products/$gdaxKey-USD/ticker";
		$data = curl_file_get_contents($url);
                $rate = 0;
		if ($data != FALSE) {
			$json = json_decode($data);
                        if (isset($json->{"price"})) {
				$rate = floatval($json->{"price"});
				echo "\tgdax->".$rate."\n";
			} else if ($krakenKey != FALSE) {
				$url = "https://api.kraken.com/0/public/Ticker?pair=$krakenKey";
				$data = curl_file_get_contents($url);
				if ($data != FALSE) {
					$json = json_decode($data);
					if (isset($json->{"result"}) && isset($json->{"result"}->{"$krakenKey"}->{"c"})) {
						$rate = floatval($json->{"result"}->{"$krakenKey"}->{"c"}[0]);
						echo "\tkraken->".$rate."\n";
					}
				}
			}

			if ($rate == 0 && $coinBaseRate != 0) {
				$rate = $coinBaseRate;
				echo "\tcoinbase->".$coinBaseRate."\n";
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
	$data = array (
			'From' => "+14243532161",
			'To' => "+12135092030",
			'Body' => $body,
		      );
	$post = http_build_query($data);
        $url = "https://api.twilio.com/2010-04-01/Accounts/${Account_Sid}/Messages.json";
	$c = curl_init();
	curl_setopt($c, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($c, CURLOPT_POSTFIELDS, $post);
        curl_setopt($c, CURLOPT_USERPWD, "${Account_Sid}:${Auth_Token}");
	curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'User-Agent: curl/7.29.0',
				'Accept: application/json'
				));
	$contents = curl_exec($c);
	curl_close($c);

	if ($contents) return json_decode($contents);
	else return FALSE;
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
