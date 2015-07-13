<?php

/**
 * Author: Akhilesh Chandra Gupta
 * Author URI: http://www.bitnol.com
 * API URI: http://api.gitnol.com
 * Description: Modular demo of the application of Cipher API
 * License: MIT License
 */

	// Youtube Video ID 	
	if(isset($_GET['id']) && $_GET['id'] != ""){
		$video_id = $_GET['id'];
	}else{
		echo 'video id not provided';
		exit;
	}
	$results = getYTLink($video_id);
	foreach($results as $itag => $link){
		echo '<a href="'.$link.'">Download '.$itag.'</a><br>';
	}

	function getYTLink($video_id){
		$CipherAPIkey = "Enter your CIpher apikey here";

		// get_video_info url formation
		// although for cipher signature we have to get the details from the video's webapge not from get_video_info object
		$info_url = "http://www.youtube.com/get_video_info?video_id=".$video_id."&el=vevo&el=embedded&hl=en_US";
		
		// youtube webpage url formation
		$yt_url = 'http://www.youtube.com/watch?v='.$video_id.'&gl=US&persist_gl=1&hl=en&persist_hl=1';;

		// get the contents from the url
		$raw_data = file_get_contents($info_url);
		
		// parse the data received and save it as an array
		$output = array();
		parse_str($raw_data,$output);

		// check the status of the get_video_info object
		if($output['status']=='ok'){
			
			// check for the cipher signature
			$cipher = (isset($output['use_cipher_signature']) &&  $output['use_cipher_signature']=='True') ? true : false;
							
				// if cipher is true then we have to change the plan and get the details from the video's youtube wbe page
				$yt_html = file_get_contents($yt_url);
				
				// parse for the script containing the configuration
				preg_match('/ytplayer.config = {(.*?)};/',$yt_html,$match);
				$yt_object = @json_decode('{'.$match[1].'}') ;

				/// check if we are able to parse data
				if(!is_object($yt_object)){
					//'Sorry! Unable to parse Data';
					return 'Error Code 1';
				}else{
					
					// parse available formats
					$formats = $yt_object->args->url_encoded_fmt_stream_map;
					// get the player id from assets section
					$player_id = strbtwn($yt_object->assets->js,'html5player-','.js');
					$player_id = explode("/", $player_id);
					$player_id = $player_id[0];
														
					// get the algo dictionary
					// first check if the file exists
					if(file_exists('./algo.json'))
						$algos = json_decode(file_get_contents('algo.json'),true);
					else{
						// API call to fetch the algo dictionary
						$algos_dict = file_get_contents("http://api.gitnol.com/getAlgo.php?playerID=".$player_id."&apikey=".$CipherAPIkey);
						
						// saving the algo dictonary in local env for easy access
						// Note: Developers should save the dictionary in their local env. 
						// Only make the API call for the new player ids which is not present in the algo dictionary.
						// Repeated violation will results in IP ban.
						file_put_contents('algo.json', $algos_dict);
						
						$algos = json_decode($algos_dict,true);
					}

					/// check if the algo exist for the given player id
					if(!array_key_exists($player_id, $algos)){
						
						// if the algo dictionary is old then fetch a new one
						$algos_dict = file_get_contents("http://api.gitnol.com/v4/getAlgo.php?playerID=".$player_id."&apikey=".$CipherAPIkey);
						file_put_contents('algo.json', $algos_dict);
						
						$algos = json_decode($algos_dict,true);
						$algo = $algos[$player_id][1];
						 
					}else{
						$algo = $algos[$player_id][1];
					}
													
					// download links formation
					$dlinks = array();
					$links  = explode(',',$formats);
					
					
					foreach ($links as $link) {
						parse_str($link,$linkarr);
						
						// parse link array one by one and decrypt the signature
						// If cipher is true then we have to decode it
						if($cipher){
							$dlinks[$linkarr['itag']] = $linkarr['url'] . "&signature=" . decrypt($linkarr['s'],$algo);
						}else{
							if(!isset($linkarr['sig']) || !isset($linkarr['s']))
								$dlinks[$linkarr['itag']] = $linkarr['url'];
							else
								$dlinks[$linkarr['itag']] = $linkarr['url'] . "&signature=" . isset($linkarr['s']) ? $linkarr['s'] : $linkarr['sig'];
						}
					}
					return $dlinks;
				}
				
		}else{
			//'Unable to get Video Info';
			return 'Error Code 3'; 
		}
	}
	// string helper function
	function strbtwn($content,$start,$end){
		$r = explode($start, $content);
		if (isset($r[1])){
			$r = explode($end, $r[1]);
			return $r[0];
		}
		return '';
	}
	
	// signature decoding 
	function decrypt($sig, $algo){
		$dsig = '';
		foreach($algo as $key => $value){
			$dsig .= $sig[$value];
		}
		return $dsig;
	}
?>
