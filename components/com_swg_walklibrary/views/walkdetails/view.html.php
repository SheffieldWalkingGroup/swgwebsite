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
class SWG_WalkLibraryViewWalkDetails extends JView
{
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();
		$dispatcher = JDispatcher::getInstance();
		$model	    = $this->getModel('walkdetails');
		$controller = JController::getInstance('SWG_WalkLibrary');

		// Get some data from the models
		$state		= $this->get('State');
		$this->walk	= $this->get('Walk');
		$this->walkInstances = $this->walk->getInstances();
		
		// Get some permissions info
		$this->canAdd = $controller->canAdd();
		$this->canEdit = $controller->canEdit($this->walk);

		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		// Load the map JS
		$document = JFactory::getDocument();
		JHtml::_('behavior.framework', true);
		$document->addScript('/libraries/openlayers/OpenLayers.js');
		$document->addScript('/swg/js/maps.js');
		$start = new Waypoint();
		$start->osRef = getOSRefFromSixFigureReference($this->walk->startGridRef);
		$end = new Waypoint();
		$end->osRef = getOSRefFromSixFigureReference($this->walk->endGridRef);
		
		// Create the map	
		$document->addScriptDeclaration(<<<MAP
window.addEvent("domready", function() {
	var map = new SWGMap('map');
	map.addWalk({$this->walk->id});
});
MAP
);
		
		// Display the view
		parent::display($tpl);
	}
	
	public function urlToEdit(Walk $walk) {
		return $this->walkURL($walk,"addeditwalk");
	}
	
	private function walkURL(Walk $walk, $view)
	{
		// Get the current URL. We want to strip off anything in the parameters except a component
		$url = $_SERVER['REQUEST_URI'];
	
		// Get the current URL parameters
		if (strpos($url, "?") !== false)
		{
			$inParams = explode("&", substr($url,strpos($url,"?")+1));
			$urlBase = substr($url,0,strpos($url,"?"));
		}
		else
		{
			$inParams = array();
			$urlBase = $url;
		}
	
		// Build the new URL parameters
		$params = array(
			"view=".$view,
			"walkid=".$walk->id,
		);
	
		if (isset($inParams['option']))
			$params['option'] = $inParams['option'];
	
		return $urlBase."?".implode("&amp;", $params);
	}
}