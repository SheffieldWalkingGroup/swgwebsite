<?php

require_once("SWGBaseModel.php");
require_once("Waypoint.php");
require_once("Walk.php");
require_once("WalkInstance.php");
include_once(JPATH_SITE."/swg/lib/phpcoord/phpcoord-2.3.php");

class Route extends SWGBaseModel implements Iterator {

	/**
	* The route is not visible - only used to calculate walk length etc.
	* @var int
	*/
	const Visibility_None = 1; // Avoid confusion with 'not set'
	/**
	* Leaders can download the route
	* @var int
	*/
	const Visibility_Leaders = 10;
	/**
	* The route is displayed on the map
	* @var int
	*/
	const Visibility_Map = 20;
	/**
	* Any member can download the route
	* @var int
	*/
	const Visibility_Members = 30; // This should be the default
	
	/**
	 * A planned route, entered before the walk
	 */
	const Type_Planned = 10;
	
	/**
	 * A logged route recorded on the actual walk
	 */
	const Type_Logged = 20;


	/**
	* The route ID. 
	* If this is set, the route will override this route ID when saving. 
	* Otherwise, it'll insert a new route
	* @var int
	*/
	private $id;

	/**
	* The walk this is a route for, or a WalkInstance it's a track for
	* @var Walkable
	*/
	private $walk;
		
	/**
	* Waypoints of this route
	* @var Waypoint[]
	*/
	private $wayPoints = array();
	/**
	* Cumulative distance in metres
	* @var int
	*/
	private $distance;
	/**
	* Cumulative ascent in metres. May be useful for estimating difficulty.
	* @var int
	*/
	private $ascent;

	/**
	* Person who uploaded the route - Joomla user ID
	* @var int
	*/
	private $uploadedBy;

	/**
	* Date/time when the route was uploaded - UNIX epoch
	* @var int
	*/
	private $uploadedDateTime;
	
	private $visibility;
	
	private $type;

	// For iterator use
	private $pointer;

	function __construct(Walkable &$w=null)
	{
		if (isset($w))
			$this->walk =& $w;
		$this->pointer = 0;
	}
	
	function __set($name, $value)
	{
		if ($name == "visibility")
		{
			if (in_array($value, array(self::Visibility_Leaders, self::Visibility_Map, self::Visibility_Members, self::Visibility_None)))
			{
				$this->visibility = $value;
			}
		}
		else if ($name == "uploadedBy" || $name == "uploadedDateTime")
		{
			$this->$name = (int)$value;
		}
		else if ($name == "type" && ($value == self::Type_Logged || $value == self::Type_Planned))
		{
			$this->type = $value;
		}
	}
	
	function __get($name)
	{
		if (in_array($name, array("visibility","id")))
		{
			return $this->$name;
		}
	}
	
	function setWalk(Walkable &$w)
	{
		$this->walk =& $w;
	}

	/**
	* Get a waypoint from this route
	* @param int $index Waypoint index
	*/
	public function getWaypoint($index)
	{
		return $this->wayPoints[$index];
	}

	/**
	* Get the number of waypoints in the route
	*/
	public function numWaypoints()
	{
		return count($this->wayPoints);
	}

	/**
	* Replace a waypoint in the route
	* @param int $index Index to replace (will add a new waypoint at the end
	* @param Waypoint $wp Waypoint to set
	* TODO: Need to recalculate route
	*/
	public function setWaypoint($index, Waypoint $wp)
	{
		if (is_int($index))
		{
			$this->wayPoints[$index] = $wp;
		}
	}

	/**
	* Gets the walk distance in metres
	* @return int
	*/
	public function getDistance()
	{
		return $this->distance;
	}

	/**
	* Gets all waypoints as a single array
	* Copied - safe to modify
	*/
	public function getAllWaypoints()
	{
		return $this->wayPoints;
	}

	/**
	* Set a new route from a GPX XML document (pre-loaded with the DOM library)
	* 
	* This assumes that we want to load all route or track data in the file.
	* If the file contains at least one route, it will load all routes.
	* If not, it will load all tracks.
	* 
	* @param DOMDocument $data
	*/
	public function readGPX(DOMDocument $data)
	{
		$this->wayPoints = array();
		
		// TODO: Error if we can't load this
		include_once(JPATH_BASE."/swg/lib/phpcoord/phpcoord-2.3.php");
		
		$isTrack = false;
		$routes = $data->getElementsByTagName("rte");
		if ($routes->length == 0)
		{
			$routes = $data->getElementsByTagName("trk");
			$isTrack = true;
		}
		$this->distance = 0;
		$this->ascent = 0;
		
		foreach ($routes as $route)
		{
			$this->readGPXSection($route);
		}
	}
	
