<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * A detailed view of a walk.
 * 
 * This can be used from the walk library, or from an event list.
 * Because most (all?) fields can be changed on a WalkInstance,
 * this can take either a Walk or a WalkInstance
 */
class SWG_EventsViewWalkDetails extends JView
{
  function display($tpl = null)
  {
    $app		= JFactory::getApplication();
	$params		= $app->getParams();
	$dispatcher = JDispatcher::getInstance();
    $model	    = $this->getModel('walkdetails');

	// Get some data from the models
	$state		= $this->get('State');
	$this->walk	= $this->get('Walk');
	$this->walkInstances = $this->walk->getInstances();

	// Check for errors.
	if (count($errors = $this->get('Errors'))) 
	{
		JError::raiseError(500, implode('<br />', $errors));
		return false;
	}
	
	// Load the map JS
	$document = JFactory::getDocument();
	$document->addScript('http://openlayers.org/api/OpenLayers.js');
	$start = new Waypoint();
	$start->osRef = getOSRefFromSixFigureReference($this->walk->startGridRef);
	$end = new Waypoint();
	$end->osRef = getOSRefFromSixFigureReference($this->walk->endGridRef);
	
	// JSON-encode the route (if available)
	// TODO: Change to hasRoute() or something
	$r = $this->walk->route;
	if (isset($r))
	  $route = $this->walk->route->jsonEncode();
	else
	  $route = "''";
		
	$document->addScriptDeclaration(<<<MAP
window.addEvent("domready", function() {
  var map = new OpenLayers.Map("map");
  var mapData = new OpenLayers.Layer.OSM(
    "OpenCycleMap",
    ["http://a.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png",
       "http://b.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png",
       "http://c.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png"],
	{sphericalMercator:true}
  );
  map.addLayer(mapData);
  var route = {$route};

  // Add markers for start and end points
  // Note: need to transform from WGS1984 to (usually) Spherical Mercator projection
  var start = new OpenLayers.LonLat({$start->latLng->lng},{$start->latLng->lat}).transform(
    new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()
  );
  var end = new OpenLayers.LonLat({$end->latLng->lng},{$end->latLng->lat}).transform(
    new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()
  );
  
  var startPopup = new OpenLayers.Popup.FramedCloud("StartPopup",
    start, null,
    "Start: {$this->walk->startGridRef}<br>{$this->walk->startPlaceName}", null, true
  );
  
  var markers = new OpenLayers.Layer.Markers("Start/end locations");
  map.addLayer(markers);
  markers.addMarker(new OpenLayers.Marker(start));
  markers.addMarker(new OpenLayers.Marker(end));
  
  //map.addPopup(startPopup);
  
  // Add route layer
  if (route != '')
  {
    var rtLayer = new OpenLayers.Layer.Vector("Route");
    
    var points = new Array(); 
    for (pt in route)
    {
      if (route[pt].hasOwnProperty('lng'))
      {
        var point = new OpenLayers.Geometry.Point(route[pt].lng, route[pt].lat).transform(
          new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()
        );
        points.push(point);
      }
    }
    var rt = new OpenLayers.Geometry.LineString(points);
    var rtFeature = new OpenLayers.Feature.Vector(
      rt, null, {
        strokeColor:"#FF9555",
        strokeOpacity:1,
        strokeWidth:4,
        pointRadius:3,
        pointerEvents:"visiblePainted"
      }
    );
    
    map.addLayer(rtLayer);
    rtLayer.addFeatures([rtFeature]);
    
    
  }
  map.setCenter(start,16);
  
  map.addControl(new OpenLayers.Control.LayerSwitcher());
});
MAP
);
	
	// Display the view
	parent::display($tpl);
  }
}