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
protected $walkid;
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
protected $challenge;
protected $isLinear;

private $leaderId;
private $leaderName;
protected $leader;
private $backmarkerId;
private $backmarkerName;
protected $backmarker;
private $meetPointId;
private $meetPlaceTime;
protected $meetPoint;

protected $headCount;
protected $distance;
protected $mileometer;
protected $reviewComments;
protected $deleted;

protected $hasRoute;
protected $routeVisibility;

/**
* Track for this walk
* @var Route
*/
private $track;

/**
* Array of variable => dbfieldname
* Only includes variables that can be represented directly in the database
* (i.e. no arrays or objects)
* Does not include ID as this may interfere with database updates
* @var array
*/
public $dbmappings = array(
	'name'				=> 'name',
	'walkid'			=> 'walklibraryid',
	'distanceGrade'		=> 'distancegrade',
	'difficultyGrade'	=> 'difficultygrade',
	'miles'				=> 'miles',
	'location'			=> 'location',
	'isLinear'			=> 'islinear',
	'startGridRef'		=> 'startgridref',
	'startPlaceName'	=> 'startplacename',
	'endGridRef'		=> 'endgridref',
	'endPlaceName'		=> 'endplacename',
	'description'		=> 'routedescription',
	'childFriendly'		=> 'childfriendly',
	'dogFriendly'		=> 'dogfriendly',
	'speedy'			=> 'speedy',
	'challenge'			=> 'challenge',
	
	'leaderId'			=> 'leaderid',
	'leaderName'		=> 'leadername',
	'backmarkerId'		=> 'backmarkerid',
	'backmarkerName'	=> 'backmarkername',
	'meetPointId'		=> 'meetplace',
	'meetPlaceTime' 	=> 'meetplacetime',
	
	'okToPublish'		=> 'readytopublish',
	'routeVisibility'	=> 'routevisibility',
	
	'headCount'			=> 'headcount',
	'distance'			=> 'distance',
	
	// TODO: Headcount, mileometer...
);

/**
 * These variables are database objects, only loaded on demand.
 * The loading must be triggered when returning them.
 */
public $loadOnDemand = array(
	"leader", "backmarker", "meetPoint"
);

public $type = "Walk";

	public function getType()
	{
		return self::TypeWalk;
	}

