<?php
require_once("Event.php");
/**
 * A weekend away
 * 
 * Note: Weekends don't have place/time alterations.
 * There's no start time, and if the place is altered then the whole event should be cancelled & recreated
 */
class Weekend extends Event {
protected $endDate;
protected $placeName;
protected $area;
protected $url;
protected $places;
protected $cost;
protected $contact;
protected $noContactOfficeHours;
protected $bookingsOpen;
protected $challenge;
protected $swg;
protected $latLng;
protected $paymentDue;

public $dbmappings = array(
	'name'	=> 'name',
	'placeName'	=> 'placename',
	'area'	=> 'area',
	'description'	=> 'description',
	'url'		=> 'url',
	'places'		=> 'places',
	'cost'		=> 'cost',
	'contact'		=> 'contact',
	'bookingsOpen'	=> 'bookingsopen',
	'okToPublish'	=> 'oktopublish',
	
);

public $type = "Weekend";
	public function getType()
	{
		return self::TypeWeekend;
	}

public function fromDatabase(array $dbArr)
{
	$this->id = $dbArr['ID'];
	
	parent::fromDatabase($dbArr);
	
	$this->__set("start",strtotime($dbArr['startdate']));
	$this->endDate = strtotime($dbArr['enddate']);
	$this->noContactOfficeHours = (bool)$dbArr['nocontactofficehours'];
	$this->challenge = (bool)$dbArr['challenge'];
	$this->swg = (bool)$dbArr['swg'];
	
	$this->alterations->setVersion($dbArr['version']);
	$this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
	
	if (!empty($dbArr['latitude']) && !empty($dbArr['longitude']))
		$this->latLng = new LatLng($dbArr['latitude'], $dbArr['longitude']);
	else if ($dbArr['latitude'] == "" && $dbArr['longitude'] == "")
		$this->latLng = null;
	
	// Set up the alterations
	$this->alterations->setVersion($dbArr['version']);
	$this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
	
	$this->alterations->setDetails($dbArr['detailsaltered']);
	$this->alterations->setCancelled($dbArr['cancelled']);
	$this->alterations->setOrganiser($dbArr['organiseraltered']);
	$this->alterations->setDate($dbArr['datealtered']);
}

public function toDatabase(JDatabaseQuery &$query)
{
	parent::toDatabase($query);
	
	$query->set("startdate = '".$query->escape(strftime("%Y-%m-%d",$this->start))."'");
	$query->set("enddate = '".$query->escape(strftime("%Y-%m-%d",$this->endDate))."'");
	
	$query->set("nocontactofficehours = ".(int)$this->noContactOfficeHours);
	$query->set("challenge = ".(int)$this->challenge);
	$query->set("swg = ".(int)$this->swg);
	
	$query->set("version = ".$this->alterations->version);
	$query->set("lastmodified = '".$query->escape($this->alterations->lastModified)."'");
	$query->set('detailsaltered = '. (int)$this->alterations->details);
	$query->set('cancelled = '. (int)$this->alterations->cancelled);
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
}

	public function valuesToForm()
	{
		$values = array(
			'id'			=> $this->id,
			'name'			=> $this->name,
			'placeName'		=> $this->placeName,
			'description'	=> $this->description,
			'okToPublish'	=> $this->okToPublish,
			
			'area'			=> $this->area,
			'url'			=> $this->url,
			'places'		=> $this->places,
			'cost'			=> $this->cost,
			'contact'		=> $this->contact,
			'nocontactofficehours' => $this->noContactOfficeHours,
			'bookingsopen'	=> $this->bookingsOpen,
			'challenge'		=> $this->challenge,
			'swg'			=> $this->swg,
			
			'alterations_details'	=> $this->alterations->details,
			'alterations_date'		=> $this->alterations->placeTime,
			'alterations_organiser'	=> $this->alterations->organiser,
			'alterations_cancelled'	=> $this->alterations->cancelled,
		);
		
		if (!empty($this->start))
			$values['startdate'] = strftime("%Y-%m-%d", $this->start);
		if (!empty($this->endDate))
			$values['enddate'] = strftime("%Y-%m-%d", $this->endDate);
		
		if (!empty($this->latLng))
			$values['latLng'] = $this->latLng->lat.",".$this->latLng->lng;
		
		return $values;
	}
	
	/**
	* A weekend must have a name, a description, a start & end date, a place name and an area.
	*/
	public function isValid()
	{
		return (!empty($this->name) && !empty($this->description) && !empty($this->start) && !empty($this->endDate) && !empty($this->placeName) && !empty($this->area));
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
			case "placeName":
			case "area":
			case "description":
			case "url":
			case "contact":
			case "bookingsOpen":
			case "cost":
				$this->$name = $value;
				break;
			case "places":
				$this->$name = (int)$value;
				break;
			case "okToPublish":
			case "challenge":
			case "swg":
				$this->$name = (bool)$value;
				break;
			case "start":
				if (!empty($value))
				{
					$this->$name = $value;
					// Calculate the payment due date.
					// Payment is due by the end of the month before the date 2 weeks before the weekend
					// Looks like "last day of -1 month" is only supported by PHP 5.3, so let's do this the old-fashioned way
					$threeWeeksBefore = $value - (86400 * 14);
					// Looks like "last day of -1 month" is only supported by PHP 5.3, so let's do this the old-fashioned way
                                       $this->paymentDue = strtotime(strftime("%Y-%m-01", $threeWeeksBefore)) - 86400;}
				else
				{
					$this->$name = null;
					$this->paymentDue = null;
				}
				break;
			case "endDate":
				if (!empty($value))
					$this->$name = $value;
				else
					$this->$name = null;
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
					// Convert to LatLng
					if (isset($value['lat']) && isset($value['lng']))
					{
						if (is_numeric($value['lat']) && is_numeric($value['lng']))
						{
							$this->$name = new LatLng($value['lat'], $value['lng']);
						}
						else
						{
							$this->$name = null;
						}
					}
				}
				break;
			default:
				// All others - fall through to Event
				parent::__set($name, $value);
			
		}
	}

	public function hasMap() {
		return (!empty($this->latLng));
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
