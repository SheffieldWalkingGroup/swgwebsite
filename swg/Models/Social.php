<?php
	/**
	* A social
	*/
	require_once("Event.php");
	class Social extends Event {

	protected $bookingsInfo;
	protected $clipartFilename;

	protected $start;
	protected $end;

	// Start and end times for the new members' part (if applicable)
	protected $showNormal;
	protected $showNewMember;
	protected $newMemberStart;
	protected $newMemberEnd;

	protected $location;
	protected $postcode;
	protected $latLng;

	protected $cost;

	public $dbmappings = array(
		'name'		=> 'title',
		'description'	=> 'fulldescription',
		'okToPublish'	=> 'readytopublish',
		'bookingsInfo'	=> 'bookingsinfo',
		'clipartFilename'	=> 'clipartfilename',
		
		'location'		=> 'location',
		'postcode'		=> 'postcode',
		'cost'		=> 'cost',
		
		'showNormal'	=> 'shownormal',
		'showNewMember'	=> 'shownewmember',
	);
	
	public $type = "Social";

	public function fromDatabase(array $dbArr)
	{
		$this->id = $dbArr['SequenceID'];
		
		parent::fromDatabase($dbArr);
		
		$this->start = strtotime($dbArr['on_date']." ".$dbArr['starttime']);
		
		// TODO: Shouldn't make up an end time here - do that in the UI if/where needed
		if (!empty($dbArr['endtime']))
			$this->end = strtotime($dbArr['on_date']." ".$dbArr['endtime']);
		else
			$this->end = $this->start + 120*60;
		
		if (!empty($dbArr['newmemberstart']))
			$this->newMemberStart = strtotime($dbArr['on_date']." ".$dbArr['newmemberstart']);
		if (!empty($dbArr['newmemberend']))
			$this->newMemberEnd = strtotime($dbArr['on_date']." ".$dbArr['newmemberend']);
		
		// If end is the next day...
		if ($this->end < $this->start)
		{
		$this->end += 86400;
		}
		
		if (!empty($dbArr['latitude']) && !empty($dbArr['longitude']))
		$this->latLng = new LatLng($dbArr['latitude'], $dbArr['longitude']);
		
		$this->alterations->setVersion($dbArr['version']);
		$this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
	}

	public function toDatabase(JDatabaseQuery &$query)
	{
		$query->set("on_date = '".$query->escape(strftime("%Y-%m-%d",$this->start))."'");
		$query->set("starttime = '".$query->escape(strftime("%H:%M",$this->start))."'");
		
		if (!empty($this->end))
			$query->set("endtime = '".$query->escape(strftime("%H:%M", $this->end))."'");
			
		if (!empty($this->newMemberStart))
		$query->set("newmemberstart = '".$query->escape(strftime("%H:%M",$this->newMemberStart))."'");
		
		if (!empty($this->newMemberEnd))
			$query->set("newmemberend = '".$query->escape(strftime("%H:%M", $this->newMemberEnd))."'");
		
		$query->set("version = ".$this->alterations->version);
		$query->set("lastmodified = '".$query->escape($this->alterations->lastModified)."'");
		
		if (!empty($this->latLng))
		{
			$query->set("latitude = ".$this->latLng->lat);
			$query->set("longitude = ".$this->latLng->lng);
		}
		
		parent::toDatabase($query);
	}
	
	public function valuesToForm()
	{
		$values = array(
			'id'=>$this->id,
			'name'=>$this->name,
			'description'	=> $this->description,
			'okToPublish'	=> $this->okToPublish,
			
			'booking'		=> $this->bookingsInfo,
			'shownormal'	=> (int)$this->showNormal,
			'shownewmember'	=> (int)$this->showNewMember,
			
			'postcode'		=> $this->postcode,
			'location'		=> $this->location,
			'cost'			=> $this->cost,
			
		);
		
		if (!empty($this->start))
		{
			$values['date']		= strftime("%Y-%m-%d", $this->start);
			$values['starttime']= strftime("%H:%M", $this->start);
		}
		if (!empty($this->end))
			$values['endtime']	= strftime("%H:%M", $this->end);
		
		if (!empty($this->newMemberStart))
			$values['newMemberStart'] = strftime("%H:%M", $this->newMemberStart);
		if (!empty($this->newMemberEnd))
			$values['newMemberEnd'] = strftime("%H:%M", $this->newMemberEnd);
			
		if (!empty($this->latLng))
			$values['latLng'] = array('lat'=>$this->latLng->lat, 'lng'=>$this->latLng->lng);
			
		return $values;
			
	}
	
	/**
	* A social must have a name, a description and a start date/time.
	* It must be for current members and/or new members.
	*/
	public function isValid()
	{
		if(!empty($this->name) && !empty($this->description) && !empty($this->start))
		{
			if ($this->showNewMember || $this->showNormal)
			{
				return true;
			}
		}
		
		return false;
	}

	public function __get($name)
	{
		return $this->$name; // TODO: What params should be exposed?
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
			case "name":
			case "description":
			case "bookingsInfo":
			case "clipartFilename":
			case "cost":
			case "location":
				$this->$name = $value;
				break;
			case "okToPublish":
			case "showNormal":
			case "showNewMember":
				$this->$name = (bool)$value;
				break;
			case "start":
			case "newMemberStart":
			case "end":
			case "newMemberEnd":
				if (!empty($value) && is_numeric($value))
					$this->$name = $value;
				break;
			case "latLng":
				if ($value instanceof LatLng)
					$this->$name = $value;
				else if (is_array($value))
				{
					// Convert to LatLng
					if (isset($value['lat']) && is_numeric($value['lat']) && isset($value['lng']) && is_numeric($value['lng']))
					{
						$this->$name = new LatLng($value['lat'], $value['lng']);
					}
				}
				break;
			case "postcode":
				// Geolocate this postcode
				// TODO: This will wipe any existing latLng...
				// Get the postcode passed in
				if (preg_match("/^([A-Z]{1,2}[0-9]{1,2}[A-Z]?[ ]?[0-9][A-Z]{2})$/",$value))
				{
					$postcode = str_replace(" ","",$value);
					$curl = curl_init("http://www.uk-postcodes.com/postcode/".$postcode.".json");
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$res = json_decode(curl_exec($curl));
					if (isset($res->geo))
					{
						$this->latLng = new LatLng($res->geo->lat, $res->geo->lng);
					}
				}
				$this->postcode = $value;
				break;
		}
	}
	
	public static function numEvents($startDate=self::DateToday, $endDate=self::DateEnd, $getNormal=true, $getNewMember=true)
	{
		// Build a query to get future socials
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("count(1)");
		$query->from("socialsdetails");
		// TODO: This is a stored proc currently - can we use this?
		$where = array(
			"on_date >= '".self::timeToDate($startDate)."'",
			"on_date <= '".self::timeToDate($endDate)."'",
			"readytopublish",
		);
		// Hide normal/new member events if we're not interested
		if (!$getNormal || !$getNewMember)
		{
		if ($getNormal)
			$where[] = "shownormal";
		else if ($getNewMember)
			$where[] = "shownewmember";
		else
			$where[] = "false";
		}
		$query->where($where);
		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	* Gets the next few scheduled socials
	* @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
	* @return array Array of Socials
	*/
	public static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1, $offset=0, $reverse=false, $getNormal = true, $getNewMember = true, $showUnpublished=false) {
		
		// Build a query to get future socials
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("socialsdetails");
		// TODO: This is a stored proc currently - can we use this?
		$where = array(
			"on_date >= '".self::timeToDate($startDate)."'",
			"on_date <= '".self::timeToDate($endDate)."'",
		);
		if (!$showUnpublished)
		{
			$query->where("readytopublish");
		}
		// Hide normal/new member events if we're not interested
		if (!$getNormal || !$getNewMember)
		{
		if ($getNormal)
			$where[] = "shownormal";
		else if ($getNewMember)
			$where[] = "shownewmember";
		else
			$where[] = "false";
		}
		$query->where($where);
		if ($reverse)
			$query->order(array("on_date DESC", "title ASC"));
		else
			$query->order(array("on_date ASC", "title ASC"));
		$db->setQuery($query, $offset, $numToGet);
		$socialData = $db->loadAssocList();
		
		// Build an array of Socials
		$socials = array();
		while (count($socialData) > 0 && count($socials) != $numToGet) {
			$social = new Social();
			$social->fromDatabase(array_shift($socialData));
			$socials[] = $social;
		}

		return $socials;
	}

	/**
	* Gets a limited number of events, starting today and going forwards
	* Partly for backwards-compatibility, but also to improve readability
	* @param int $numEvents Maximum number of events to get
	*/
	public static function getNext($numEvents, $getNormal = true, $getNewMember = true) {
		return self::get(self::DateToday, self::DateEnd, $numEvents, 0, false, $getNormal, $getNewMember);
	}

	public static function getSingle($id) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("socialsdetails");
		
		$query->where(array("SequenceID = ".intval($id)));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 1)
		{
		$soc = new Social();
		$soc->fromDatabase($db->loadAssoc());
		return $soc;
		}
		else
		return null;
		
	}

	public function hasMap() {
		return (!empty($this->latLng) || !empty($this->postcode));
	}

	}