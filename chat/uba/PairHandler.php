<?php

class PairHandler
{
	var $db;

	var $combinatorics = null;

	var $seen_pairs = null;

	function __construct($in_db)
	{
		$this->db = $in_db;
		$this->combinatorics = new Math_Combinatorics;
	}

	function getSeenPairs()
	{

		if($this->seen_pairs !== null) return $this->seen_pairs;

		$result = $this->db->query('SELECT one, other FROM seen_pairs;');
		
		$res = array();
		while($row = $result->fetch()) 
			$res[] = array($row["one"], $row["other"]);
		
		$result->free();
		$this->seen_pairs = $res;
		return $this->seen_pairs;
	}

	function seenPair($i, $j)
	{
		
		$seen_pairs = $this->getSeenPairs();

		foreach($seen_pairs as $pair) 
			if(($pair[0] == $i && $pair[1] == $j) || ($pair[1] == $i && $pair[0] == $j)) 
				return true;
		
		return false;
	}

	function savePair($i, $j)
	{
		
		if($this->seenPair($i, $j))
		{
			echo "The pair ($i, $j) was already seen";
			return false;
		}

		$result = $this->db->query("INSERT INTO seen_pairs(one, other) VALUES($i, $j);");
		$this->seen_pairs[] = array($i, $j);
		return true;

	}

	function reset()
	{
		$result = $this->db->query("DELETE FROM seen_pairs;");
		$this->seen_pairs = array();
		return true;
	}

	function pairEquals($pair1, $pair2)
	{
		return (($pair1[0] == $pair2[0] && $pair1[1] == $pair2[1]) || ($pair1[0] == $pair2[1] && $pair1[1] == $pair2[0]));
	}


	function getAllPairs($ids)
	{
		return array_map('array_values', $this->combinatorics->combinations($ids, 2));
	}
	
	function getAvailablePairs($ids)
	{
		$all = $this->getAllPairs($ids);
		$seen = $this->getSeenPairs();
		$avail = array();
		//pre($seen);
		for ($i=0; $i < count($all); $i++) { 
			$found = false;
			
			for ($j=0; $j < count($seen) && !$found; $j++) 
				$found |= $this->pairEquals($all[$i], $seen[$j]);
			
			if(!$found) $avail[] = $all[$i];
		}
		return $avail;
	}

	function pairContains($pair, $individual)
	{
		return in_array($individual, $pair);
	}
	
	function removeIndividualsFromPairs($pairs, $pair)
	{
		$result = array();

		foreach ($pairs as $one_pair) 
		{
			if(!$this->pairContains($one_pair, $pair[0]) && !$this->pairContains($one_pair, $pair[1])) $result[] = $one_pair;
		}

		return $result;
	}

	function chooseOnePair($available_pairs, $unassigned_individuals)
	{
		for ($i=0; $i < count($available_pairs); $i++)  $valid_indexes[$i] = $i;
		

		while(count($valid_indexes) > 0)
		{
			$index_of_indexes = rand(0, count($valid_indexes) -1);	
			$i = $valid_indexes[$index_of_indexes];

			//echo "avail:".count($available_pairs)."<br>";
			//echo "una:".count($unassigned_individuals)."<br>";
			//echo "index:".count($valid_indexes)."<br>";
			//echo "i: {$i} <br>";
			

			$post_selection_pairs = $available_pairs;
			$pair = $available_pairs[$i];
			//pre($pair, "par");
			$post_selection_pairs = $this->removeIndividualsFromPairs($available_pairs, $pair);
			//pre($post_selection_pairs, "post");

			if($this->existsOnePairForEachIndividual($post_selection_pairs, array_values(array_diff($unassigned_individuals, $pair))))
				return $i;


			$valid_indexes = array_values(array_diff($valid_indexes, [$i]));

		}
		
		Log::log("Error in chooseOnePair");
		return 400000;

	}

	function existsOnePairForEachIndividual($pairs, $individuals)
	{
		//pre($pairs);
		//pre($individuals);
		//die();
		for($i=0; $i < count($individuals); $i++) { 
			
			$found = false;
			for ($j=0; $j < count($pairs) && ! $found; $j++) 
				$found |= $this->pairContains($pairs[$j], $individuals[$i]);
			
			if(!$found) return false;
		}
		return true;
	}

	function generateRoundPairs($ids)
	{
		
		if(count($ids) % 2 != 0 )
		{
			Log::log("Total users is not even");
			return false;
		}

		$available_pairs = $this->getAvailablePairs($ids);
		//pre($available_pairs);
		//die();
		$unassigned_individuals = $ids;
		$selected_pairs = array();
		
		if(count($available_pairs) == 0)
		{
			Log::log("All combinations were seen! End of the experiment");
			return false;
		}

		
		while(count($unassigned_individuals) > 0)
		{
			
			$i = $this->chooseOnePair($available_pairs, $unassigned_individuals);
			
			$pair = $available_pairs[$i];
			
			$available_pairs = $this->removeIndividualsFromPairs($available_pairs, $pair);
			$selected_pairs[] = $pair;
			if(!$pair)
			{
				pre($i, "i");	
				pre($available_pairs, "avail");
				pre($unassigned_individuals, "unassigned");
				pre($selected_pairs, "selected");
			} 
			$unassigned_individuals =  array_values(array_diff($unassigned_individuals, $pair));

		}

		foreach($selected_pairs as $pair) $this->savePair($pair[0], $pair[1]);
		return $selected_pairs;
		
	}

}


?>