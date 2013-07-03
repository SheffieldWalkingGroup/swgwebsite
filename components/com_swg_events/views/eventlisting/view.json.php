<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * HTML Event listing class for the SWG Events component
 */
class SWG_EventsViewEventListing extends JView
{
	// Overwriting JView display method
	function display($tpl = null) 
	{
		// What type of event to we want?
		// TODO: Probably shouldn't return anything that isn't OK to publish - this is publicly accessible.
		$type = JRequest::getVar('eventtype',null,"get");
		$id = JRequest::getVar('id',null,"get","INTEGER");
		if (isset($id) && isset($type))
		{
			// Single event - connect directly to SWG backend
			switch (strtolower($type)) {
				case "social":
					$factory = SWG::socialFactory();
					break;
				case "walk":
					$factory = SWG::walkInstanceFactory();
					break;
				case "weekend";
					$factory = SWG::walkInstanceFactory();
					break;
			}
			$result = $factory->getSingle($id);
			print $result->jsonEncode();
		}
		else
		{
			// Go through the model
			$events = $this->get('Events');
			$result = array();
			foreach ($events as $event)
			{
				$result[] = $event->sharedProperties();
			}
			print json_encode($result);
		}
	}
}