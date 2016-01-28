	<?php
/**
* Telegram Bot EsciLaRicetta Lic. MIT by Matteo Tempestini e Francesco "Piersoft" Paolicelli
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
if (strpos($text,'/start') === false ){
	$text =str_replace("/","",$text);
}
if (strpos($text,'@escilaricettaBot') !== false) $text =str_replace("@escilaricettaBot ","",$text);
	if ($text == "/start" || $text == "Informazioni") {
		$img = curl_file_create('logo.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$reply = "Benvenuto. Questo è un servizio automatico (bot da Robot) per le ricette tipiche raccolte su ".NAME." con licenza CC-BY-SA. Mandaci anche tu la tua ricetta compilando: http://goo.gl/forms/yajhBkIzw7. In questo bot puoi ricercare gli argomenti per parola chiave anteponendo il carattere - , oppure cliccare su Numero per cercare per numero la ricetta e infine cercare per Città inviando la tua posizione (📎). In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot è stato realizzato da @piersoft e @il_tempe grazie a ".NAME.".\nRingraziamo per il prezioso contributo Andrea Borruso, Ciro Spataro e Matteo Fortini. Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.\nL'elenco delle ricette inserite dagli utenti è in licenza CC-BY-SA ed è in formato CSV: https://goo.gl/rfGvd1.\nSeguici su Facebook: https://www.facebook.com/EsciLaRicetta-1515883528729002/, su Twitter: @escilaricetta e sul nostro sito internet interattivo: http://escilaricetta.github.io/recipe-site/ e se proprio ti piace il progetto metti un voto su https://storebot.me/bot/escilaricettabot";
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
				$location="Sto cercando le ricette contenenti nel titolo o negli ingredienti: ".$text;
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$text=str_replace(" ","%20",$text);
				$text=strtoupper($text);
		//		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20upper(C)%20like%20%27%25";
				$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(C)%20like%20%27%25";
				$urlgd .=$text;
				$urlgd .="%25%27%20OR%20upper(O)%20like%20%27%25";
				$urlgd .=$text;
				$urlgd .="%25%27%20AND%20upper(M)%20like%20%27X%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
				$inizio=1;
				$homepage ="";

				$csv = array_map('str_getcsv',file($urlgd));
				$csv=str_replace(array("\r", "\n"),"",$csv);

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
					$homepage .="Ricetta n°: ".$csv[$i][0]."\n".$csv[$i][2]."\n";
					$homepage .="\nPer ingredienti e preparazione digita o clicca su: /".$csv[$i][0]."\n";
					$homepage .="____________\n";


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
			$location="Digitare direttamente il n° della ricetta che ti interessa";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
			$location="Eccoti tutte le ricette disponibili:\n";
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
		//	$text=str_replace(" ","%20",$text);
//			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20N%20IS%20NOT%20NULL";
			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(M)%20like%20%27X%27";

			$urlgd .="%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
			sleep (1);
			$inizio=1;
			$homepage ="";

			$csv = array_map('str_getcsv',file($urlgd));
			$csv=str_replace(array("\r", "\n"),"",$csv);

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
				$homepage .="N°: /".$csv[$i][0]."\n".$csv[$i][2];
				$homepage .="\nRegione: ".$csv[$i][10];
				$homepage .="\n____________\n";


			}
			$chunks = str_split($homepage, self::MAX_LENGTH);
			foreach($chunks as $chunk) {
				$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
					}
				$mappa="Puoi visualizzarle tutte su mappa:\nhttp://goo.gl/jtkbTk";
				$content = array('chat_id' => $chat_id, 'text' => $mappa,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);


		}elseif (strpos($text,'1') !== false || strpos($text,'2') !== false || strpos($text,'3') !== false || strpos($text,'4') !== false || strpos($text,'5') !== false || strpos($text,'6') !== false || strpos($text,'7') !== false || strpos($text,'8') !== false || strpos($text,'9') !== false || strpos($text,'0') !== false ){
			$location="Sto elaborando la ricetta n°: ".$text;
			$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
	//		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20A%2CC%2CD%2CG%2CH%2CP%2CL%2CM%2CO%2CJ%2CK%20WHERE%20A%20%3D%20";
			$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20A%20%3D%20";

			$urlgd .=$text;
			$urlgd .="%20AND%20upper(M)%20like%20%27X%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
			$inizio=1;
			$homepage ="";
			$csv = array_map('str_getcsv',file($urlgd));
			$csv=str_replace(array("\r", "\n"),"",$csv);

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

				if ($csv[1][8]!=NULL){
				$fotoname=rand(5, 15);
				$ch = curl_init($csv[1][8]);
				$urlfile="log/temp".$fotoname.".png";
				$fp = fopen($urlfile, 'wb');
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
				$img = curl_file_create($urlfile,'image/png');
				$contentp = array('chat_id' => $chat_id, 'photo' => $img);
				$telegram->sendPhoto($contentp);
		//		$content = array('chat_id' => $chat_id, 'text' => $fotoname,'disable_web_page_preview'=>true);
		//		$telegram->sendMessage($content);

				}
				$homepage .="\n";
				$homepage .="Titolo: ".$csv[$i][2]."\n";
				$homepage .="Categoria: ".$csv[$i][3]."\n";
				$homepage .="Ingredienti:\n".$csv[$i][14]."\n";
				$homepage .="Preparazione:\n".$csv[$i][15]."\n";
	if ($csv[$i][6] !=NULL || $csv[$i][7] !=NULL) $homepage .="Ricetta proposta da: ".$csv[$i][6]." ".$csv[$i][7]."\n";
	if ($csv[$i][11] !=NULL) $homepage .="Tempo di preparazione: ".$csv[$i][11]."\n";
	if ($csv[$i][8] !=NULL)	$homepage .="Foto: ".$csv[$i][8]."\n";
				$homepage .="Città: ".$csv[$i][9]."\n";
				$homepage .="Regione: ".$csv[$i][10]."\n";
	if ($csv[$i][22] !=NULL)			$homepage .="Note narrative: ".$csv[$i][22];
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
			 $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Digita il numero,fai una ricerca con - oppure invia la tua posizione (📎)]");
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
	 				$location="Sto cercando le ricette nelle città più vicine a \"".$comune."\" (fino a 50km) grazie alle coordinate che hai inviato";//: ".$lat.",".$lon;
	 				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
	 				$telegram->sendMessage($content);

	 			  $alert="";

	 			//	echo $comune; debug
	 			$comune=str_replace(" ","%20",$comune);
	 			$comune=strtoupper($comune);
		//		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(K)%20like%20%27%25";
		//		$urlgd .=$comune;
		//		$urlgd .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
				$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(M)%20like%20%27X%27";

				$urlgd .="%20&key=".GDRIVEKEY."&gid=".GDRIVEGID2;
				$csv = array_map('str_getcsv',file($urlgd));
	 			$count = 0;
	 			foreach($csv as $data=>$csv1){
	 				$count = $count+1;
	 			}
	 		if ($count ==0 || $count ==1 )
	 		{
	 					$location="Nessuna ricetta trovata";
	 					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
	 					$telegram->sendMessage($content);
	 					$this->create_keyboard_temp($telegram,$chat_id);
	 					exit;
	 		}	elseif ($count >100)
	 		{
	 					$location="Troppi risultati, impossibile visualizzazione";
	 					$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
	 					$telegram->sendMessage($content);
	 					$this->create_keyboard_temp($telegram,$chat_id);
	 					exit;
	 		}

	 			$inizio=1;
	 			$homepage ="";

	 			$latidudine="";
	 			$longitudine="";
	 			$data=0.0;
	 			$data1=0.0;
	 			//$count = 0; debug
	 			$dist=0.0;
	 				$paline=[];
	 				$distanza=[];
	 				$countf = 0 ;

	 			for ($i=$inizio;$i<$count;$i++){

	 				$homepage .="\n";

	 				$lat10=floatval($csv[$i][20]);
	 				$long10=floatval($csv[$i][21]);
	 				$theta = floatval($lon)-floatval($long10);
	 				$dist =floatval( sin(deg2rad($lat)) * sin(deg2rad($lat10)) +  cos(deg2rad($lat)) * cos(deg2rad($lat10)) * cos(deg2rad($theta)));
	 				$dist = floatval(acos($dist));
	 				$dist = floatval(rad2deg($dist));
	 				$miles = floatval($dist * 60 * 1.1515 * 1.609344);

	 				if ($miles >=1){
	 					$data1 =number_format($miles, 2, '.', '');
	 					$data =number_format($miles, 2, '.', '')." Km";
	 				} else {
	 					$data =number_format(($miles*1000), 0, '.', '')." mt";
	 					$data1 =number_format(($miles*1000), 0, '.', '');
	 				}
	 				$csv[$i][100]= array("distance" => "value");

	 				$csv[$i][100]= $data1;
	 				$csv[$i][101]= array("distancemt" => "value");

	 				$csv[$i][101]= $data;
	 				$t=floatval($r*50);


	 						if ($data < $t)
	 						{

	 							$distanza[$i]['distanza'] =$csv[$i][100];
	 							$distanza[$i]['distanzamt'] =$csv[$i][101];
	 							$distanza[$i]['id'] =$csv[$i][0];
	 							$distanza[$i]['lat'] =$csv[$i][20];
	 							$distanza[$i]['lon'] =$csv[$i][21];
	 							$distanza[$i]['comune'] =$csv[$i][9];
	 							$distanza[$i]['titolo'] =$csv[$i][2];
	 					//		$distanza[$i]['portata'] =$csv[$i][5];
	 					//		$distanza[$i]['uver'] =$csv[$i][6];
	 					//		$distanza[$i]['note'] =$csv[$i][7];
	 					//		$distanza[$i]['foto'] =$csv[$i][8];

	 				$countf++;

	 						}


	 			}

	 			$temp_c1="";
	 			sort($distanza);
	 			for ($f=0;$f<20;$f++){

	 					if($distanza[$f]['titolo'] !=NULL)			$temp_c1 .="\n".	$distanza[$f]['titolo'];
	 			//		if($distanza[$f]['comune'] !=NULL)			$temp_c1 .="\nComune di ".$distanza[$f]['comune'];
						if($distanza[$f]['distanzamt'] !=NULL)			$temp_c1 .="\nsegnalata a ".$distanza[$f]['distanzamt'];
						if($distanza[$f]['id'] !=NULL)					$temp_c1 .="\nPer i dettagli clicca su: /".$distanza[$f]['id'];

	 					if($distanza[$f]['lat'] !=NULL){
	 					//	$temp_c1 .="\nVisualizza su Openstreetmap:\n";
	 					//	$temp_c1 .="(fondamentale in caso di emergenza umanitaria)\n";
	 					//	$temp_c1 .= "http://www.openstreetmap.org/?mlat=".$distanza[$f]['lat']."&mlon=".$distanza[$f]['lon']."#map=19/".$distanza[$f]['lat']."/".$distanza[$f]['lon']."\n";
	 					//	$temp_c1 .="Visualizza su Google App:\nhttp://maps.google.com/maps?q=".$distanza[$f]['lat'].",".$distanza[$f]['lon'];

	 				}

	 				if($distanza[$f]['id'] !=NULL)						$temp_c1 .="\n_____________\n";


	 			}
	 			$chunks = str_split($temp_c1, self::MAX_LENGTH);
	 		  foreach($chunks as $chunk) {

	 		 		 $content = array('chat_id' => $chat_id, 'text' => $chunk, 'reply_to_message_id' =>$bot_request_message_id,'disable_web_page_preview'=>true);
	 		 		 $telegram->sendMessage($content);

	 		  }

				$mappa="Puoi visualizzarle tutte su mappa:\nhttp://goo.gl/jtkbTk";
				$content = array('chat_id' => $chat_id, 'text' => $mappa,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);

	 		 	$today = date("Y-m-d H:i:s");

	 		 	$log=$today. ",location sent," .$chat_id. "\n";
	 		 	$this->create_keyboard_temp($telegram,$chat_id);
	 		 	exit;

	 	}


	 }

	 ?>
