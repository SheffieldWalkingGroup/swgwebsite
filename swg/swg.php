<?php
/**
 * A collection of general functions and constants
 * @author peter
 *
 */
class SWG {
	const EventType_Walk = 1;
	const EventType_Social = 2;
	const EventType_Weekend = 3;
	const EventType_NewMemberSocial = 21;
	
	public static function printableEventType($typeID)
	{
		switch ($typeID)
		{
		case self::EventType_Walk:
			return "walk";
		case self::EventType_Social:
			return "social";
		case self::EventType_Weekend:
			return "weekend";
		case self::EventType_NewMemberSocial:
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
}

/**
 * Interface for anything walkable
 * Walkable things can have routes etc.
 * @author peter
 *
 */
interface Walkable {

}