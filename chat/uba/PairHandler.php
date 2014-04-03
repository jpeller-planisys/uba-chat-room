<?php

class PairHandler
{
	var $db;

	var $combinatorics = null;

	var $seen_pairs = null;

	var $internal_data = null;
	function __construct($in_db)
	{
		$this->db = $in_db;
		$this->combinatorics = new Math_Combinatorics;
		$aux = $this->db->getAssoc("SELECT data FROM current_round_data", true);

		if($aux)
			$this->internal_data = json_decode($aux["data"], true);
		else
		{
			$this->setInternalDataStructure();
		}
			
	}

	function setInternalDataStructure()
	{
		$this->internal_data = array("played_rounds" => array(), "game" => array(), "rounds_data" => array());
	}


	function dumpToResults()
	{
		$obj = new stdClass();
		$obj->played_rounds = $this->internal_data["played_rounds"];
		$obj->game = $this->internal_data["game"];
		$obj->opinion_changes = $this->db->getAssoc("SELECT * FROM opinion_changes");
		$obj->messages = $this->db->getAssoc("SELECT * FROM ajax_chat_messages");
		$obj->users = $this->db->getAssoc("SELECT * FROM ajax_chat_online");
		$this->db->query("INSERT INTO results(`data`) VALUES('".json_encode($obj)."')");
	}

	function saveAndReset()
	{
		$this->dumpToResults();
		$this->reset();
	}

	function reset()
	{
		
		$result = $this->db->query("DELETE FROM current_round_data;");
		$result = $this->db->query("DELETE FROM opinion_changes;");
		$result = $this->db->query("DELETE FROM ajax_chat_messages;");
		return true;
	}

	function flush()
	{
		$this->db->query("DELETE FROM current_round_data");
		$this->db->query("INSERT INTO current_round_data VALUES('".json_encode($this->internal_data)."')");
	}

	static function pairContains($pair, $individual)
	{
		return in_array($individual, $pair);
	}


	function getPlayedRounds()
	{
		return $this->internal_data["played_rounds"];

	}

	function getAllRounds()
	{
		return array_keys($this->internal_data["game"]);
	}

	function addPlayedRound($index)
	{
		$this->internal_data["played_rounds"][] = $index;
		$this->flush();
	}

	function getNextRound()
	{
		$played = $this->getPlayedRounds();


		$all = $this->getAllRounds();
		$available = array_values(array_diff($all, $played));
		
		if(count($available) == 0)
		{
			Log::log("Game is over!");
			return false;
		}

		$index = rand(0, count($available) - 1);
		
		$this->addPlayedRound($available[$index]);

		return $this->internal_data["game"][$available[$index]];

	}

	function currentRound()
	{
		return count($this->internal_data["played_rounds"]);
	}


	function initializeFor($ids)
	{
		$this->reset();
		if(count($ids) % 2 != 0 )
		{
			Log::log("Total users is not even");
			return false;
		}

		$all_pairs = array_map('array_values', $this->combinatorics->combinations($ids, 2));

		$valid_rounds =  $this->combinatorics->combinations($all_pairs, count($ids)/2, array("PairHandler", "isValidRound"));
		
		

		$valid_rounds = array_map('array_values', $valid_rounds);


		Log::debug("Total users: ". count($ids));
		Log::debug("Total pairs: ".count($all_pairs));
		Log::debug("Total valid rounds: ".count($valid_rounds));

		unset($all_pairs);
		
		$this->valid_rounds = &$valid_rounds;// = $this->filterValidRounds($all_rounds);
		
		
		
		
		$step = 0;
		$game = array();
		$used_in_step = array();
		
		for ($i=0; $i < count($ids)-1; $i++) 
			$step_data[$i] = array("unviable_nodes" => array(), "already_tried" => array(), "marked_used" => array());


		while($step < count($ids)-1 && $step >= 0)
		{
			Log::debug("Paso: $step");
			if($this->addRound($game, $valid_rounds, $step_data[$step]) !== false)
			{
				if($this->validGame($game, $valid_rounds))
				{

					Log::debug("\tADVANCE un round: ".$this->reprRound($valid_rounds[$game[count($game)-1]]));
					Log::debug("\tel juego ahora es asi:".$this->reprGame($game, $valid_rounds));
					Log::debug("----");

					$step++;
				}
				else
				{
					Log::debug("\tSTAY: agregue un round, pero hizo al juego invalido");
					Log::debug("\tel juego era es asi: ".$this->reprGame($game, $valid_rounds));
					
					$step_data[$step]["already_tried"][] = array_pop($game);
					
					Log::debug("\tquedo asi: ".$this->reprGame($game, $valid_rounds));
					Log::debug("\ty step_data asi: ".$this->reprStepData($step_data, $step));;
					Log::debug("----");
				}
				
			}
			else
			{
				
				$this->backTrackStep($step, $valid_rounds, $step_data, $game);
				$step--;

				Log::debug("BACKTRACK:".$this->reprGame($game, $valid_round));;
				Log::debug("step_data: ".$this->reprStepData($step_data, $step));
				Log::debug("step_data+1: ".$this->reprStepData($step_data, $step+1));
				Log::debug("----");

			}
		}
		
		foreach($game as $round_index)
		{
			$valid_round_sequence[] = $valid_rounds[$round_index];
		}
		$this->internal_data["game"] = $valid_round_sequence;
		$this->internal_data["played_rounds"] = array();
		$this->flush();
		return true;
	}
	
