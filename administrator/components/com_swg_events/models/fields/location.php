<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

/**
 * An interactive map the user can click on to set a location.
 * Location is set & read as a pair of hidden fields,
 * but this can optionally also read/write from a location name field and a grid reference.
 *
 * @param label Label
 * @param start Starting position as latitude,longitude. If not set, location will be used (if set)
 * @param locationOverridesStart If true, the first location (if set) will be used as the starting position even if start is set.
 * @param zoom Starting zoom
 * @param location Starting location as latitude,longitude. Additional locations can be added with AddLocation.
 * @param startEndLocations If true, treat the first and last locations as start and end points respectively.
 * @param gridRefField ID of text field to write grid reference to when a location is set. To read a grid reference, TODO: Use event on the map 
 * TODO: Location name read/write
 */
class JFormFieldLocation extends JFormField
{
	protected $type = "Location";
	
	/**
	 * @var LatLng Starting position of map
	 */
	private $start = null;
	
	private $zoom = 14;
	
	/**
	 * @var array Marked locations
	 */
	private $locations = array();
	
	public function __construct()
	{
		parent::__construct();
		
		// Set up our initial location
		if (isset($this->value['start']))
			$this->start = self::parseLatLongTuple($this->value['start']);
		
		if (isset($this->element['zoom']) && is_numeric($this->element['zoom']->data()) && (int)$this->element['zoom']->data() > 0)
			$this->zoom = (int)$this->element['zoom']->data();
		
		if (isset($this->value['location']))
		{
			$this->location[0] = self::parseLatLongTuple($this->value['location']);
			
			// If we have no explicit start, or the location overrides the default start, use this
			if (empty($this->start) || $this->value['locationOverridesStart'])
			{
				$this->start = $this->location[0];
			}
		}
		
		// Final fallback for starting location
		if (empty($this->start))
		{
			$this->start = new LatLng(53.38155556,-1.469722222);
		}
	}
	
	/**
	 * Adds a new location to the set. 
	 * If an index is given, and is lower than the number of locations already on the map,
	 * the set will be reindexed. Otherwise, the location is added at the end.
	 * @param LatLng $latLng Location coordinates. If not set, a blank location is added (this allows the user to add a new location on the map)
	 * @param int $index Index for the new location. If blank, the location is added at the end of the list.
	 */
	public function addLocation(LatLng $latLng=null, $index=null)
	{
		if (!is_numeric($index) || $index > count($this->locations))
			$index = $this->locations;
		
		$this->locations[(int)$index] = $latLng;
	}
	
	/**
	 * Parses a latitude,longitude string into an array
	 * @param string $string String with format latitude,longitude
	 * @return LatLng
	 */
	private static function parseLatLongTuple($string)
	{
		$loc = explode(",", $string);
		if (count($loc) != 2 || !is_numeric($loc[0]) || !is_numeric($loc[1]))
			throw new InvalidArgumentException("Position must be given as <latitude,longitude>.");
			
		return New LatLng((float)$loc[0], (float)$loc[1]);
	}
	
	public function getInput()
	{
$this->addLocation(new LatLng(53.379265, -1.47922));
$jsGridRefFieldIDs = json_encode(array("jform_startGridRef", "jform_endGridRef"));
		// Prepare variables for JS
		$jsStartPos = json_encode($this->start);
		$jsZoom = $this->zoom;
		$jsLocations = json_encode($this->locations);
		
		// Load the maps JS
		$document = JFactory::getDocument();
		JHtml::_('behavior.framework', true);
		$document->addScript('/libraries/openlayers/OpenLayers.js');
		$document->addScript('/swg/js/maps.js');
		
		if (empty($this->element['gridRefField']))
			$jsGotGridRefField = "false";
		else
		{
			$jsGotGridRefField = "true";
			$jsGridRefFieldID = "jform_".$this->element['gridRefField'];
		}
		
		if (empty($this->element['locationNameField']))
			$jsGotLocationNameField = "false";
		else
		{
			$jsGotLocationNameField = "true";
			$jsLocationNameFieldIDs = "jform_".$this->element['locationNameField'];
		}
		
		$document->addScript(JURI::base()."administrator/components/com_swg_events/models/fields/location.js");			
		$document->addScriptDeclaration(<<<MAP
		
window.addEvent('domready', function()
{
	var mapJS = new JFormFieldLocation("{$this->id}", {$jsStartPos}, {$jsZoom}, {$jsLocations}, {$jsGridRefFieldIDs}, "{$jsLocationNameFieldIDs}");
});
MAP
);
		$html = <<<FLD
<div id='{$this->id}_map' style='width:600px;height:400px;'>
	<div id="{$this->id}_search" class="searchpanel">
		<h4>Search</h4>
		<input type="text" class="searchfield" />
		<input type="button" class="submit" value="Search" />
	</div>
</div>
<input type='text' size='80' name='{$this->name}' id='{$this->id}' value='{$jsLocations}' />"
FLD
;
		return $html;
	}
}