	/**
	 * Read a single section (route or track) of a GPX file and add it to the total route
	 * The sections are merged into one, with a straight line between the end of one and the start of the next.
	 *
	 * @param DOMElement $route Route or track root element
	 */
	private function readGPXSection(DOMElement $route)
	{
		if ($route->tagName == "trk")
			$routePoints = $route->getElementsByTagName("trkpt");
		else
			$routePoints = $route->getElementsByTagName("rtept"); 
	
		/* Now we start to iterate through all the waypoints.
		* For each point, we want the distance from the last point (added to cumulative
			* distance) and the height *increase* since the last point.
		* We assume that the points are close enough together that we can treat the surface
		* of the Earth as a plane and use Pythagoras' theorem to calculate distances.
		* To start with, we make the start point the previous point.
		*/
		$numPoints = $routePoints->length;
		$lastKnownHeight = null; // Last known height - used if some nodes are missing height data
		$prevPoint = null;
		$iWrite = count($this->wayPoints);
		for ($iRead=0; $iRead<$numPoints; $iRead++)
		{
			// Get the position of this waypoint
			$next = $routePoints->item($iRead);
			$nextPoint = new Waypoint();
			$nextPoint->latLng = new LatLng($next->attributes->getNamedItem("lat")->nodeValue, $next->attributes->getNamedItem("lon")->nodeValue);
			
			// Now see if we can get the time, speed & altitude
			if ($next->hasChildNodes())
			{
				foreach ($next->childNodes as $childNode)
				{
					if ($childNode->nodeType == XML_ELEMENT_NODE)
					{
						switch($childNode->tagName)
						{
							case "ele":
							$nextPoint->alt = (int)$childNode->nodeValue;
							break;
							case "time":
							$nextPoint->time = $childNode->nodeValue;
							break;
						}
					}
				}
			}
			
			$this->wayPoints[$iWrite] = $nextPoint;
			
			// If we're on anything but the first waypoint, get the previous one for some calculations
			if ($iWrite >= 1)
			{
				$prevPoint = $this->wayPoints[$iWrite-1];
				
				// Calculate distance & total ascent
				$this->distance += $nextPoint->distanceTo($prevPoint);
				if (isset($nextPoint->alt) && isset($lastKnownHeight))
					$this->ascent += max($nextPoint->alt - $lastKnownHeight, 0);
			}
			
			// Store the height for future rounds - if another waypoint is missing height info
			// we look back to the last time we had it
			if (isset($nextPoint->alt))
				$lastKnownHeight = $nextPoint->alt;
			
			$iWrite++;
		}
	}

	/**
	 * Output the route as a GPX XML document
	 *
	 * @return DOMDocument GPX data as a DOMDocument. Call saveXML() to get an XML string.
	 */
	public function writeGPX()
	{
		$doc = new DomDocument();
		$gpx = $doc->createElement("gpx");
		$gpx->appendChild(new DomAttr("xmlns","http://www.topografix.com/GPX/1/1"));
		$gpx->appendChild(new DomAttr("creator","www.sheffieldwalkinggroup.org.uk"));
		$gpx->appendChild(new DomAttr("version","1.1"));
		$gpx->appendChild(new DomAttr("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance"));
		$gpx->appendChild(new DomAttr("xsi:schemaLocation","http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd"));
		$doc->appendChild($gpx);
		
		$rte = $doc->createElement("rte");
		$rteName = $doc->createElement("name");
		$rteName->appendChild(new DOMText($this->walk->name));
		$rte->appendChild($rteName);
		$rteNum = $doc->createElement("number");
		$rteNum->appendChild(new DOMText(1));
		$rte->appendChild($rteNum);
		
		foreach ($this->wayPoints as $id=>$wp)
		{
			// We always have the lat & long for each waypoint
			$rtept = $doc->createElement("rtept");
			$rtept->appendChild(new DomAttr("lat",$wp->latLng->lat));
			$rtept->appendChild(new DomAttr("lon",$wp->latLng->lng));
			
			// Now add extra things if we have them
			if (!empty($wp->alt))
			{
				$alt = $doc->createElement("ele");
				$alt->appendChild(new DOMText($wp->alt));
				$rtept->appendChild($alt);
			}
			
			if (!empty($wp->time))
			{
				$time = $doc->createElement("time");
				$time->appendChild(new DOMText(strftime("%Y-%m-%dT%TZ", $wp->time)));
				$rtept->appendChild($time);
			}
			
			$rte->appendChild($rtept);
		}
		
		$gpx->appendChild($rte);
		return $doc;
	}