	function pairEquals($pair1, $pair2)
	{
		return (($pair1[0] == $pair2[0] && $pair1[1] == $pair2[1]) || ($pair1[0] == $pair2[1] && $pair1[1] == $pair2[0]));
	}

	private function validGame($game, $valid_rounds)
	{
		if(count($game) < 2) return true;

		$added_round = $valid_rounds[$game[count($game)-1]];
		for ($i=0; $i < count($game)-1; $i++) 
		{ 
			$a_round = $valid_rounds[$game[$i]];
			for ($k=0; $k < count($a_round); $k++) { 
				
				$a_pair = $a_round[$k];
				for ($j=0; $j < count($added_round); $j++) 
				{ 
					$one_added_round_pair = $added_round[$j];

					if($this->pairEquals($one_added_round_pair, $a_pair))
					{
						//echo $this->reprPair($one_added_round_pair)."==".$this->reprPair($a_pair);
						return false;
					}
				}
			}
		}
		return true;
	}

	//check for all pairs in round to be different
	static function isValidRound($in_round)
	{
		$round = array_values($in_round);
		for ($i=0; $i < count($round); $i++) { 
			for ($j= $i+1; $j < count($round); $j++) { 
				$all_diff = true;
				foreach($round[$i] as $one)
					foreach($round[$j] as $other) $all_diff &= $one != $other;
			
				if(!$all_diff) return false;
				
			}
		}
		return true;
	}


	private function filterValidRounds($rounds)
	{
		$rounds = array_map('array_values', $rounds);
		$valid_rounds =	array_filter($rounds, array("PairHandler", "isValidRound"));
		return $valid_rounds;
	}


	private function addRound(&$game, &$valid_rounds, &$this_step)
	{
		foreach($valid_rounds as $k => $v)
		{
			if(!in_array($k, $this_step["already_tried"]) && !in_array($k, $this_step["unviable_nodes"]))$usables[]=$k;	
		}

		if(!$usables) return false;

		$random_index = rand(0, count($usables)-1);

		$round_index = $usables[$random_index];

		$game[] = $round_index;

		return true;

	}

	private function backTrackStep($step, &$valid_rounds, &$step_data, &$game)
	{
		$bad_round = array_pop($game);
		$step_data[$step-1]["unviable_nodes"][] = $bad_round;

		//echo "backtrac step: {$step}<br>";
		$step_data[$step]["already_tried"] = array();
	}




	function reprStepData($array, $step)
	{
		
		$str = "";
		foreach($array[$step] as $ind => $vals)
		{				
			if($vals)
			{
					$str.="$ind: [";
					foreach($vals as $val)$str.="$val,";
					$str= substr_replace($str, "", -1)."], ";
			}
		}

		$str= substr_replace($str, "", -2);
		$str.="], ";
			
		
		$str= substr_replace($str, "", -2);

		$str.=")";
		return $str;
	}

	function reprPair($pair)
	{
		return "({$pair[0]}, {$pair[1]})";
	}

	function reprRound($round)
	{
		$str="[";
		if(is_array($round))
		{
			$data = $round;

			if($data)
				foreach($data as $pair)
				{
					$str.= $this->reprPair($pair).",";	
				} 
		}
		

		if($str != "[") $str= substr_replace($str, "", -1);
		return $str."]";
	}

	function reprGame($game, $rounds = null)
	{
		if($rounds == null)
		 $rounds = &$this->valid_rounds;
		$str= "{";
		if($game)
		foreach($game as $round_index)
		{
			$str.=$this->reprRound($rounds[$round_index]).",";	
		}
		
		

		if($str != "{") $str= substr_replace($str, "", -1);
		return $str."}";	
	}


	

}


?>