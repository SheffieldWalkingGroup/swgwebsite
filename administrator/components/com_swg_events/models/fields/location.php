<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

class JFormFieldLocation extends JFormField
{
	protected $type = "Location";
	
// 	protected $forceMultiple = true;
	
	public function getInput()
	{
		// What is the starting location?
		$jsGotLocation = "false";
		if (
			isset($this->value) && 
			isset($this->value['lat']) && is_numeric($this->value['lat']) &&
			isset($this->value['lng']) && is_numeric($this->value['lng'])
		)
		{
			$start = new LatLng($this->value['lat'], $this->value['lng']);
			$jsGotLocation = "true";
		}
		else if (
			isset($this->element['lat']) && is_numeric($this->element['lat']) &&
			isset($this->element['lng']) && is_numeric($this->element['lng'])
		)
			$start = new LatLng($this->element['lat'], $this->element['lng']);
		else
		    $start = new LatLng(53.38155556,-1.469722222);
		if (isset($this->element['zoom']) && is_numeric($this->element['zoom']->data()) && (int)$this->element['zoom']->data() > 0)
			$zoom = (int)$this->element['zoom']->data();
		else
		    $zoom = 14;
		
		// Load the maps JS
		$document = JFactory::getDocument();
		JHtml::_('behavior.framework', true);
		$document->addScript('/libraries/openlayers/OpenLayers.js');
		$document->addScript('/swg/js/maps.js');
		$document->addScriptDeclaration(<<<MAP
window.addEvent('domready', function()
{
	var map = new SWGMap("{$this->id}_map");
	map.setDefaultMap("street");
	var start = new OpenLayers.LonLat({$start->lng}, {$start->lat}).transform(new OpenLayers.Projection("EPSG:4326"), map.map.getProjectionObject());
	map.map.setCenter(start,{$zoom});
	
	var loc, marker;
	var markerIcon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
	
	var markerLayer = map.map.getLayersByName("Locations")[0];
	
	// Set up the click handlers
	map.addClickHandler(function(e, location) {
		// Set the location text fields
		document.getElementById("{$this->id}_lat").value = location.lat;
		document.getElementById("{$this->id}_lng").value = location.lon;
		
		// Put the marker here
		loc = new OpenLayers.LonLat(location.lon,location.lat).transform(new OpenLayers.Projection("EPSG:4326"), map.map.getProjectionObject());
		
		marker = new OpenLayers.Marker(loc);
		markerLayer.clearMarkers();
		markerLayer.addMarker(marker);
	});
	
	// Maybe add an intial marker
	if ({$jsGotLocation})
	{
		loc = new OpenLayers.LonLat({$start->lng},{$start->lat}).transform(new OpenLayers.Projection("EPSG:4326"), map.map.getProjectionObject());
		marker = new OpenLayers.Marker(loc);
		markerLayer.clearMarkers();
		markerLayer.addMarker(marker);
	}
});
MAP
);
		$html = "";
		
		// TODO: Allow enabling/disabling of visible fields
		$html .= "<div id='{$this->id}_map' style='width:600px;height:400px;'></div>";
		$html .= "<input type='hidden' name='{$this->name}[lat]' id='{$this->id}_lat' value='{$start->lat}' />";
		$html .= "<input type='hidden' name='{$this->name}[lng]' id='{$this->id}_lng' value='{$start->lng}' />";
		
		return $html;
	}
}