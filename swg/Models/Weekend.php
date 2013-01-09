<?php
require_once("Event.php");
/**
 * A weekend away
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
  
  public function fromDatabase(array $dbArr)
  {
    $this->id = $dbArr['ID'];
    
    parent::fromDatabase($dbArr);
    
    $this->start = strtotime($dbArr['startdate']);
    $this->endDate = strtotime($dbArr['enddate']);
    $this->noContactOfficeHours = (bool)$dbArr['nocontactofficehours'];
    $this->challenge = (bool)$dbArr['challenge'];
    $this->swg = (bool)$dbArr['swg'];
    
    $this->alterations->setVersion($dbArr['version']);
    $this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
    
    $this->latLng = new LatLng($dbArr['latitude'], $dbArr['longitude']);
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
    
    if (!empty($this->latLng))
	{
		$query->set("latitude = ".$this->latLng->lat);
		$query->set("longitude = ".$this->latLng->lng);
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
			
			'startdate'		=> strftime("%Y-%m-%d", $this->start),
			'enddate'		=> strftime("%Y-%m-%d", $this->endDate),
			
			'area'			=> $this->area,
			'url'			=> $this->url,
			'places'		=> $this->places,
			'cost'			=> $this->cost,
			'contact'		=> $this->contact,
			'nocontactofficehours' => $this->noContactOfficeHours,
			'bookingsopen'	=> $this->bookingsOpen,
			'challenge'		=> $this->challenge,
			'swg'			=> $this->swg,
			
		);
		
		if (!empty($this->latLng))
			$values['latLng'] = array('lat'=>$this->latLng->lat, 'lng'=>$this->latLng->lng);
		
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
			case "endDate":
				if (!empty($value))
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
			
		}
	}
  
  /**
   * Gets a limited number of events, starting today and going forwards
   * Partly for backwards-compatibility, but also to improve readability
   * @param int $numEvents Maximum number of events to get
   */
  public static function getNext($numEvents) {
    return self::get(self::DateToday, self::DateEnd, $numEvents);
  }
  
	public static function numEvents($startDate=self::DateToday, $endDate=self::DateEnd, $getNormal=true, $getNewMember=true)
	{
		// Build a query to get future socials
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("count(1) as count");
		$query->from("weekendsaway");
		// TODO: This is a stored proc currently - can we use this?
		$where = array(
			"enddate >= '".self::timeToDate($startDate)."'",
			"startdate <= '".self::timeToDate($endDate)."'",
			"readytopublish",
		);
		$query->where($where);
		$db->setQuery($query);
		return $db->loadResult();
	}
  
  /**
   * Gets the next few scheduled weekends
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Weekends
   */
  public static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1, $offset=0, $reverse=false) {
    // Build a query to get future weekends
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("weekendsaway");
    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "enddate >= '".self::timeToDate($startDate)."'",
        "startdate <= '".self::timeToDate($endDate)."'",
        "oktopublish",
    ));
    if ($reverse)
		$query->order(array("startdate DESC"));
	else
		$query->order(array("startdate ASC"));
    $db->setQuery($query, $offset, $numToGet);
    $weekendData = $db->loadAssocList();
  
    // Build an array of Weekends
    // TODO: Set actual SQL limit
    $weekends = array();
    while (count($weekendData) > 0 && count($weekends) != $numToGet) {
      $weekend = new Weekend();
      $weekend->fromDatabase(array_shift($weekendData));
      $weekends[] = $weekend;
    }
  
    return $weekends;
  }
  
  public static function getSingle($id) {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("weekendsaway");
    
    $query->where(array("ID = ".intval($id)));
    $db->setQuery($query);
    $res = $db->query();
    if ($db->getNumRows($res) == 1)
    {
      $we = new Weekend();
      $we->fromDatabase($db->loadAssoc());
      return $we;
    }
    else
      return null;
    
  }
  
	public function hasMap() {
		return (!empty($this->latLng));
	}	
}