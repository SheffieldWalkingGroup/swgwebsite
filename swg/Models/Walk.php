<?php
defined('_JEXEC') or die('Restricted access');
require_once("SWGBaseModel.php");
require_once("Leader.php");
require_once("Route.php");

/**
* A walk in our library.
* @see WalkInstance for an instance of a walk, with a date and a leader etc.
* @author peter
*
*/
class Walk extends SWGBaseModel implements Walkable {
	protected $id;
	protected $name;
	protected $distanceGrade;
	protected $difficultyGrade;
	protected $miles;
	protected $location;
	protected $isLinear;
	protected $startGridRef;
	protected $startPlaceName;
	protected $startLatLng;
	protected $endGridRef;
	protected $endPlaceName;
	protected $endLatLng;
	protected $description;
	protected $fileLinks;
	protected $information;
	protected $routeImage;
	protected $suggestedBy;
	protected $status;
	protected $specialTBC;
	protected $dogFriendly;
	protected $transportByCar;
	protected $transportPublic;
	protected $childFriendly;
	protected $routeVisibility;

	/**
	* Route for this walk
	* @var Route
	*/
	private $route;

	/**
	* Array of variable => dbfieldname
	* Only includes variables that can be represented directly in the database
	* (i.e. no arrays or objects)
	* Does not include ID as this may interfere with database updates
	* @var array
	*/
	private $dbmappings = array(
		'name'           => 'walkname',
		'distanceGrade'  => 'distancegrade',
		'difficultyGrade'=> 'difficultygrade',
		'miles'          => 'miles',
		'location'       => 'location',
		'isLinear'       => 'islinear',
		'startGridRef'   => 'startgridref',
		'startPlaceName' => 'startplacename',
		'endGridRef'     => 'endgridref',
		'endPlaceName'   => 'endplacename',
		'description'    => 'routedescription',
		'fileLinks'      => 'filelinks',
		'information'    => 'information',
		'routeImage'     => 'routeimage',
		'status'         => 'status',
		'specialTBC'     => 'special_tbc',
		'childFriendly'  => 'childfriendly',
		'dogFriendly'    => 'dogfriendly',
		'transportByCar' => 'transportbycar',
		'transportPublic'=> 'transportpublic',
		'routeVisibility'=> 'routevisibility',
	);

	public function fromDatabase(array $dbArr)
	{
		$this->id = $dbArr['ID'];
		
		parent::fromDatabase($dbArr);
		
		$this->suggestedBy = Leader::getLeader($dbArr['suggestedby']);
		
		// Also set the lat/lng
		if (!empty($this->startGridRef))
		{
			$startOSRef = getOSRefFromSixFigureReference($this->startGridRef);
			$startLatLng = $startOSRef->toLatLng();
			$startLatLng->OSGB36ToWGS84();
			$this->startLatLng = $startLatLng;
		}
		
		if (!empty($this->endGridRef))
		{
			$endOSRef = getOSRefFromSixFigureReference($this->endGridRef);
			$endLatLng = $endOSRef->toLatLng();
			$endLatLng->OSGB36ToWGS84();
			$this->endLatLng = $endLatLng;
		}
			
		// TODO: Load route?

	}

	// TODO: This isn't used - everything is in save()
	public function toDatabase(JDatabaseQuery &$query)
	{
		parent::toDatabase($query);
		
		$query->set("suggestedby", $this->suggestedBy->id);
	}

	public function valuesToForm()
	{
		return array(
		'id'=>$this->id,
		'name'=>$this->name,
		'distanceGrade'=>$this->distanceGrade,
		'difficultyGrade'=>$this->difficultyGrade,
		'miles'=>$this->miles,
		'location'=>$this->location,
		'isLinear'=>(int)$this->isLinear, // Joomla seems to ignore false?
		'startGridRef'=>$this->startGridRef,
		'startPlaceName'=>$this->startPlaceName,
		'endGridRef'=>$this->endGridRef,
		'endPlaceName'=>$this->endPlaceName,
		'description'=>$this->description,
		'fileLinks'=>$this->fileLinks,
		'information'=>$this->information,
		'routeImage'=>$this->routeImage,
		'suggestedBy'=>$this->suggestedBy,
		'status'=>$this->status,
		'specialTBC'=>$this->specialTBC,
		'dogFriendly'=>$this->dogFriendly,
		'transportByCar'=>$this->transportByCar,
		'transportPublic'=>$this->transportPublic,
		'childFriendly'=>$this->childFriendly,
			
		'route' => ($this->route instanceof Route ? $this->route->jsonEncode() : false),
		'routeVisibility' => $this->routeVisibility,
		);
	}

