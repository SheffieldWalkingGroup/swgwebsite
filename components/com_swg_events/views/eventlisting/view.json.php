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
		// TODO: Probably shouldn't return anything that isn't OK to publish - this is publicly accessible. This may be the cause of null events in the bottomless page output.
		$type = JRequest::getVar('eventtype',null,"get");
		$id = JRequest::getVar('id',null,"get","INTEGER");
		if (isset($id) && isset($type))
		{
			// Single event - connect directly to SWG backend
			// TODO: eventFactory method to take strings as well
			switch (strtolower($type)) {
				case "social":
					$factory = SWG::socialFactory();
					break;
				case "walk":
					$factory = SWG::walkInstanceFactory();
					break;
				case "weekend";
					$factory = SWG::weekendFactory();
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
				$evtProps = $event->sharedProperties();
				
				// Remove contact details from past events. Not really the best place to do it, but oh well. At least it means end users can't see them.
				if (
					(isset($event->endDate) && (unixtojd($event->endDate) < unixtojd(time()))) ||
					(isset($event->start) && (unixtojd($event->start) < unixtojd(time())))
				)
				{
					unset($evtProps['leader']['telephone']);
				}
				$result[] = $evtProps;
			}
			print json_encode($result);
		}
	}
}