	/**
	* Save this route to the database.
	* @throws BadMethodCallException If the route has no Walk or WalkInstance set
	*/
	public function save()
	{
		if (!isset($this->walk))
			throw new BadMethodCallException("Cannot save route without setting the walk first");
		
		$db =& JFactory::getDBO();
		// Commit the whole route as one transaction
		$db->transactionStart();
		
		// First, commit the route's general data
		$query = $db->getQuery(true);
		if (isset($this->uploadedBy))
			$query->set("uploadedby = ".$this->uploadedBy);
		if (isset($this->uploadedDateTime))
			$query->set("uploadeddatetime = '".$query->escape(strftime("%Y-%m-%d %T", $this->uploadedDateTime))."'");
		$query->set("length = ".$this->distance);
		$query->set("ascent = ".$this->ascent);
		if (isset($this->visibility))
			$query->set("visibility = ".$this->visibility);
		if (isset($this->type))
			$query->set("type = ".$this->type);
		
		// Connect to a walk or a walkinstance
		if ($this->walk instanceof Walk)
			$query->set("walkid = ".$this->walk->id);
		else if ($this->walk instanceof WalkInstance)
			$query->set("walkinstanceid = ".$this->walk->id);
		
		// Are we inserting or updating?
		if (isset($this->id))
		{
			$query->where("routeid = ".(int)$this->id);
			$query->update("routes");
		}
		else
            $query->insert("routes");
		
		$db->setQuery($query);
		$db->query();
		
		// TODO: Handle failure
		
		if (!isset($this->id))
		{
			// Get the route ID from the database
			$this->id = $db->insertid();
		}
		
		// Delete any existing waypoints for this route
		$query = $db->getQuery(true);
		$query->delete("routepoints")->where("routeid = ".$this->id);
		$db->setQuery($query);
		$db->query();
		
		// Now commit the waypoints
		$i=0;
		while ($this->numWaypoints() > $i)
		{
			$wp = $this->wayPoints[$i];
			
			$query = $db->getQuery(true);
			$query->insert("routepoints");
			$query->set("routeid = ".(int)$this->id);
			$query->set("sequenceid = ".$i);
			
			$query->set("latitude = ".(float)$wp->latLng->lat);
			$query->set("longitude = ".(float)$wp->latLng->lng);
			
			$query->set("easting = ".(int)$wp->osRef->easting);
			$query->set("northing = ".(int)$wp->osRef->northing);
			
			$query->set("altitude = ".(int)$wp->alt);
			$query->set("datetime = ".(int)$wp->time);
			// TODO: Speed?
			
			$db->setQuery($query);
			$db->query();
			// TODO: Gracefully handle errors - can we just leave out one waypoint?

			if ($db->getErrorMsg() != "")
				echo $db->getErrorMsg()."<br />";
			
			$i++;
		}
		
		// Commit the transaction TODO - if it's OK
		$db->transactionCommit();
	}
	
