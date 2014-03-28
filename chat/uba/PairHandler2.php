<?php

class PairHandler2
{
	var $db;

	var $combinatorics = null;

	var $seen_pairs = null;

	var $internal_data = array();
	function __construct($in_db)
	{
		$this->db = $in_db;
		$this->combinatorics = new Math_Combinatorics;
		$aux = $this->db->getAssoc("SELECT data FROM current_round_data", true);

		if($aux)
			$this->internal_data = json_decode($aux["data"], true);
	}

	function reset()
	{
		$result = $this->db->query("DELETE FROM current_round_data;");
		return true;
	}

	function flush()
	{
		$this->db->query("DELETE FROM current_round_data");
		$this->db->query("INSERT INTO current_round_data VALUES('".json_encode($this->internal_data)."')");
	}


	function isValidRound($round)
	{
		//pre($round);
		for ($i=0; $i < count($round); $i++) { 
			for ($j=0; $j < count($round); $j++) { 
				
				if($j != $i)
				{
					if(!($round[$i][0] != $round[$j][0] && 
						 $round[$i][1] != $round[$j][0] && 
						 $round[$i][1] != $round[$j][1] && 
						 $round[$i][0] != $round[$j][1])) return false;
				}
			}
		}
		return true;
	}

	function filterValidRounds($rounds)
	{
		$valid_rounds = array();
		for ($i=0; $i < count($rounds); $i++) 
		{ 
			$rounds[$i] = array_map('array_values', $rounds[$i]);
			$rounds[$i] = array_values($rounds[$i]);
			if($this->isValidRound($rounds[$i]))
			{
				$valid_rounds[] = $rounds[$i];	
			} 
		}

		return $valid_rounds;
	}

	function getPlayedRounds()
	{
		return $this->internal_data["played_rounds"]? $this->internal_data["played_rounds"] : array();

	}

	function addPlayedRound($index)
	{
		$this->internal_data["played_rounds"][] = $index;
		$this->flush();
	
	}

	function filterUnseenRounds($rounds)
	{

		$indexes = $this->getSeenRounds();
		for ($i=0; $i < count($indexes); $i++) 
			unset($rounds[$indexes[$i]]);

		return $rounds;
	}

	function pairEquals($pair1, $pair2)
	{
		return (($pair1[0] == $pair2[0] && $pair1[1] == $pair2[1]) || ($pair1[0] == $pair2[1] && $pair1[1] == $pair2[0]));
	}


	function pairInArray($pair, $arrayOfPairs)
	{
		for ($i=0; $i < count($arrayOfPairs); $i++) { 
			if($this->pairEquals($pair, $arrayOfPairs[$i])) return true;
		}
		return false;
	}

	function filterValidGames($paths)
	{
		$res = array();
		foreach($paths as $path)
		{
			$pairs_in_path = array();
			$valid_path = true;
			foreach($path as $round)
			{
				foreach($round as $pair)
				{
					if($this->pairInArray($pair, $pairs_in_path)) $valid_path = false;
					else $pairs_in_path[] = $pair;
				}
			}
			if($valid_path) $res[] = $path;
		}
		$res = array_map('array_values', $res);
		return array_values($res);
	}

	function initializeFor($ids)
	{
		$this->reset();
		if(count($ids) % 2 != 0 )
		{
			Log::log("Total users is not even");
			return false;
		}

		$all_pairs = $this->combinatorics->combinations($ids, 2);
		$all_rounds = $this->combinatorics->combinations($all_pairs, count($ids)/2);
		$valid_rounds = $this->filterValidRounds($all_rounds);
		
		$games = $this->combinatorics->combinations($valid_rounds, count($ids)-1);
		$valid_games = $this->filterValidGames($games);

		$random_index = rand(0, count($valid_games)-1);

		$game = $valid_games[$random_index];

		$this->internal_data["game"] = $game;
		$this->flush();

	}

	function getRound()
	{
		$all_rounds = $this->internal_data["game"];
		if(count($all_rounds) == 0)
		{
			Log::log("Run initializeFor before!");
			return false;		
		}

		foreach ($this->getPlayedRounds() as $index) 
		{
			unset($all_rounds[$index]);
		}
		

		if(count($all_rounds) == 0)
		{
			Log::log("Experiment ended!");
			return false;	
		}

		foreach($all_rounds as $index => $round)
			$indexes[] = $index;


		$this_round = rand(0, count($indexes)-1);
		
		$this->addPlayedRound($indexes[$this_round]);

		return $all_rounds[$indexes[$this_round]];

	}

}


?>