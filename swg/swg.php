<?php
JLoader::register('SocialFactory', JPATH_SITE."/swg/Factories/SocialFactory.php");
JLoader::register('WalkInstanceFactory', JPATH_SITE."/swg/Factories/WalkInstanceFactory.php");
JLoader::register('WeekendFactory', JPATH_SITE."/swg/Factories/WeekendFactory.php");
JLoader::register('DummyEventFactory', JPATH_SITE."/swg/Factories/DummyEventFactory.php");
JLoader::register('UnitConvert', JPATH_SITE."/swg/UnitConvert.php");
JLoader::register('UserException', JPATH_SITE."/swg/Exceptions/UserException.php");
JLoader::register('Leader', JPATH_SITE."/swg/Models/Leader.php");
JLoader::register('Facebook', JPATH_SITE."/libraries/facebook/facebook.php");
JLoader::register('Event', JPATH_SITE."/swg/Models/Event.php");
JLoader::register('WalkProgramme', JPATH_SITE."/swg/Models/WalkProgramme.php");
JLoader::register('WalkProposal', JPATH_SITE."/swg/Models/WalkProposal.php");
/**
 * A collection of general functions and constants
 * @author peter
 *
 */
class SWG {
	
	public static $fbconf = array(
		'appId'	=> 204618129661880,
		'secret'=> "13b065f077df0e3c3badaf715487d2b4",
	);
	
	private static $factoryWalkInstance;
	private static $factorySocial;
	private static $factoryWeekend;
	private static $factoryDummy;
	
	/**
	 * Returns the WalkInstance factory
	 * @return WalkInstanceFactory
	 */
	public static function walkInstanceFactory()
	{
		if (!isset(self::$factoryWalkInstance))
			self::$factoryWalkInstance = new WalkInstanceFactory();
		return self::$factoryWalkInstance;
	}
	
	/**
	 * Returns the Social factory
	 * @return SocialFactory
	 */
	public static function socialFactory()
	{
		if (!isset(self::$factorySocial))
			self::$factorySocial = new SocialFactory();
		return self::$factorySocial;
	}
	
	/**
	 * Returns the Weekend factory
	 * @return WeekendFactory
	 */
	public static function weekendFactory()
	{
		if (!isset(self::$factoryWeekend))
			self::$factoryWeekend = new WeekendFactory();
		return self::$factoryWeekend;
	}
	
	/**
	 * Returns the Dummy event factory
	 * @return DummyEventFactory
	 */
	public static function dummyFactory()
	{
		if (!isset(self::$factoryDummy))
			self::$factoryDummy = new DummyEventFactory();
		return self::$factoryDummy;
	}
	
	/**
	 * Returns a factory for an unknown event type
	 * @param int $evtType Event type to return a factory for. See EventType_* constants
	 * @return EventFactory
	 */
	public static function eventFactory($evtType)
	{
		switch($evtType)
		{
			case Event::TypeWalk:
				return self::walkInstanceFactory();
			case Event::TypeSocial:
				return self::socialFactory();
			case Event::TypeWeekend:
				return self::weekendFactory();
			case Event::TypeDummy:
				return self::dummyFactory();
		}
	}
	
	public static function printableEventType($typeID)
	{
		switch ($typeID)
		{
		case Event::TypeWalk:
			return "walk";
		case Event::TypeSocial:
			return "social";
		case Event::TypeWeekend:
			return "weekend";
		case Event::TypeNewMemberSocial:
			return "new member social";
		}
	}
	
	/**
	 * Parses a latitude,longitude string into an array
	 * @param string $string String with format latitude,longitude
	 * @return LatLng
	 */
	public static function parseLatLongTuple($string)
	{
		if (empty($string))
			return null;
		
		$loc = explode(",", $string);
		if (count($loc) != 2 || !is_numeric($loc[0]) || !is_numeric($loc[1]))
		{
			throw new InvalidArgumentException("Position must be given as &lt;latitude,longitude&gt;.");
			
		}
			
		return New LatLng((float)$loc[0], (float)$loc[1]);
	}
	
	/**
	 * Returns a Facebook API object for the current user
	 * If one doesn't already exist in the session, it will be set up using the access token in the user's profile
	 * The resulting SDK object will be stored in the session.
	 * @return Facebook|bool Returns false if user has no access token
	 */
	/*public static function getFacebook()
	{
		// Check if there's already a Facebook object in the session
		$session = JFactory::getSession();
		if (!$session->has("facebook") || !$session->get("facebook") instanceof Facebook)
		{
			// Get the access token from the database (if any)
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select("profile_value");
			$query->from("j_user_profiles");
			$query->where("user_id = ".intval(JFactory::getUser()->id));
			$query->where("profile_key = 'swg_extras.fbtoken'");
			$db->setQuery($query);
			$res = $db->query();
			if ($db->getNumRows($res) == 1)
			{
				$fb = new Facebook(self::$fbconf);
				$row = $db->loadColumn();
				$fb->setAccessToken($row[0]);
				if ($fb->getUser())
					$session->set("facebook", $fb);
				else
				    return false;
				
			}
			else
			{
				return false;
			}
		}
		return $session->get("facebook");
	}*/
	
	/**
	 * Converts a time in server local time to UTC
	 * @param int $localtime Time in server local time
	 * @return int Time in UTC
	 */
	public static function localToUTC($localtime)
	{
		$offset = strftime("%z", $localtime);
		
		$offsetSecs = (substr($offset, 1, 2) * 3600) + (substr($offset, 3, 2) * 60);
		if (substr($offset, 0, 1) == "-")
			$offsetSecs = $offsetSecs * -1;
		
		$utc = ($localtime + $offsetSecs);
		return $utc;
	}
}

/**
 * Interface for anything walkable
 * Walkable things can have routes etc.
 * @author peter
 *
 */
interface Walkable {

}