	/**
	 * Checks if the route given is valid for a particular WalkInstance
	 * * The date must be the same
	 * * The start place must be within 1km of the planned start
	 * * The end place must be within 1km of the planned end
	 * * The length must be between 0.5 and 2 times the planned length
	 * (there is no check of start *time*)
	 * @param WalkInstance $w Walk to check against
	 * @return bool True if route matches walk
	 */
	public function checkAgainstWalk(WalkInstance $w, &$reason = "", &$reasonDetail = "")
	{
		$start = $this->getWaypoint(0);
		$end = $this->getWaypoint($this->numWaypoints()-1);
		
		if (strftime("%F", $start->time) != strftime("%F", $w->start))
		{
			$reason = "Route was recorded on a different day from the walk";
			$reasonDetail = "Timestamp of route is ".strftime("%d-%m-%Y", $start->time).", walk was on ".strftime("%d-%m-%Y", $w->start);
			return false;
		}
		
		$plannedDistance = UnitConvert::distance($w->miles, UnitConvert::Mile, UnitConvert::Metre);
		if ($this->distance < $plannedDistance * 0.5 || $this->distance > $plannedDistance * 2)
		{
			$reason = "Route distance doesn't match the walk";
			$reasonDetail = "Route distance is ".$this->distance."m, planned walk distance was ".$plannedDistance."m";
			return false;
		}
		
		$plannedStart = new Waypoint();
		$plannedStart->latLng = $w->startLatLng;
		if ($start->distanceTo($plannedStart) > 1000)
		{
			$reason = "Start location doesn't match planned walk";
			$reasonDetail = "Start location of recorded track is ".$start->osRef->toSixFigureString().", should be ".$w->startGridRef;
			return false;
		}
		
		$plannedEnd = new Waypoint();
		$plannedEnd->latLng = $w->endLatLng;
		if ($end->distanceTo($plannedEnd) > 1000)
		{
			$reason = "End location doesn't match planned walk";
			$reasonDetail = "End location of recorded track is ".$end->osRef->toSixFigureString().", should be ".$w->endGridRef;
			return false;
		}
		return true;
	}
	
	/**
	 * Finds a walk that matches this one
	 * Loads all walks on the same date as the track, then checks them with checkAgainstWalk.
	 * Returns the first matching walk.
	 * @return WalkInstance Matching walk, or null if no match
	 */
	public function findMatchingWalk()
	{
		$wiFactory = SWG::walkInstanceFactory();
		$wiFactory->reset();
		$wiFactory->startDate = $this->getWaypoint(0)->time;
		$wiFactory->endDate = $this->getWaypoint(0)->time;
		$walks = $wiFactory->get();
		foreach ($walks as $walk)
		{
			if ($this->checkAgainstWalk($walk))
				return $walk;
		}
		return null;
		
	}

	/**
	* Loads a route from the database from its ID
	* @param int $id Route ID to load
	* @param Walkable $w Walk to attach this route to, if the object already exists. A new one will be created if not.
	* @throws InvalidArgumentException If walkable passed in does not match walkable set in database
	*/
	public static function loadSingle($id, Walkable $w=null)
	{
		$id = (int)$id;
		$db =& JFactory::getDBO();
		
		// First, get the route's general data
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("routes");
		$query->where(array("routeid = ".$id));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 0)
			return null;
		$dbArr = $db->loadAssoc();
		
		$wiFactory = SWG::walkInstanceFactory();
		
		// If we've been given a Walkable, make sure it matches the one on the route
		// It's OK to load a route for a WalkInstance on a Walk, and vice-versa
		if ($w != null)
		{
			if ($w instanceof Walk)
			{
				if ($dbArr['walkid'] != $w->id)
				{
					// Try to load a matching walkInstance
					if (!empty($dbArr['walkinstanceid']))
					{
						$wi = $wiFactory->getSingle($dbArr['walkinstanceid']);
						if ($wi->walkid != $w->id)
						{
							throw new InvalidArgumentException("Loaded route is for WalkInstance ".$wi->id.", Walk ".$wi->walkid." (does not match Walk ".$w->id.")");
						}
					}
					else
						throw new InvalidArgumentException("Loaded route is for Walk ".$dbArr['walkid']." (does not match Walk ".$w->id.")");
				}
			}
			else
			{
				if ($dbArr['walkinstanceid'] != $w->id && $dbArr['walkid'] != $w->walkid)
				{
					throw new InvalidArgumentException("Loaded route is not for given WalkInstance");
				}
			}
		}
		else
		{
		// Load the Walkable
		if (empty($dbArr['walkinstanceid']))
		{
			// A Walk
			$w = Walk::getSingle($dbArr['walkid']);
		}
		else
		{
			// A WalkInstance
			$w = $wiFactory->getSingle($dbArr['walkinstanceid']);
		}
		}
		
		// Create the route object
		$rt = new Route($w);
		
		// TODO: uploadedby/time, length, ascent
		// Load the basic route properties
		$rt->id = $id;
		
		$rt->distance = $dbArr['length'];
		$rt->ascent = $dbArr['ascent'];
		$rt->uploadedBy = $dbArr['uploadedby']; // TODO: Load the actual user? Also, uploadedby should be a Joomla user, not a Leader
		$rt->uploadedDateTime = strtotime($dbArr['uploadeddatetime']);
		$rt->visibility = (int)$dbArr['visibility'];
		$rt->type = (int)$dbArr['type'];
		
