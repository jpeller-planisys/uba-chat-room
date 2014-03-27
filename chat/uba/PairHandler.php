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
		return true;
	}

	function pairEquals($pair1, $pair2)
	{
		return (($pair1[0] == $pair2[0] && $pairs1[1] == $pair2[1]) || $pair1[0] == $pair2[1] && $pairs1[1] == $pair2[0]));
	}


	function generateRoundPairs($onlineUsersID)
	{
		$all_combinations = $this->combinatorics->combinations($onlineUsersID, 2);
		return;
		$seen_pairs = $this->getSeenPairs();
		echo "seen_pairs:";
		print_r($seen_pairs);
		$combinations = $this->combinationsFor($total_users);
		
		echo "<br>combinations:".$combinations."<br>";
		if($total_users % 2 != 0 )
		{
			Log::log("Total users is not even");
			return false;
		}

		if(count($seen_pairs) == $combinations)
		{
			Log::log("All combinations were seen! End of the experiment");
			return false;
		}

		$assigned_users = array();
		$round_pairs = array();
		while(count($round_pairs) <= ($total_users/2) && $anti_infinity_counter < 10000000)
		{
			$anti_infinity_counter++; 

			$i = rand(1, $total_users);
			$j = rand(1, $total_users);
			if($i == $j || in_array($i, $assigned_users) || in_array($j, $assigned_users)) continue;
			if($this->seenPair($i, $j)) continue;
			

			if($this->savePair($i, $j))
			{
				echo "($i, $j)<br>";
				$assigned_users[] = $i;
				$assigned_users[] = $j;
				$round_pairs[] = array($i, $j);
			}
			
		}

		return false;
	}

}


?>