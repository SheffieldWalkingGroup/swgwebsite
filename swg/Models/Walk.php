<?php
defined('_JEXEC') or die('Restricted access');
require_once("SWGBaseModel.php");
require_once("Leader.php");

/**
 * A walk in our library.
 * @see WalkInstance for an instance of a walk, with a date and a leader etc.
 * @author peter
 *
 */
class Walk extends SWGBaseModel {
  private $id;
  private $name;
  private $distanceGrade;
  private $difficultyGrade;
  private $miles;
  private $location;
  private $isLinear;
  private $startGridRef;
  private $startPlaceName;
  private $endGridRef;
  private $endPlaceName;
  private $routeDescription;
  private $fileLinks;
  private $information;
  private $routeImage;
  private $suggestedBy;
  private $status;
  private $specialTBC;
  private $dogFriendly;
  private $transportByCar;
  private $transportPublic;
  private $childFriendly;
  
  /**
   * GPX route for this walk. 
   * This is stored as XML parsed by PHP's DOM parser
   * It must be a single route (not a track), using WGS84 datum.
   * This should be checked by any pre-parsing functions.
   * @var DOMDocument
   */
  private $route;
  
  /**
   * Constructs a walk object from an array of database fields
   * @param array $dbArr Associative array from the walks table
   */
  public function __construct($dbArr = null)
  {
    if (empty($dbArr))
      return;
    
    $this->id = $dbArr['ID'];
    $this->name = $dbArr['walkname'];
    $this->distanceGrade = $dbArr['distancegrade'];
    $this->difficultyGrade = $dbArr['difficultygrade'];
    $this->miles = $dbArr['miles'];
    $this->location = $dbArr['location'];
    $this->isLinear = $dbArr['islinear'];
    $this->startGridRef = $dbArr['startgridref'];
    $this->startPlaceName = $dbArr['startplacename'];
    $this->endGridRef = $dbArr['endgridref'];
    $this->endPlaceName = $dbArr['endplacename'];
    $this->description = $dbArr['routedescription'];
    $this->fileLinks = $dbArr['filelinks'];
    $this->information = $dbArr['information'];
    $this->routeImage = $dbArr['routeimage'];
    $this->suggestedBy = Leader::getLeader($dbArr['suggestedby']);
    $this->status = $dbArr['status'];
    $this->specialTBC = $dbArr['special_tbc'];
    $this->childFriendly = $dbArr['childfriendly'];
    $this->dogFriendly = $dbArr['dogfriendly'];
    //     $this->transportByCar = $dbArr['transport'];
    //     $this->transportPublic = $dbArr[''];
    
  }
  