	public function __get($name)
	{
		// Load the route if we don't have it already
		// TODO: Only try once, and catch exceptions
		if ($name == "route" && !isset($this->route))
		{
			$this->loadRoute();
		}
		
		return $this->$name; // TODO: What params should be exposed?
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
		case "information":
			$this->$name = $value;
			break;
		// Booleans
		case "isLinear":
		case "dogFriendly":
		case "transportByCar":
		case "transportPublic":
		case "childFriendly":
			$this->$name = (bool)$value;
			break;
		// More specific processing
		case "distanceGrade":
			$value = strtoupper($value);
			if ($value == "A" || $value == "B" || $value == "C")
				$this->$name = $value;
			else if (!empty($value))
				throw new UnexpectedValueException("Distance grade must be A, B or C");
			break;
		case "difficultyGrade":
			$value = (int)$value;
			if ($value == 1 || $value == 2 || $value == 3)
				$this->$name = $value;
			else if (!empty($value))
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
			$value = str_replace(" ","",$value);
			if (empty($value))
				break;
			if (preg_match("/[A-Z][A-Z]([0-9][0-9]){3,}/", $value))
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
				throw new UnexpectedValueException("Grid references must be at least 6-figures, with the grid square letters before (e.g. SK123456)");
			break;
			
			
		// Checks TODO 
		case "location":
		case "fileLinks":
		case "routeImage":
		case "suggestedBy":
		case "status":
		case "specialTBC":
			$this->$name = $value;
			break;
		case "routeVisibility":
			$this->$name = (int)$value;
		}
	}
	public function __isset($name)
	{
		switch ($name)
		{
			case "route":
				return (isset($this->route));
				break;
			
		}
	}

	/**
	* Loads the route for this walk (if any)
	*/
	public function loadRoute()
	{
		// Load the route if we don't have it already
		// TODO: Only try once, and catch exceptions
		if (!isset($this->route))
		{
		$rt = Route::loadForWalkable($this,false,Route::Type_Planned,1);
		if (!empty($rt))
			$this->route = $rt[0];
		}
	}

	/**
	* Connects a route to this walk, and sets relevant data (e.g. length)
	* 
	* * The route may be able to give us:
	* * Distance
	*     - Calculated
	* * Location
	*     - Which region are most of the points in?
	* * Linearity
	*     - Is the end within 500m of the start?
	* * Start grid ref
	*     - Convert to OSGB36
	* * Start place name
	*     - May be stored as a waypoint in the route
	*     - List of known start points
	*     - Reverse geocoding
	* * End grid ref
	* * TODO: End place name
	* If any of these aren't available (i.e. start/end place names),
	* they are left unchanged. All others are always overwritten.
	* 
	* @param Route $r
	*/
	public function setRoute(Route &$r)
	{
		$this->route =& $r;
		
		// Get start and end places
		$start = $r->getWaypoint(0);
		$this->startGridRef = $start->osRef->toSixFigureString();
		$this->startPlaceName = $start->reverseGeocode();
		
		$end = $r->getWaypoint($r->numWaypoints()-1);
		$this->endGridRef = $end->osRef->toSixFigureString();
		$this->endPlaceName = $end->reverseGeocode();
		
		// Is this a linear walk? 
		// TODO: Magic number
		$this->isLinear = ($start->distanceTo($end) > 500);
		
		// Convert distance to miles, get the distance grade
		// Resolution is half a mile
		$this->miles = round($r->getDistance()*0.000621371192*2)/2;
		$this->distanceGrade = $this->getDistanceGrade($this->miles);
	}

	/**
	* Unset an existing route
	*/
	public function unsetRoute()
	{
		$this->route = null;
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

	/**
	* Gets walks suggested by a specified person 
	* @param Leader $suggester
	* TODO: Move to WalkFactory
	*/
	public static function getWalksBySuggester(Leader $suggester)
	{
		$db =& JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walks");
		
		// TODO: This is a stored proc currently - can we use this?
		$query->where(array(
			"suggestedby = ".(int)$suggester->id,
		));
		$query->order(array("walkname ASC"));
		$db->setQuery($query);
		$walkData = $db->loadAssocList();
		
		// Build an array of WalkInstances
		// TODO: Set actual SQL limit
		$walks = array();
		while (count($walkData) > 0) {
		$walk = new Walk();
		$walk->fromDatabase(array_shift($walkData));
		$walks[] = $walk;
		}
		
		return $walks;
	}

	// TODO: Move to WalkFactory
	public static function getSingle($id) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walks");

		$query->where(array("ID = ".intval($id)));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 1)
		{
			$walk = new Walk();
			$walk->fromDatabase($db->loadAssoc());
			return $walk;
		}
		else
			return null;
	}

	/**
	* Returns an array of instances of this walk, oldest first
	* TODO: Move to WalkInstanceFactory
	*/
	public function getInstances() {
		require_once("WalkInstance.php");
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walkprogrammewalks");

		$query->where(array("walklibraryid = ".intval($this->id)));
		$db->setQuery($query);
		
		$instanceData = $db->loadAssocList();
		
		$instances = array();
		while (count($instanceData) > 0) {
			$instance = new WalkInstance();
			$instance->fromDatabase(array_shift($instanceData));
			$instances[] = $instance;
		}

		return $instances;
	}

	/**
	* Save this walk to the database
	* Also saves any route attached to the walk
	*/
	public function save() {
		$db = JFactory::getDbo();
		
		// Commit everything as one transaction
		$db->transactionStart();
		$query = $db->getQuery(true);
		
		// First, do the basic fields
		foreach ($this->dbmappings as $var => $dbField)
		{
			$query->set($dbField." = '".$db->escape($this->$var)."'");
		}
		$query->set("suggestedby = ".$this->suggestedBy->id);
		
		// Update or insert?
		if (!isset($this->id))
		{
			$query->insert("walks");
		}
		else 
		{
			$query->where("ID = ".(int)$this->id);
			$query->update("walks");
		}
		
		$db->setQuery($query);
		$db->query();
		
		if (!isset($this->id))
		{
			// Get the ID from the database
			$this->id = $db->insertid();
		}
		
		// TODO: Handle failure
		
		// Commit the transaction - the route is not a critical part of the walk
		$db->transactionCommit();
		
		// Now save the route
		if (isset($this->route))
			$this->route->save();
	}
}
