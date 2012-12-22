<?php
require_once("Event.php");
include_once("WalkMeetingPoint.php");
include_once("Leader.php");
require_once("Route.php");
/**
 * An instance of a walk, i.e. a walk with a date and a leader etc.
 * @author peter
 *
 */
class WalkInstance extends Event implements Walkable {
  protected $walk;
  protected $distanceGrade;
  protected $difficultyGrade;
  protected $miles;
  protected $location;
  protected $startGridRef;
  protected $startPlaceName;
  protected $startLatLng;
  protected $endGridRef;
  protected $endPlaceName;
  protected $endLatLng;

  protected $childFriendly;
  protected $dogFriendly;
  protected $speedy;
  protected $isLinear;
  protected $transportByCar;
  protected $transportPublic;

  protected $leader;
  protected $backmarker;
  protected $meetPoint;

  protected $dateAltered;

  protected $headCount;
  protected $mileometer;
  protected $reviewComments;
  protected $deleted;
  protected $cancelled;
  
  protected $hasRoute;
  
  /**
   * Array of variable => dbfieldname
   * Only includes variables that can be represented directly in the database
   * (i.e. no arrays or objects)
   * Does not include ID as this may interfere with database updates
   * @var array
   */
  public $dbmappings = array(
      'name'		=> 'name',
      'walk'		=> 'walklibraryid',
      'distanceGrade'	=> 'distancegrade',
      'difficultyGrade'	=> 'difficultygrade',
      'miles'          => 'miles',
      'location'       => 'location',
      'isLinear'       => 'islinear',
      'startGridRef'   => 'startgridref',
      'startPlaceName' => 'startplacename',
      'endGridRef'     => 'endgridref',
      'endPlaceName'   => 'endplacename',
      'description'    => 'routedescription',
      'childFriendly'  => 'childfriendly',
      'dogFriendly'    => 'dogfriendly',
      'speedy'		=> 'speedy',
      'challenge'	=> 'challenge',
      
      'okToPublish'	=> 'readytopublish',
      
      // TODO: Headcount, mileometer...
  );

  public function fromDatabase(array $dbArr)
  {
    $this->id = $dbArr['SequenceID'];
    
    parent::fromDatabase($dbArr);
    
    $this->start = strtotime($dbArr['WalkDate']." ".$dbArr['meettime']);
    $this->meetPoint = new WalkMeetingPoint($dbArr['meetplace'], $this->start, $dbArr['meetplacetime']);
    $this->leader = Leader::getLeader($dbArr['leaderid']);
    $this->backmarker = Leader::getLeader($dbArr['backmarkerid']);
    if (!empty($dbArr['leadername']))
      $this->leader->setDisplayName($dbArr['leadername']);
    if (!empty($dbArr['backmarkername']))
      $this->backmarker->setDisplayName($dbArr['backmarkername']);
      
    // Set up the alterations
    $this->alterations->setVersion($dbArr['version']);
    $this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
    
    $this->alterations->setDetails($dbArr['detailsaltered']);
    $this->alterations->setCancelled($dbArr['cancelled']);
    $this->alterations->setPlaceTime($dbArr['meetplacetimedetailsaltered']);
    $this->alterations->setOrganiser($dbArr['walkleaderdetailsaltered']);
    $this->alterations->setDate($dbArr['datealtered']);
    
    // Also set the lat/lng
    $startOSRef = getOSRefFromSixFigureReference($this->startGridRef);
    $startLatLng = $startOSRef->toLatLng();
    $startLatLng->OSGB36ToWGS84();
    $this->startLatLng = $startLatLng;
    
    $endOSRef = getOSRefFromSixFigureReference($this->endGridRef);
    $endLatLng = $endOSRef->toLatLng();
    $endLatLng->OSGB36ToWGS84();
    $this->endLatLng = $endLatLng;
  }
  
  public function toDatabase(JDatabaseQuery &$query)
  {
    parent::toDatabase($query);
    
    $query->set("WalkDate", strftime("%Y-%m-%d",$this->start));
    $query->set("meettime", strftime("%H:%M",$this->start));
    $query->set("meetplace", $this->meetPoint->id);
    $query->set("meetplacetime", $this->meetPoint->extra);
    
    $query->set("leaderid", $this->leader->id);
    if ($this->leader->hasDisplayName)
      $query->set("leadername", $this->leader->displayName);
    $query->set("backmarkerid", $this->backmarker->id);
    if ($this->backmarker->hasDisplayName)
      $query->set("backmarkername", $this->backmarker->displayName);
    
    $query->set('version', $this->alterations->version);
    $query->set('lastmodified', $this->alterations->lastModified);
    
    $query->set('detailsaltered', $this->alterations->details);
    $query->set('cancelled', $this->alterations->cancelled);
    $query->set('meetplacetimedetailsaltered', $this->alterations->placeTime);
    $query->set('walkleaderdetailsaltered', $this->alterations->organiser);
    $query->set('datealtered', $this->alterations->date);
  }
  