  public function __get($name)
  {
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
      case "routeDescription":
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
        else
          throw new UnexpectedValueException("Distance grade must be A, B or C");
        break;
      case "difficultyGrade":
        $value = (int)$value;
        if ($value == 1 || $value == 2 || $value == 3)
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
        $value = str_replace(" ","",$value);
        if (preg_match("/[A-Z][A-Z]([0-9][0-9]){3,}/", $value))
          $this->$name = $value;
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
    }
  }
  
  /**
   * Import a route data file
   * 
   * TODO: Needs to handle imperfect files
   * @param DOMDocument $data
   * @param unknown_type $parse
   */
  public function loadRoute(DOMDocument $data, $parse = true)
  {
    $this->route = $data;
    if ($parse)
      $this->parseRoute();
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
   * Parse the current route and writes data to the Walk's properties
   * We assume that the route data contains one route,
   * so we just take the first route element that occurs in the data.
   * 
   * The route may be able to give us:
   * * Distance
   *     - Calculated
   * * Location
   *     - Which region are most of the points in?
   * * Linearity
   *     - Is the end within 500m of the start?
   * * Start grid ref
   *     - Convert to OSGB36
   * * TODO: Start place name
   *     - May be stored as a waypoint in the route
   *     - List of known start points
   *     - Reverse geocoding
   * * End grid ref
   * * TODO: End place name
   * If any of these aren't available (i.e. start/end place names),
   * they are left unchanged. All others are always overwritten.
   */
  public function parseRoute()
  {
    // TODO: Error if we can't load this
    include_once(JPATH_ROOT."/swg/lib/phpcoord/phpcoord-2.3.php");
    
    $route = $this->route->getElementsByTagName("rte")->item(0);
    /**
     * Cumulative distance in metres
     * @var int
     */
    $distance = 0;
    /**
     * Cumulative ascent in metres. May be useful for estimating difficulty.
     * @var int
     */
    $ascent = 0;
    $routePoints = $route->getElementsByTagName("rtept");
    
    // Read the first point
    $firstPoint = $routePoints->item(0);
    $start = new LatLng($firstPoint->attributes->getNamedItem("lat")->nodeValue, $firstPoint->attributes->getNamedItem("lon")->nodeValue);
    $start->WGS84ToOSGB36();
    $this->startGridRef = $start->toOSRef()->toSixFigureString();
    $startPlace = $this->reverseGeocode($start->toOSRef());
    if ($startPlace)
      $this->startPlaceName = $startPlace;

    /* Now we start to iterate through all the waypoints. We start at the second point.
     * For each point, we want the distance from the last point (added to cumulative
     * distance) and the height *increase* since the last point.
     * We assume that the points are close enough together that we can treat the surface
     * of the Earth as a plane and use Pythagoras' theorem to calculate distances.
     * To start with, we make the start point the previous point.
     */
    $numPoints = $routePoints->length;
    $prev = $routePoints->item(0); // Previous node as XML
    $prevPoint = $start->toOSRef(); // Previous node as an OS point, OSGB36
    for ($i=1; $i<$numPoints; $i++)
    {
      // TODO: Take a sample of ~10 waypoints to determine what region the walk is in
      
      // Get the next point and convert it to an OS reference
      $next = $routePoints->item($i);
      $nextPoint = new LatLng($next->attributes->getNamedItem("lat")->nodeValue, $next->attributes->getNamedItem("lon")->nodeValue);
      $nextPoint->WGS84ToOSGB36();
      $nextPoint = $nextPoint->toOSRef();
      
      // Calculate distances
      $deltaEasting = $nextPoint->easting - $prevPoint->easting;
      $deltaNorthing = $nextPoint->northing - $prevPoint->northing;
      if ($next->getElementsByTagName("elev")->length > 0 && $prev->getElementsByTagName("elev")->length > 0)
        $deltaHeight = $next->getElementsByTagName("elev")->item(0)->nodeValue - $prev->getElementsByTagName("elev")->item(0)->nodeValue;
      else
        $deltaHeight = 0;
      
      // Add on to totals
      $deltaDistance = sqrt(pow($deltaEasting, 2) + pow($deltaNorthing,2));
      $distance += $deltaDistance;
      $ascent += max($deltaHeight, 0); // Only add height to the total ascent if we've gone up between these waypoints
      
      // Make the current waypoint the previous one
      $prev = $next;
      $prevPoint = $nextPoint;
    }
    
    // Now get the last point
    $lastPoint = $routePoints->item($routePoints->length-1);
    $end = new LatLng($lastPoint->attributes->getNamedItem("lat")->nodeValue, $lastPoint->attributes->getNamedItem("lon")->nodeValue);
    $end->WGS84ToOSGB36();
    $this->endGridRef = $end->toOSRef()->toSixFigureString();
    $endPlace = $this->reverseGeocode($end->toOSRef());
    if ($endPlace)
      $this->endPlaceName = $endPlace;
    
    // Is this a linear walk?
    $deltaEasting = $end->toOSRef()->easting - $start->toOSRef()->easting;
    $deltaNorthing = $end->toOSRef()->northing - $start->toOSRef()->northing;
    $deltaDistance = sqrt(pow($deltaEasting, 2) + pow($deltaNorthing,2));
    $this->isLinear = ($deltaDistance > 500); // TODO: Magic number
    
    // Convert distance to miles, get the distance grade
    // Resolution is half a mile
    // TODO: Magic numbers
    $this->miles = round($distance*0.000621371192*2)/2;
    $this->distanceGrade = $this->getDistanceGrade($this->miles);
  }
  
  /**
   * Attempts to get a place name by reverse geocoding.
   * TODO: Look in our own database for common points
   * If we don't have a reference for this point in our own database,
   * we use OpenStreetMap's Nominatim API (CC-BY-SA)
   * See http://wiki.openstreetmap.org/wiki/Nominatim#Reverse_Geocoding_.2F_Address_lookup
   * We only use the returned value if it's one of the following, in this order (first is kept):
   * * information
   * * parking
   * * building
   * * townhall
   * If none of these match, the following combinations are also valid:
   * * place_of_worship, suburb
   * * bus_stop, suburb
   * * pub, suburb
   * (Note: Suburb is usually the village name, e.g. Tideswell CP)
   * TODO: Remove "CP" and similar
   * TODO: Maybe display location type, e.g. pub, car park...
   * @param $point LatLng|OSRef Point to look up. LatLng is assumed to be in WGS84, OSRef in OSGB36
   */
  public function reverseGeocode($point)
  {
    $validLocationTypes = array(
       "information","parking","building",
       "townhall",
    );
    
    $backupLocations = array(
        "place_of_worship",
        "bus_stop",
        "pub",
    );
    $return = false; // We return false if no suitable place found
    
    if ($point instanceof OSRef)
    {
      $point = $point->toLatLng();
      $point->OSGB36ToWGS84();
    }
    // TODO: Reject point if it isn't now a LatLng
    
    // TODO: Our own database
    
    // Connect to Nominatim with CURL, get results in XML format
    $options = array(
        'format=xml',
        "lat=".$point->lat,
        "lon=".$point->lng,
        'addressdetails=1',
        );
    
    $curl = curl_init("http://nominatim.openstreetmap.org/reverse?".implode("&", $options));
    curl_setopt($curl,CURLOPT_USERAGENT, "Sheffield Walking Group Leaders' area - admin contact tech@sheffieldwalkinggroup.org.uk");
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
    
    $res = curl_exec($curl);
    if ($res)
    {
      // Use the DOM parser
      $result = DomDocument::loadXML($res);
      $address = $result->getElementsByTagName("addressparts")->item(0)->childNodes;
      
      // Get a suitable place name
      $possibleLocation = ""; // Store second-class location data here until we find something better 
      foreach($address as $addressPart)
      {
        if (in_array($addressPart->nodeName, $validLocationTypes))
        {
          $return = $addressPart->nodeValue;
          break;
        }
        
        if (empty($possibleLocation) && in_array($addressPart->nodeName, $backupLocations))
        {
          $possibleLocation = $addressPart->nodeValue;
          
          if ($addressPart->nodeName != "suburb")
          {
            $suburbs = $result->getElementsByTagName("addressparts")->item(0)->getElementsByTagName("suburb");
            if (!empty($suburbs) && !empty($suburbs->item(0)->nodeValue))
            {
              $suburb = $suburbs->item(0)->nodeValue;
              // Strip out "CP" if present
              $suburb = trim(str_replace("CP", "", $suburb));
              $possibleLocation.= ", ".$suburb;
            }
          }
        }
      }
      
      if (!empty($possibleLocation))
        return $possibleLocation;
    }
    else
    {
      var_dump(curl_error($curl));
    }
    
    return $return;
  }

  /**
   * Gets walks suggested by a specified person 
   * @param Leader $suggester
   */
  public static function getWalksBySuggester(Leader $suggester)
  {
    $db = JFactory::getDBO();
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
      $walk = new Walk(array_shift($walkData));
      $walks[] = $walk;
    }
    
    return $walks;
  }
}
