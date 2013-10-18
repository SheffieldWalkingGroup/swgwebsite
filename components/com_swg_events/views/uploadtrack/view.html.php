<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * Upload a GPX track of a WalkInstance
 */
class SWG_EventsViewUploadTrack extends JView
{
  function display($tpl = null)
  {
    $app		= JFactory::getApplication();
	$params		= $app->getParams();
	$dispatcher = JDispatcher::getInstance();
    $model	    = $this->getModel('uploadtrack');

	// Get some data from the models
	$state		= $this->get('State');
	$this->wi	= $this->get('WalkInstance');
	$this->form	= $this->get('Form');
	
	// Has the user uploaded a track to preview?
	$this->track = $this->get('CachedTrack');
	$this->gotTrack = !empty($this->track);
	
	$document = JFactory::getDocument();
	JHtml::_('behavior.framework', true);
	$document->addScript('swg/js/common.js');
	
	// Prepare any error messages
	// TODO: Find how Joomla handles error messages (and make them look nicer) - maybe just throw exceptions out and have an exception handler that displays a popup
	$errors = $this->getErrors();
var_dump($errors);
	$errJS = "";
	if (!empty($errors))
	{
		$errJS = "Popup('Could not upload track', ".implode("<br />", $errors).");";
		$document->addScriptDeclaration(<<<ERR
window.addEvent("domready", function() {
	$errJS
});
ERR
);
	}
	
	// Do we know what walk we're working on?
	if (isset($this->wi))
	{
		if ($this->gotTrack)
		{
			$this->track->setWalk($this->wi);
			$this->wi->setTrack($this->track);
		}
		
		// Load the map JS
		$document->addScript('libraries/openlayers/OpenLayers.js');
		$document->addScript('swg/js/maps.js');
		$start = new Waypoint();
		$start->osRef = getOSRefFromSixFigureReference($this->wi->startGridRef);
		$end = new Waypoint();
		$end->osRef = getOSRefFromSixFigureReference($this->wi->endGridRef);
		
		// Create the map	
		if ($this->gotTrack)
		{
			$trackJSON = $this->track->jsonEncode();
			$trackJS = "var route = new Route(wi);\nroute.read(".$this->track->jsonEncode().");\nmap.loadedRoute(route,wi);\n";
		}
		else
		{
			$trackJS = "";
		}
		
		
	
	
		$document->addScriptDeclaration(<<<MAP
window.addEvent("domready", function() {
    var map = new SWGMap('map');
	var wi = map.addWalkInstance({$this->wi->id});
	map.addLoadedHandler(function(){
		{$trackJS}
		map.zoomToFit();
	});
});
MAP
);
	}
	
	// Display the view
	parent::display($tpl);
  }
}