		// Set all the waypoints
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from("routepoints");
		$query->where(array("routeid = ".$id));
		$query->order("sequenceid ASC");
		$db->setQuery($query);
		$res = $db->query();
			
		// Joomla can't be arsed to support/provide documentation for fetching individual rows like EVERYTHING ELSE DOES
		$megaArray = $db->loadAssocList("sequenceid");
		
		foreach ($megaArray as $i=>$dbArr)
		{
			$wp = new Waypoint();
			$wp->osRef = new OSRef($dbArr['easting'], $dbArr['northing']);
			$wp->altitude = $dbArr['altitude'];
			$wp->time = $dbArr['datetime'];
			$rt->setWaypoint($i, $wp);
		}
		
		return $rt;
	}

	/**
	* Loads routes matching a Walk or WalkInstance.
	* 
	* Only one Type_Planned route will be returned. If possible, this will be for the Walkable passed in.
	* Otherwise, a route planned for the base Walk or the most recently-uploaded route for any instance is used.
	* All Type_Logged routes for any matching Walkable will be returned.
	*
	* @param Walkable $w Walk or WalkInstance to find routes for
	* @param boolean $allowRelated If true, routes for related instances and the walk itself will be found - if $w is a Walk, that Walk and all instances are returned. If $w is a WalkInstance that instance and the parent Walk are returned.
	* @param int $type Type of route to fetch - see Route::Type_Planned and Route::Type_Logged
	* @param int $limit Limit the number of routes to find. Default is no limit (0)
	* @return Array Array of routes for this Walk or WalkInstance
	*/
	public static function loadForWalkable(Walkable $w, $allowRelated=false, $type=null, $limit=0)
	{
        if (!$w->id) {
            return array();
        }
		$db =& JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("routeid, type");
		$query->from("routes");
		
		// What walks/instances are allowed?
		if ($w instanceof Walk)
		{
			$qryWalkID = array("walkid = ".$w->id);
			if ($allowRelated)
			{
				foreach ($w->getInstances() as $wi)
				{
					$qryWalkID[] = "walkinstanceid = ".$wi->id;
				}
			}
			$query->where("(".implode(" OR ", $qryWalkID).")");
			$query->order("walkid IS NOT NULL"); // Put the Walk first
		}
		else
		{
			$qryWalkInstance = array("walkinstanceid = ".$w->id);
			if ($allowRelated)
			{
				$qryWalkInstance[] = "walkid = ".$w->walkid;
			}
			$query->where("(".implode(" OR ", $qryWalkInstance).")");
			$query->order("walkinstanceid IS NOT NULL"); // Put the WalkInstance first
		}
		
		if (isset($type))
		{
			$query->where("type = ".(int)$type);
		}
		
		$query->order("uploadeddatetime DESC"); // Most recent first
		
		if (!empty($limit))
			$query->setLimit($limit,0);
		
		$db->setQuery($query);
		$db->query();
		
		$routes = array();
		$dbArr = $db->loadAssocList("routeid");
		$foundPlanned = false;
		if (!empty($dbArr))
		{
			foreach ($dbArr as $row)
			{
				// Only want the first planned route that comes out
				if ($row['type'] == 10)
				{
					if ($foundPlanned)
						continue;
					
					$foundPlanned = true;
				}
				
				$routes[] = self::loadSingle($row['routeid'], $w);
			}
		}
		
		return $routes;
	}

	// Iterator functions
	function rewind() {
		$this->pointer = 0;
	}

	function current() {
		return $this->wayPoints[$this->pointer];
	}

	function key() {
		return $this->pointer;
	}

	function next() {
		$this->pointer++;
	}

	function valid() {
		return $this->pointer < count($this->wayPoints);
	} 

	/**
	* When encoding a route, we want basic details at the top, then an array of waypoints
	* @see SWGBaseModel::sharedProperties()
	*/
	public function sharedProperties()
	{
		$properties = array(
			"uploadedby"       => $this->uploadedBy,
			"uploadeddatetime" => $this->uploadedDateTime,
			"distance"         => $this->distance,
			"ascent"           => $this->ascent,
			"waypoints"        => array(),
			"visibility"	   => $this->visibility,
			"type"			   => $this->type,
		);
		
		// Iterate through waypoints
		foreach ($this->wayPoints as $id=>$wp)
		{
			$properties['waypoints'][$id] = $wp->sharedProperties();
		}
		return $properties;
	}
}
