<?php
defined('_JEXEC') or die('Restricted access');
require_once("SWGBaseModel.php");

/**
 * A walk in our library.
 * @see WalkInstance for an instance of a walk, with a date and a leader etc.
 * @author peter
 *
 */
class Walk extends SWGBaseModel {
  private $walkName;
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
  
  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }
  
  /**
   * Import a route data file
   * 
   * TODO: Needs to handle imperfect files
   * @param unknown_type $data
   * @param unknown_type $parse
   */
  public function loadRoute($data, $parse = true)
  {
    $this->route = DOMDocument::loadXML($data);
    if ($parse)
      $this->parseRoute();
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
    include_once("../lib/phpcoord/phpcoord-2.3.php");
    
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
    // TODO: Attempt to get a place name

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
    // TODO: Attempt to get a place name
    
    // Is this a linear walk?
    $deltaEasting = $end->toOSRef()->easting - $start->toOSRef()->easting;
    $deltaNorthing = $end->toOSRef()->northing - $start->toOSRef()->northing;
    $deltaDistance = sqrt(pow($deltaEasting, 2) + pow($deltaNorthing,2));
    $this->isLinear = ($deltaDistance > 500); // TODO: Magic number
    
    // Convert distance to miles, get the distance grade
    // Resolution is half a mile
    // TODO: Magic numbers
    $this->miles = round($distance*0.000621371192*2)/2;
    if ($this->miles <= 8)
      $this->distanceGrade = "A";
    else if ($this->miles <= 12)
      $this->distanceGrade = "B";
    else
      $this->distanceGrade = "C";
  }
}