	public function valuesToForm()
	{
		$values = array(
			'id'			=> $this->id,
			'name'			=> $this->name,
			'description'	=> $this->description,
			'okToPublish'	=> $this->okToPublish,
			'date'			=> strftime("%Y-%m-%d", $this->start),
			
			'walkid'		=> $this->walk->id,
			'distancegrade'	=> $this->distanceGrade,
			'difficultygrade'=>$this->difficultyGrade,
			'miles'			=> $this->miles,
			'location'		=> $this->location,
			'startGridRef'	=> $this->startGridRef,
			'startPlaceName'=> $this->startPlaceName,
			'endGridRef'	=> $this->endGridRef,
			'endPlaceName'	=> $this->endPlaceName,
			
			'childfriendly'	=> $this->childFriendly,
			'dogfriendly'	=> $this->dogFriendly,
			'speedy'		=> $this->speedy,
			'linear'		=> $this->isLinear,
			'transportbycar'=> $this->transportByCar,
			'transportpublic'=>$this->transportPublic,
			
			'leaderid'		=> $this->leader->id,
			'leadername'	=> $this->leader->displayName,
			'backmarkerid'	=> $this->backmarker->id,
			'backmarkername'=> $this->backmarker->displayName,
			'meetpointid'	=> $this->meetpoint->id,
			'meetdetails'	=> $this->meetpoint->extra,
			
			'altereddetails'=> $this->alterations->details,
			'alteredmeetpoint'=>$this->alterations->placeTime,
			'alteredleader'	=> $this->alterations->organiser,
			'altereddate'	=> $this->alterations->date,
			'cancelled'		=> $this->alterations->cancelled,
			
		);
		
		if ($this->leader->hasDisplayName)
			$values['leadername'] = $this->leader->displayName;
		if ($this->backmarker->hasDisplayName)
			$values['backmarkername'] = $this->backmarker->displayName;
		$routes = Route::loadForWalkable($this, false, 1);
		if (!empty($routes))
			$values['routeid'] = $routes[0]->id;
			
		return $values;
			
	}
	
	/**
	 * A walk must have a name, a description and a start date/time.
	 */
	public function isValid()
	{
		if(!empty($this->name) && !empty($this->description) && !empty($this->start))
		{
			return true;
		}
		
		return false;
	}

  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }

  /**
   * Gets a limited number of events, starting today and going forwards
   * Partly for backwards-compatibility, but also to improve readability
   * @param int $numEvents Maximum number of events to get
   */
  public static function getNext($numEvents) {
    return self::get(self::DateToday, self::DateEnd, $numEvents);
  }

  /**
   * Gets the next few scheduled walks
   * @param int $startDate Get events on or after this date. Unix time, also accepts day constants (see Event.php)
   * @param int $endDate Get events on or before this date. Unix time, also accepts day constants (see Event.php)
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @param bool $showUnpublished Show unpublished events
   * @return array Array of WalkInstances
   */
  public static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1, $showUnpublished = false) {
    // Build a query to get future walks that haven't been deleted.
    // We do want cancelled walks - users should be notified about these.
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walkprogrammewalks");

    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "WalkDate >= '".self::timeToDate($startDate)."'",
        "WalkDate <= '".self::timeToDate($endDate)."'",
        "NOT deleted",
    ));
    if (!$showUnpublished)
    {
      $query->where("readytopublish");
    }
    $query->order(array("WalkDate ASC", "meettime ASC"));
    $db->setQuery($query);
    $walkData = $db->loadAssocList();

    // Build an array of WalkInstances
    // TODO: Set actual SQL limit
    $walks = array();
    while (count($walkData) > 0 && count($walks) != $numToGet) {
      $walk = new WalkInstance();
      $walk->fromDatabase(array_shift($walkData));
      $walks[] = $walk;
    }

    return $walks;
  }

  public function isCancelled() {
    return $this->cancelled;
  }

  public function getEventType() {
    return "walk";
  }

  public function getWalkDay() {
    if (date("N",$this->start) < 6)
      return "Weekday";
    else
      return date("l",$this->start);
  }

  /**
   * Estimate the finish time as:
   *   1 hour after the start time (unless the meet point is the walk start)
   *   + 0.5 hours per mile
   *   TODO: Rounded up to the nearest hour
   */
  public function estimateFinishTime() {
    $finish = $this->start;
    if (!$this->meetPoint->isAtWalkStart())
      $finish += 3600; // Add 1 hour travelling time
    $hoursWalking = 0.5*$this->miles;
    return ($finish + 3600*$hoursWalking);
  }

  public static function getSingle($id) {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walkprogrammewalks");

    $query->where(array("SequenceID = ".intval($id)));
    $db->setQuery($query);
    $res = $db->query();
    if ($db->getNumRows($res) == 1)
    {
      $wi = new WalkInstance();
      $wi->fromDatabase($db->loadAssoc());
      return $wi;
    }
    else
      return null;

  }
  
  public function hasMap() {
    return true;
  }
}