<?php

/**
 * Utilities to do with attending an event, that don't affect the event itself
 * TODO: Some of these functions should work with the event object rather than do raw DB calls
 */
class EventAttendance 
{
	private $event;
	
	public function __construct(Event $evt)
	{
		$this->event = $evt;
	}
	
	/**
	 * Adds the specified user as an attendee of this event
	 * @param int $userID User ID
	 */
	public function addAttendee($userID)
	{
		// Check if this user has already attended this event
		if ($event->wasAttendedBy($userID))
			return;
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->set(array(
			"eventtype = ".$this->event->getType(),
			"eventid = ".$this->event->id,
			"user = ".(int)$userID,
		));
		$query->insert("eventattendance");
	}
	
	/**
	 * Gets all events attended by a particular user
	 * @param int $userID
	 * @return array with keys 'type' and 'id'
	 * @todo Use the event store in memory and return full events
	 */
	public static function eventsAttendedBy($userID)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("eventtype as type, eventid as id");
		$query->from("eventattendance");
		$query->where(array("user = ".(int)$userID));
		$db->setQuery($query);
		$attended = $db->loadAssocList();
		// TODO: get events from the event store
		return $attended;
	}
}