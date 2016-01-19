<?php
/**
* Telegram Bot EsciLaRicetta Lic. MIT by Matteo Tempestini e Piersoft
* @author Francesco Piero Paolicelli @piersoft derivato da parte di codice di @emergenzaprato
*/

include("Telegram.php");
include("settings_t.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/start" || $text == "Informazioni") {
		$img = curl_file_create('logo.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$reply = "Benvenuto. Questo è un servizio automatico (bot da Robot) per le ricette tipiche raccolte su ".NAME." con licenza CC-BY-SA. Mandaci anche tu la tua ricetta compilando: http://goo.gl/forms/yajhBkIzw7. In questo bot puoi ricercare gli argomenti per parola chiave anteponendo il carattere - , oppure cliccare su Numero per cercare per numero la ricetta. In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot è stato realizzato da @piersoft e @il_tempe grazie a ".NAME.". Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ",new_info,," .$chat_id. "\n";
		file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		$this->create_keyboard_temp($telegram,$chat_id);
		exit;
	}
			elseif ($text == "Ricerca") {
				$reply = "Scrivi la parola da cercare anteponendo il carattere - , ad esempio: -Cotechino";
				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
		//		$log=$today. ";new chat started;" .$chat_id. "\n";
				$this->create_keyboard_temp($telegram,$chat_id);
exit;

}elseif($location!=null)
		{
			$this->location_manager($telegram,$user_id,$chat_id,$location);
			exit;
		}

		elseif(strpos($text,'/') === false){

			if(strpos($text,'?') !== false || strpos($text,'-') !== false){
				$text=str_replace("?","",$text);
				$text=str_replace("-","",$text);
				$location="Sto cercando le ricette contenenti nel titolo: ".$text;
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$text=str_replace(" ","%20",$text);
				$text=strtoupper($text);
				$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(C)%20like%20%27%25";
				$urlgd .=$text;
				$urlgd .="%25%27%20AND%20N%20IS%20NOT%20NULL&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
				$inizio=1;
				$homepage ="";

				$csv = array_map('str_getcsv',file($urlgd));

				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
				if ($count ==0){
						$location="Nessun risultato trovato";
						$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
						$telegram->sendMessage($content);
					}
					if ($count >40){
							$location="Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
							$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
							$telegram->sendMessage($content);
							exit;
						}
					function decode_entities($text) {

													$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
												$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
													$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
												$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
				return $text;
					}
				for ($i=$inizio;$i<$count;$i++){


					$homepage .="\n";
					$homepage .="Ricetta N°: ".$csv[$i][0]."\n".$csv[$i][2]."\n";
					$homepage .="\nPer la risposta puoi digitare direttamente: ".$csv[$i][0]."\n";
					$homepage .="\n____________\n";


				}
				$chunks = str_split($homepage, self::MAX_LENGTH);
				foreach($chunks as $chunk) {
					$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
						}
								$log=$today. ",ricerca,".$text."," .$chat_id. "\n";
								file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

		}else if (strpos($text,'Numero') !== false){
		//	$text=str_replace("?","",$text);
			$location="Puoi digitare direttamente il N° della ricetta che ti interessa";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
			$location="Eccoti tutte le ricette disponibili:\n";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		//	$text=str_replace(" ","%20",$text);
			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20N%20IS%20NOT%20NULL";
			//$urlgd .=$text;
			$urlgd .="%20&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
			sleep (1);
			$inizio=1;
			$homepage ="";

			$csv = array_map('str_getcsv',file($urlgd));
			//var_dump($csv[1][0]);
			$count = 0;
			foreach($csv as $data=>$csv1){
				$count = $count+1;
			}
			if ($count ==0){
					$location="Nessun risultato trovato";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
				}
				function decode_entities($text) {

												$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
											$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
												$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
											$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
			return $text;
				}
			for ($i=$inizio;$i<$count;$i++){


				$homepage .="\n";
				$homepage .="N°: ".$csv[$i][0]." - ".$csv[$i][2];
				$homepage .=$csv[$i][1]."\n";
				$homepage .="____________\n";


			}
			$chunks = str_split($homepage, self::MAX_LENGTH);
			foreach($chunks as $chunk) {
				$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
					}

		}elseif (strpos($text,'1') !== false || strpos($text,'2') !== false || strpos($text,'3') !== false || strpos($text,'4') !== false || strpos($text,'5') !== false || strpos($text,'6') !== false || strpos($text,'7') !== false || strpos($text,'8') !== false || strpos($text,'9') !== false || strpos($text,'0') !== false ){
			$location="Sto elaborando la ricetta n°: ".$text;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20A%20%3D%20";
			$urlgd .=$text;
			$urlgd .="%20AND%20N%20IS%20NOT%20NULL&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
			$inizio=1;
			$homepage ="";
			$csv = array_map('str_getcsv',file($urlgd));
			$count = 0;
			foreach($csv as $data=>$csv1){
				$count = $count+1;
			}
		if ($count ==0 || $count ==1){
					$location="Nessun risultato trovato";
					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
				}
				function decode_entities($text) {

												$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
											$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
												$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
											$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
	return $text;
				}
			for ($i=$inizio;$i<$count;$i++){


				$homepage .="\n";
				$homepage .="Titolo: ".$csv[$i][2]."\n";
				$homepage .="Categoria: ".$csv[$i][3]."\n";
				$homepage .="Ingredienti:\n".$csv[$i][4]."\n";
				$homepage .="Preparazione:\n".$csv[$i][5]."\n";
	if ($csv[$i][6] !=NULL || $csv[$i][7] !=NULL) $homepage .="Ricetta proposta da:\n".$csv[$i][6]." ".$csv[$i][7]."\n";
	if ($csv[$i][12] !=NULL) $homepage .="Tempo di preparazione: ".$csv[$i][12]."\n";
	if ($csv[$i][9] !=NULL)	$homepage .="Foto: ".$csv[$i][9]."\n";
				$homepage .="Città: ".$csv[$i][10]."\n";
				$homepage .="Regione: ".$csv[$i][11];
				$homepage .="\n____________\n";
		}
		$chunks = str_split($homepage, self::MAX_LENGTH);
		foreach($chunks as $chunk) {
			$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
				}
				$log=$today. ",ricerca,".$text."," .$chat_id. "\n";
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
		}
		$this->create_keyboard_temp($telegram,$chat_id);
exit;
}

	}

	function create_keyboard_temp($telegram, $chat_id)
	 {
			 $option = array(["Numero","Ricerca"],["Informazioni"]);
			 $keyb = $telegram->buildKeyBoard($option, $onetime=false);
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Digita il numero,fai una ricerca con - oppure invia la tua posizione]");
			 $telegram->sendMessage($content);
	 }

	 function location_manager($telegram,$user_id,$chat_id,$location)
	 	{

	 			$lon=$location["longitude"];
	 			$lat=$location["latitude"];
	 			$r=1;
	 			$response=$telegram->getData();
	 			$response=str_replace(" ","%20",$response);

	 				$reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lon."&zoom=18&addressdetails=1";
	 				$json_string = file_get_contents($reply);
	 				$parsed_json = json_decode($json_string);
	 				//var_dump($parsed_json); debug
	 				$comune="";
	 				$temp_c1 =$parsed_json->{'display_name'};

	 				if ($parsed_json->{'address'}->{'town'}) {
	 					$temp_c1 .="\nCittà: ".$parsed_json->{'address'}->{'town'};
	 					$comune .=$parsed_json->{'address'}->{'town'};
	 				}else 	$comune .=$parsed_json->{'address'}->{'city'};

	 				if ($parsed_json->{'address'}->{'village'}) $comune .=$parsed_json->{'address'}->{'village'};
	 				$location="Sto cercando le ricette a ".$comune;//."\" distanti al massimo 5 km tramite le coordinate che hai inviato: ".$lat.",".$lon;
	 				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
	 				$telegram->sendMessage($content);

	 			  $alert="";

	 			//	echo $comune; debug
	 			$comune=str_replace(" ","%20",$comune);
	 			$comune=strtoupper($comune);
	 			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(K)%20contains%20%27";
	 			$urlgd .=$comune;
				$urlgd .="%27%20AND%20N%20IS%20NOT%20NULL&key=".GDRIVEKEY."&gid=".GDRIVEGID1;

				$inizio=1;
				$homepage ="";
				$csv = array_map('str_getcsv',file($urlgd));
				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
			if ($count ==0){
						$location="Nessun risultato trovato";
						$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
						$telegram->sendMessage($content);
					}
					function decode_entities($text) {

													$text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
												$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
													$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
												$text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
		return $text;
					}
		//			if ($count >0) $count=20;
				for ($i=$inizio;$i<$count;$i++){


					$homepage .="\n";
					$homepage .="Titolo: ".$csv[$i][2]."\n";
					$homepage .="Categoria: ".$csv[$i][3]."\n";
					$homepage .="Ingredienti:\n".$csv[$i][4]."\n";
					$homepage .="Preparazione:\n".$csv[$i][5]."\n";
		if ($csv[$i][6] !=NULL || $csv[$i][7] !=NULL) $homepage .="Ricetta proposta da:\n".$csv[$i][6]." ".$csv[$i][7]."\n";
		if ($csv[$i][12] !=NULL) $homepage .="Tempo di preparazione: ".$csv[$i][12]."\n";
		if ($csv[$i][9] !=NULL)				$homepage .="Foto: ".$csv[$i][9]."\n";
					$homepage .="Città: ".$csv[$i][10]."\n";
					$homepage .="Regione: ".$csv[$i][11];
					$homepage .="\n____________\n";
			}
			$chunks = str_split($homepage, self::MAX_LENGTH);
			foreach($chunks as $chunk) {
				$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
					}

					/*
	 			$longUrl="http://www.piersoft.it/escilaricettabot/locator.php?lat=".$lat."&lon=".$lon."&r=1";
	 			$apiKey = API;

	 			$postData = array('longUrl' => $longUrl, 'key' => $apiKey);
	 			$jsonData = json_encode($postData);

	 			$curlObj = curl_init();

	 			curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key='.$apiKey);
	 			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	 			curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	 			curl_setopt($curlObj, CURLOPT_HEADER, 0);
	 			curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	 			curl_setopt($curlObj, CURLOPT_POST, 1);
	 			curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

	 			$response = curl_exec($curlObj);

	 			// Change the response json string to object
	 			$json = json_decode($response);

	 			curl_close($curlObj);
	 			//  $reply="Puoi visualizzarlo su :\n".$json->id;
	 			$shortLink = get_object_vars($json);
	 			//return $json->id;
	 if ($count !=0){
	 			$mappa ="\nVisualizza tutte ricette su mappa :\n".$shortLink['id'];
	 			$content = array('chat_id' => $chat_id, 'text' => $mappa,'disable_web_page_preview'=>true);
	 			$telegram->sendMessage($content);
	 }
	 */
	 		 	$today = date("Y-m-d H:i:s");

				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

	 		 	$log=$today. ",location sent,".$comune."," .$chat_id. "\n";
	 		 	$this->create_keyboard_temp($telegram,$chat_id);
	 		 	exit;

	 	}

}

?>
