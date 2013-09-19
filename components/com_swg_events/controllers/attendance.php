<?php
// No direct access.
defined('_JEXEC') or die;
// Include dependancy of the main controllerform class
jimport('joomla.application.component.controlleradmin');
require_once JPATH_BASE."/swg/Models/Event.php";

class SWG_EventsControllerAttendance extends JControllerAdmin
{
  
	// Store the model so it can be given to the view
	private $model;
	
	public function __construct($config = array())
	{
		parent::__construct($config);
		// Define standard task mappings.
		$this->registerTask('set', 'set');

	}

	public function attend()
	{
		// Record this user's attendance or non-attendance
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$values = array(
			"eventtype = ".JRequest::getInt("evttype"),
			"eventid = ".JRequest::getInt("evtid"),
			"user = ".$user->id,
		);
		
		// Has this user been to this event?
		$query->select("count(1)");
		$query->from("eventattendance");
		$query->where($values);
		$db->setQuery($query);
		$been = $db->loadResult();
		
		// Set up delete/insert query
		if ($been && !JRequest::getBool("set"))
		{
			// Unset attendance
			$query = $db->getQuery(true);
			$query->delete("eventattendance");
			$query->where($values);
			$db->setQuery($query);
			$db->query();
		}
		else if (!$been && JRequest::getBool("set"))
		{
			// Set attendance
			$query = $db->getQuery(true);
			$query->insert("eventattendance");
			$query->set($values);
			$db->setQuery($query);
			$db->query();
		}
		
		// Return to the page, showing the current event
		switch(JRequest::getInt("evttype"))
		{
			case Event::TypeWalk:
				$anchor="walk";
				break;
			case Event::TypeSocial:
				$anchor="social";
				break;
			case Event::TypeWeekend:
				$anchor="weekend";
				break;
			default:
				$anchor="event";
				break;
		}
		
		// Redirect to display the event, unless this is an AJAX request
		// TODO: Should use format=json
// 		if (strtolower(JRequest::getString("format")) == "json")
		if (JRequest::getBool("json"))
		{
			echo json_encode(JRequest::getBool("set"));
			exit();
		}
		else
		{
		    $anchor .= "_".JRequest::getInt("evtid");
			$target = JURI::current() . "#" . $anchor;
			JFactory::getApplication()->redirect($target);
		}
		
		return false;
	}

	

}