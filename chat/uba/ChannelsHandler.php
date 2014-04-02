<?php
class ChannelsHandler
{
	
	var $db = null;

	function __construct(&$db)
	{
		$this->db = &$db;
	}

	function getChannels($nameIndexed = true)
	{
		$channels = array();
		foreach($this->db->getAssoc("SELECT id, name FROM channels") as $db_item)
		{
			if($nameIndexed)$channels[ $db_item["name"]] = $db_item["id"];
			else $channels[] = $db_item["name"];
		}
			
		
		return $channels;
	}

	function initializeFor($n)
	{
		return $this->createChannels($n/2);
	}


	function createChannels($n)
	{
		$this->reset();
		$query = "INSERT INTO channels(id, name) VALUES";
		for($i=1; $i <= $n; $i++) 
			$query.="($i, 'Channel_".str_pad($i, 2, "0", STR_PAD_LEFT)."'),";
		

		if($this->db->query(substr($query, 0, -1)))
		{
			return $this->getChannels();
		}
	}

	function deleteChannels()
	{
		return (bool)$this->db->query("TRUNCATE TABLE channels");
	}

	function moveEveryoneToPublic()
	{
		$sql = 'UPDATE ajax_chat_online SET
				newChannel = 0,
				channelSwitch 	= 1,
				dateTime 	= NOW() ;';
					
		$result = $this->db->query($sql);
		
		return true;
	}
	

	function reset()
	{
		$this->moveEveryoneToPublic();
		$this->deleteChannels();

	}

}



?>