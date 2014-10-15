<?php

/**
 * Author: Akhilesh Chandra Gupta
 * Author URI: http://www.bitnol.com
 * API URI: http://api.gitnol.com
 * Description: Demo of the application of Cipher API
 * License: MIT License
 */

	// Video with cipher signature 
	$video_id = "zDrNLZ1uJ2w";

	// get_video_info url formation
	// although for cipher signature we have to get the details from the video's webapge not from get_video_info object
	$info_url = "http://www.youtube.com/get_video_info?el=detailpage&asv=3&video_id=".$video_id;
	
	// youtube webpage url formation
	$yt_url = 'http://www.youtube.com/watch?v='.$video_id.'&gl=US';

	// get the contents from the url
	$raw_data = file_get_contents($info_url);
	
	// parse the data received and save it as an array
	$output = array();
	parse_str($raw_data,$output);

	// check the status of the get_video_info object
	if($output['status']=='ok'){
		
		// check for the cipher signature
		$cipher = (isset($output['use_cipher_signature']) &&  $output['use_cipher_signature']=='True') ? true : false;

		// If cipher is true then we have to decode it
		if($cipher == true){
			
			// if cipher is true then we have to change the plan and get the details from the video's youtube wbe page
			$yt_html = file_get_contents($yt_url);
			
			// parse for the script containing the configuration
			preg_match('/ytplayer.config = {(.*?)};/',$yt_html,$match);
			$yt_object = @json_decode('{'.$match[1].'}') ;

			/// check if we are able to parse data
			if(!is_object($yt_object)){
				echo 'Sorry! Unable to parse Data';
			}else{
				
				// parse available formats
				$formats = $yt_object->args->url_encoded_fmt_stream_map;
				// get the player id from assets section
				$player_id = strbtwn($yt_object->assets->js,'html5player-','.js');
				$player_id = explode("/", $player_id);
				$player_id = $player_id[0];
				
				echo 'Player ID: '.$player_id.'<br /><hr />';
								
				// get the algo dictionary
				// first check if the file exists
				if(file_exists('./algo.json'))
					$algos = json_decode(file_get_contents('algo.json'),true);
				else{
					// API call to fetch the algo dictionary
					$algos_dict = file_get_contents("http://api.gitnol.com/getAlgo.php?playerID=".$player_id);
					
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
					$algos_dict = file_get_contents("http://api.gitnol.com/getAlgo.php?playerID=".$player_id);
					file_put_contents('algo.json', $algos_dict);
					
					$algos = json_decode($algos_dict,true);
					$algo = $algos[$player_id][1];
					 
				}else{
					$algo = $algos[$player_id][1];
				}
				
				echo 'Algo Used: '.$algo.'<br /><hr />';
							
				// download links formation
				$dlinks = array();
				$links  = explode(',',$formats);
				
				echo 'Download links <br /><br />';
				
				foreach ($links as $link) {
					parse_str($link,$linkarr);
					
					// parse link array one by one and decrypt the signature
					$dlinks[$linkarr['itag']] = $linkarr['url'] . "&signature=" . decrypt($linkarr['s'],$algo);
					echo $linkarr['itag'].'<br />';
					echo $dlinks[$linkarr['itag']].'<br /><br />';
				}
				echo '<hr />';
			}
		}else{
			echo 'Video Is not cipher not needed to decode';
		}
	}else{
		echo 'Unable to get Video Info';
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
	// parse the python string operation into php string operation
	function decrypt($sig,$algo){
		$funcarr = explode(' + ', $algo);
		$decrypt = '';
		foreach($funcarr as $singfunc){
			$singfunc = substr($singfunc,2,-1);
			$operators = explode(':', $singfunc);
			if (sizeof($operators) == 1) {
				$decrypt .= $sig[$operators[0]];
			}
			if (sizeof($operators) == 2) {
				if($operators[0] == ''){
					$decrypt .= substr($sig, 0 ,$operators[1]);
				}
				if($operators[1] == ''){
					$decrypt .= substr($sig, $operators[0]);
				}
				if($operators[0] >= 0 && $operators[1] >= 0){
					$decrypt .= substr($sig, $operators[0], $operators[1] - $operators[0]);
				}
			}
			if (sizeof($operators) == 3) {
				if($operators[0] == '' && $operators[1] == ''){
					$decrypt .= strrev($sig);
				}
				if($operators[0] >=0 && $operators[1] == ''){
					$decrypt .= strrev(substr($sig, 0, $operators[0] + 1));
				}
				if($operators[0] >=0 && $operators[1] >= 0){
					$decrypt .= strrev(substr($sig, $operators[1] + 1, $operators[0] - $operators[1]));
				}
			}
		}
		return $decrypt;
	}
?>
