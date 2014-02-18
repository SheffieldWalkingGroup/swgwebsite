<?php

	JLoader::register('Event', JPATH_BASE."/swg/Models/Event.php");
	/**
	* A social
	*/
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
	
	

	public function getType()
	{
		return self::TypeSocial;
	}

	public function fromDatabase(array $dbArr)
	{
		$this->id = $dbArr['SequenceID'];
		
		parent::fromDatabase($dbArr);
		
		$this->start = strtotime($dbArr['on_date']." ".$dbArr['starttime']);
		
		if (!empty($dbArr['endtime']))
			$this->end = strtotime($dbArr['on_date']." ".$dbArr['endtime']);
		
		if (!empty($dbArr['newmemberstart']))
			$this->newMemberStart = strtotime($dbArr['on_date']." ".$dbArr['newmemberstart']);
		if (!empty($dbArr['newmemberend']))
			$this->newMemberEnd = strtotime($dbArr['on_date']." ".$dbArr['newmemberend']);
		
		// If end is the next day...
		if (!empty($this->end) && $this->end < $this->start)
		{
			$this->end += 86400;
		}
		
		if (!empty($dbArr['latitude']) && !empty($dbArr['longitude']))
			$this->latLng = new LatLng($dbArr['latitude'], $dbArr['longitude']);
		else if ($dbArr['latitude'] == "" && $dbArr['longitude'] == "")
			$this->latLng = null;
		
		// Set up the alterations
		$this->alterations->setVersion($dbArr['version']);
		$this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
		
		$this->alterations->setDetails($dbArr['detailsaltered']);
		$this->alterations->setCancelled($dbArr['cancelled']);
		$this->alterations->setPlaceTime($dbArr['placetimealtered']);
		$this->alterations->setOrganiser($dbArr['organiseraltered']);
		$this->alterations->setDate($dbArr['datealtered']);
		
	}

	public function toDatabase(JDatabaseQuery &$query)
	{
		$query->set("on_date = '".$query->escape(strftime("%Y-%m-%d",$this->start))."'");
		if (date("Hi",$this->start) != 0)
			$query->set("starttime = '".$query->escape(strftime("%H:%M",$this->start))."'");
		else
			$query->set("starttime = NULL");
		
		if (!empty($this->end))
			$query->set("endtime = '".$query->escape(strftime("%H:%M", $this->end))."'");
		else
			$query->set("endtime = NULL");
			
		if (!empty($this->newMemberStart))
			$query->set("newmemberstart = '".$query->escape(strftime("%H:%M",$this->newMemberStart))."'");
		else
			$query->set("newmemberstart = NULL");
		
		if (!empty($this->newMemberEnd))
			$query->set("newmemberend = '".$query->escape(strftime("%H:%M", $this->newMemberEnd))."'");
		else
			$query->set("newmemberend = NULL");
		
		$query->set("version = ".$this->alterations->version);
		$query->set("lastmodified = '".$query->escape($this->alterations->lastModified)."'");
		$query->set('detailsaltered = '. (int)$this->alterations->details);
		$query->set('cancelled = '. (int)$this->alterations->cancelled);
		$query->set('placetimealtered = '. (int)$this->alterations->placeTime);
		$query->set('organiseraltered = '. (int)$this->alterations->organiser);
		$query->set('datealtered = '. (int)$this->alterations->date);
	
		if (!empty($this->latLng))
		{
			$query->set("latitude = ".$this->latLng->lat);
			$query->set("longitude = ".$this->latLng->lng);
		}
		else
		{
			$query->set("latitude = NULL");
			$query->set("longitude = NULL");
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
			
			'bookingsInfo'	=> $this->bookingsInfo,
			'shownormal'	=> (int)$this->showNormal,
			'shownewmember'	=> (int)$this->showNewMember,
			
			'postcode'		=> $this->postcode,
			'location'		=> $this->location,
			'cost'			=> $this->cost,
			
			'alterations_details'	=> $this->alterations->details,
			'alterations_date'		=> $this->alterations->placeTime,
			'alterations_organiser'	=> $this->alterations->organiser,
			'alterations_placeTime'	=> $this->alterations->date,
			'alterations_cancelled'	=> $this->alterations->cancelled,
		);
		
		if (!empty($this->start))
		{
			$values['date']		= strftime("%Y-%m-%d", $this->start);
			if (date("Hi",$this->start) != 0)
				$values['starttime']= strftime("%H:%M", $this->start);
		}
		if (!empty($this->end))
			$values['endtime']	= strftime("%H:%M", $this->end);
		
		if (!empty($this->newMemberStart))
			$values['newMemberStart'] = strftime("%H:%M", $this->newMemberStart);
		if (!empty($this->newMemberEnd))
			$values['newMemberEnd'] = strftime("%H:%M", $this->newMemberEnd);
			
		if (!empty($this->latLng))
			$values['latLng'] = $this->latLng->lat.",".$this->latLng->lng;
			
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
				else if ($value == "")
					$this->$name = null;
				else
				{
					var_dump($name);
					var_dump($value);
					}
				break;
			case "latLng":
				if ($value instanceof LatLng)
					$this->$name = $value;
				else if (is_string($value))
				{
					// Is it in JSON?
					if (substr($value, 0, 2) == "[{")
					{
						$value = json_decode($value);
						$value = $value[0];
						$this->$name = new LatLng($value->lat, $value->lon);
					}
					else
					{
						$this->$name = SWG::parseLatLongTuple($this->value);
					}
				}
				else if (is_array($value))
				{
					// Convert to LatLng - deliberate fallthrough
					if (isset($value['lat']) && isset($value['lng']))
					{
						if (is_numeric($value['lat']) && isset($value['lng']) && is_numeric($value['lng']))
						{
							$this->$name = new LatLng($value['lat'], $value['lng']);
						}
						else if ($value['lat'] == "" && $value['lng'] == "")
						{
							$this->$name = null;
						}
					}
				}
				else if ($value == null)
				{
					$this->$name = null;
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
			default:
				// All others - fall through to Event
				parent::__set($name, $value);
		}
	}

	public function hasMap() {
		return (!empty($this->latLng) || !empty($this->postcode));
	}
	
	public function getOrganiser()
	{
		return null; // not implemented yet
	}
	
	public function isOrganiser($user)
	{
		return false; // not implemented yet
	}
}