public function fromDatabase(array $dbArr)
{
	$this->id = $dbArr['SequenceID'];
	
	parent::fromDatabase($dbArr);
	
	$this->start = strtotime($dbArr['WalkDate']." ".$dbArr['meettime']);
	
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
	
	if (!empty($this->start))
	{
		$query->set("WalkDate = '". $query->escape(strftime("%Y-%m-%d",$this->start))."'");
		$query->set("meettime = '". $query->escape(strftime("%H:%M",$this->start))."'");
	}
	
	if (!empty($this->meetPoint))
	{
		$query->set("meetplace = ". (int)$this->meetPoint->id);
		$query->set("meetplacetime = '". $query->escape($this->meetPoint->extra)."'");
	}
	else if (!empty($this->meetPointId))
	{
		$query->set("meetplace = ". (int)$this->meetPointId);
		$query->set("meetplacetime = '". $query->escape($this->meetPlaceTime)."'");
	}
	else
	{
		$query->set("meetplace = 0");
		$query->set("meetplacetime = '". $query->escape($this->meetPlaceTime)."'");
	}
	
	if (!empty($this->leaderId))
		$query->set("leaderid = ". (int)$this->leaderId);
	else
		$query->set("leaderid = ".Leader::TBC); // TBC
	if (isset($this->leader) && $this->leader->hasDisplayName)
		$query->set("leadername = '". $query->escape($this->leader->displayName)."'");
	elseif (isset($this->leaderName))
		$query->set("leadername = '".$query->escape($this->leaderName)."'");
	else
		$query->set("leadername = ''");
		
	if (!empty($this->backmarkerId))
		$query->set("backmarkerid = ". (int)$this->backmarkerId);
	else
		$query->set("backmarkerid = ".Leader::TBC); // TBC
	if (isset($this->backmarker) && $this->backmarker->hasDisplayName)
		$query->set("backmarkername = '". $query->escape($this->backmarker->displayName)."'");
	elseif (isset($this->backmarkerName))
		$query->set("backmarkername = '".$query->escape($this->backmarkerName)."'");
	else
		$query->set("backmarkername = ''");
		
	if (isset($this->distance))
		$query->set("distance = ".(int)$this->distance);
	if (isset($this->headCount))
		$query->set("headcount = ".(int)$this->headCount);
	
	$query->set('version = '. (int)$this->alterations->version);
	$query->set('lastmodified = '. (int)$this->alterations->lastModified);
	
	$query->set('detailsaltered = '. (int)$this->alterations->details);
	$query->set('cancelled = '. (int)$this->alterations->cancelled);
	$query->set('meetplacetimedetailsaltered = '. (int)$this->alterations->placeTime);
	$query->set('walkleaderdetailsaltered = '. (int)$this->alterations->organiser);
	$query->set('datealtered = '. (int)$this->alterations->date);
}

	public function valuesToForm()
	{
		$values = array(
			'id'			=> $this->id,
			'name'			=> $this->name,
			'description'	=> $this->description,
			'okToPublish'	=> $this->okToPublish,
			'date'			=> strftime("%Y-%m-%d", $this->start),
			
			'walkid'		=> $this->walkid,
			'difficultyGrade'=>$this->difficultyGrade,
			'miles'			=> $this->miles,
			'location'		=> $this->location,
			'startGridRef'	=> $this->startGridRef,
			'startPlaceName'=> $this->startPlaceName,
			'endGridRef'	=> $this->endGridRef,
			'endPlaceName'	=> $this->endPlaceName,
			
			'childFriendly'	=> $this->childFriendly,
			'dogFriendly'	=> $this->dogFriendly,
			'speedy'		=> $this->speedy,
			'isLinear'		=> $this->isLinear,
			'challenge'		=> $this->challenge,
			
			'leaderid'		=> $this->leaderId,
			'leadername'	=> $this->leaderName,
			'backmarkerid'	=> $this->backmarkerId,
			'backmarkername'=> $this->backmarkerName,
			'meetPointId'	=> $this->meetPointId,
			'meetTime' 		=> strftime("%H:%M", $this->start),
			'meetPlaceTime'	=> $this->__get("meetPoint")->extra,
			
			'headCount'		=> $this->headCount,
			'distance'		=> $this->distance,
			
			'alterations_details'=> $this->alterations->details,
			'alterations_date'=>$this->alterations->placeTime,
			'alterations_organiser'	=> $this->alterations->organiser,
			'alterations_placeTime'	=> $this->alterations->date,
			'alterations_cancelled'		=> $this->alterations->cancelled,
			
			'track' => ($this->track instanceof Track ? $this->track->jsonEncode() : false),
		);
		
		if (isset($this->leader) && $this->leader->hasDisplayName)
			$values['leadername'] = $this->leader->displayName;
		if (isset($this->backmarker) && $this->backmarker->hasDisplayName)
			$values['backmarkername'] = $this->backmarker->displayName;
			
		// Try to load routes
		if (isset($this->id))
		{
			$routes = Route::loadForWalkable($this, false, Route::Type_Planned, 1);
			if (!empty($routes))
				$values['routeid'] = $routes[0]->id;
			
			$tracks = Route::loadForWalkable($this, false, Route::Type_Logged, 1);
			if (!empty($tracks))
				$values['trackid'] = $tracks[0]->id;
		}
		
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
	
	public function canDownloadRoute()
	{
		// TODO: Leaders can download more routes
		// No point searching for a route if we can't download it
		if ($this->routeVisibility < Route::Visibility_Members)
			return false;
			
		$route = Route::loadForWalkable($this, false, Route::Type_Planned);
		return (isset($route));
	}

	public function __get($name)
	{
		// Load connected objects from the database as needed
		switch($name)
		{
			case "meetPoint":
				$this->meetPoint = new WalkMeetingPoint($this->meetPointId, $this->start, $this->meetPlaceTime);
				break;
			case "leader":
				$this->leader = Leader::getLeader($this->leaderId);
				if (!empty($this->leaderName))
					$this->leader->setDisplayName($this->leaderName);
				break;
			case "backmarker":
				$this->backmarker = Leader::getLeader($this->backmarkerId);
				if (!empty($this->backmarkerName))
					$this->backmarker->setDisplayName($this->backmarkerName);
				break;
			case "walk":
				return Walk::getSingle($this->walkid);
			case "track":
				// Load the track if we don't have it already
				// TODO: Only try once, and catch exceptions
				if (!isset($this->track))
					$this->loadTrack();
				return $this->track;
			case "distance":
				// If we don't have a (real) distance set, convert the (estimated) miles into metres and give that
				break;
			
			// If the walk is circular, return the equivalent start point
			case "endPlaceName":
			case "endLatLng":
			case "endGridRef":
				if ($this->isLinear)
					return $this->$name;
				else
				{
				    $var = "start".substr($name, 3);
				    return $this->$var;
				}
				
				
		}
		return $this->$name; // TODO: What params should be exposed?
	}

	public function __isset($name)
	{
		switch ($name)
		{
			case "meetPoint":
			case "meetPointId":
				return (isset($this->meetPoint) || isset($this->meetPointId));
				break;
			case "leader":
			case "leaderId":
				return (isset($this->leader) || isset($this->leaderId));
				break;
			case "backmarker":
			case "backmarkerId":
				return (isset($this->backmarker) || isset($this->backmarkerId));
				break;
			case "track":
				return (isset($this->track));
				break;
			default:
				return (isset($this->$name));
			
		}
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
			// Strings - just save them (TODO: Safety checks?)
			case "name":
			case "startPlaceName":
			case "endPlaceName":
			case "description":
				$this->$name = $value;
				break;
			// Integer
			case "start":
			case "headCount":
			case "distance":
				$this->$name = (int)$value;
				break;
			// Booleans
			case "isLinear":
			case "dogFriendly":
			case "childFriendly":
			case "speedy":
			case "challenge":
				$this->$name = (bool)$value;
				break;
			// More specific processing
			case "distanceGrade":
				$value = strtoupper($value);
				if (empty($value))
					$this->$name = null;
				else if ($value == "A" || $value == "B" || $value == "C")
					$this->$name = $value;
				else
					throw new UnexpectedValueException("Distance grade must be A, B or C");
				break;
			case "difficultyGrade":
				$value = (int)$value;
				if (empty($value))
					$this->$name = null;
				else if ($value == 1 || $value == 2 || $value == 3)
					$this->$name = $value;
				else
					throw new UnexpectedValueException("Difficulty grade must be 1, 2 or 3");
				break;
				
			case "miles":
				$value = (float)$value;
				if ($value >= 0)
				{
					$this->$name = $value;
					$this->distanceGrade = $this->getDistanceGrade($value);
				}
				else
					throw new UnexpectedValueException("Distance must be positive"); // TODO: Validate >0 when saving
				break;
				
			// Grid references - start with two letters, then an even number of digits - at least 6
			case "startGridRef":
			case "endGridRef":
				$value = strtoupper(str_replace(" ","",$value));
				if (empty($value))
				{
					$this->$name = null;
					if ($name == "startGridRef")
						$this->startLatLng = null;
					else
						$this->endLatLng = null;
				}
				else if (preg_match("/[A-Z][A-Z]([0-9][0-9]){3,}/", $value))
				{
					$this->$name = $value;
					// Also set the lat/lng
					$osRef = getOSRefFromSixFigureReference($value);
					$latLng = $osRef->toLatLng();
					$latLng->OSGB36ToWGS84();
					if ($name == "startGridRef")
						$this->startLatLng = $latLng;
					else
						$this->endLatLng = $latLng;
				}
				else
				{
					throw new UnexpectedValueException("Grid references must be at least 6-figures, with the grid square letters before (e.g. SK123456)");
				}
				break;
				
			// Connected objects
			case "leader":
			case "leaderId":
				if (empty($value))
				{
					$this->leader = null;
					$this->leaderId = null;
				}
				else if ($value instanceof Leader)
				{
					$this->leader = $value;
					$this->leaderId = $value->id;
				}
				else if (is_int($value) || ctype_digit($value))
				{
					$this->leaderId = (int)$value;
					$this->leader = null; // Will be loaded when needed
				}
				else 
				{
					throw new UnexpectedValueException("Leader or Leader ID must be a Leader or an integer");
				}
				break;
			case "backmarker":
			case "backmarkerId":
				if (empty($value))
				{
					$this->backmarker = null;
					$this->backmarkerId = null;
				}
				if ($value instanceof Leader)
				{
					$this->backmarker = $value;
					$this->backmarkerId = $value->id;
				}
				else if (is_int($value) || ctype_digit($value))
				{
					$this->backmarkerId = (int)$value;
					$this->backmarker = null; // Will be loaded when needed
				}
				else 
				{
					throw new UnexpectedValueException("Backmarker or backmarker ID must be a Leader or an integer");
				}
				break;
			case "meetPoint":
			case "meetPointId":
				if (empty($value))
				{
					$this->meetPoint = null;
					$this->meetPointId = null;
				}
				elseif ($value instanceof WalkMeetingPoint)
				{
					$this->meetPoint = $value;
					$this->meetPointId = $value->id;
				}
				else if (is_int($value) || ctype_digit($value))
				{
					$this->meetPointId = (int)$value;
					$this->meetPoint = null;
				}
				else
				{
					throw new UnexpectedValueException("Meetpoint or MeetPointID must be a WalkMeetingPoint or an integer");
				}
				if (isset($this->meetPoint) && !empty($this->meetPlaceTime))
				{
					$this->meetPoint->setExtra($this->meetPlaceTime);
				}
				break;
			case "walkid":
				$this->walkid = (int)$value;
				break;
			case "meetPlaceTime":
				$this->meetPlaceTime = $value;
				if (isset($this->meetPoint))
					$this->meetPoint->setExtra($value);
				break;
				
			// Checks TODO 
			case "location":
			case "suggestedBy":
			case "status":
			case "specialTBC":
				$this->$name = $value;
				break;
			case "routeVisibility":
				$this->$name = (int)$value;
				break;
				
			default:
				// All others - fall through to Event
				parent::__set($name, $value);
		}
	}
	
	/**
	* Loads the track for this walk (if any)
	*/
	public function loadTrack()
	{
		// Load the route if we don't have it already
		// TODO: Only try once, and catch exceptions
		if (!isset($this->track))
		{
			$rt = Route::loadForWalkable($this,false,Route::Type_Logged,1);
			if (!empty($rt))
				$this->track = $rt[0];
		}
	}
	
	/**
	* Assigns a track to this walks
	* TODO: Store the recorded length
	* TODO: Verify the track matches this walk (probably a separate function)
	* @param Route $r
	*/
	public function setTrack(Route &$r)
	{
		$this->track =& $r;
	}
	
	/**
	* Unset an existing track
	*/
	public function unsetTrack()
	{
		$this->track = null;
	}

	/**
	* Calculate the distance grade of a walk
	* @param float $miles Number of miles
	*/
	private function getDistanceGrade($miles)
	{
		if ($miles <= 8)
			return "A";
		else if ($miles <= 12)
			return "B";
		else
			return "C";
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
		if (!$this->__get("meetPoint")->isAtWalkStart())
		$finish += 3600; // Add 1 hour travelling time
		$hoursWalking = 0.5*$this->miles;
		return ($finish + 3600*$hoursWalking);
	}

	public function hasMap() {
		return true;
	}
	
	public function getOrganiser()
	{
		return $this->__get("leader")->getJoomlaUser();
	}
	
	public function getOrganiserWord()
	{
		return "leader";
	}
	
	public function isOrganiser($user)
	{
		if ($user instanceof Leader)
		{
			return ($user->id == $this->leaderId);
		}
		else if ($user instanceof JUser)
		{
			return ($this->__get("leader")->joomlaUserID == $user->id);
		}
	}
}