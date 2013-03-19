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
 * @param lat Starting latitude
 * @param lng Starting longitude
 * @param zoom Starting zoom
 * @param gridRefField ID of text field to write grid reference to when a location is set. To read a grid reference, TODO: Use event on the map 
 * TODO: Location name read/write
 */
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
			$jsLocationNameFieldID = "jform_".$this->element['locationNameField'];
		}
		
		$document->addScript(JURI::base()."administrator/components/com_swg_events/models/fields/location.js");			
		$document->addScriptDeclaration(<<<MAP
		
window.addEvent('domready', function()
{
	var startPos = {'lat':{$start->lat}, 'lng':{$start->lng},'zoom':{$zoom}};
	var mapJS = new JFormFieldLocation("{$this->id}", startPos, null, "{$jsGridRefFieldID}", "{$jsLocationNameFieldID}");
});
MAP
);
		$html = "";
		
		// TODO: Allow enabling/disabling of visible fields
		$html .= "<div id='{$this->id}_map' style='width:600px;height:400px;'></div>";
		$html .= "<input type='hidden' name='{$this->name}[lat]' id='{$this->id}_lat' ".( !empty($this->value) ? "value='{$start->lat}'" : "" )." />";
		$html .= "<input type='hidden' name='{$this->name}[lng]' id='{$this->id}_lng' ".( !empty($this->value) ? "value='{$start->lng}'" : "" )." />";
		
		return $html;
	}
}