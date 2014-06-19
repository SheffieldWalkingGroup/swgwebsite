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
			$result = array(
				'status' => JRequest::getBool("set"),
			);
			if (JRequest::getBool("stats"))
			{
				// TODO: Uncouple
				include_once(JPATH_SITE."/modules/mod_swg_userstats/helper.php");
				$result['stats'] = ModSWG_UserStatsHelper::getStats($user, UnitConvert::Mile);
			}
			echo json_encode($result);
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
	
	public function facebook()
	{
		$fb = SWG::getFacebook();
		if (!$fb)
			return;
			
		// Do we have publish permissions?
		$perms = $fb->api('me/permissions');
		$session = JFactory::getSession();
		if (empty($perms['data'][0]['publish_actions']))
		{
			if ($session->get("FBRequestPerm", false))
			{
				// Already requested permissions, don't do it again
				$session->set("FBRequestPerm", false);
				throw new UserException("Need publish permission", 0, "You need to give the Sheffield Walking Group app publish permissions to post events to Facebook.");
			}
			else
			{
				// Get publish permissions, then return here. Store a note in the session so we can handle the user rejecting the permission
				//$session->set("FBRequestPerm", true);
				$current = JURI::getInstance();
				$url = $fb->getLoginUrl(array(
					"scope" => 'publish_actions',
					"redirect_uri" => $current->toString(),
				));
				// TODO: check if we're using the API - can't redirect that
				header("Location: ".$url);
				exit(0);
			}
		}
		
		$session->set("FBRequestPerm", false);
		
		// Post the event
		$evtid = JRequest::getInt("evtid");
// 		$factory = SWG::eventFactory();
// 		$event = $factory->getSingle($evtid);
		
		$eventURI = new JURI("index.php?option=com_swg_events&view=eventlisting"); // TODO: Individual event links
		switch(JRequest::getInt("evttype"))
		{
			case SWG::EventType_Walk:
				// TODO: Did this person lead the walk?
				$action = "do";
				$type = "walk";
				break;
			case SWG::EventType_Social:
				$action = "go_to";
				$type = "social";
				$url = "http://samples.ogp.me/205234152933611";
				break;
			case SWG::EventType_Weekend:
				$action = "go_to";
				$type = "weekend_away";
				break;
		}
		$fb->api('me/sheffwalkinggroup:'.$action, 'POST',	array($type => $eventURI->toString()));
		// TODO: Need to store this post ID (event attendance table?) in case we change it later
	}

	

}