<?php
require_once("SWGBaseModel.php");
/**
* A meeting point for a walk,
* and an instance of meeting there.
* Generates information about meeting for a walk:
* place, time, etc.
*/
class WalkMeetingPoint extends SWGBaseModel {
const Meet_FITZ = 1;
const Meet_TESCO = 2;
const Meet_UNIV = 3;
const Meet_START = 4;
const Meet_OTHER = 7;

protected $id;
protected $shortDesc;
protected $longDesc;
protected $location;
protected $extra;
protected $meetTime;

/**
* Constructs a meeting point for the specified WalkInstance
* TODO: Might want constructors that don't take a WalkInstance.
* If so, will need a global constructor that hands the job out to worker methods
* (simulating method overloading)
* @param int $meetPoint Meeting point ID
* @param int $meetTime Meeting time as a Unix timestamp
* @param String $meetPlaceTime Additional information about meeting for this walk 
*/
public function __construct($meetPoint, $meetTime, $meetPlaceTime) {
	$db = JFactory::getDBO();
	$query = $db->getQuery(true);
	$query->select("*");
	$query->from("startpoints");

	$query->where(array("SequenceID = ".intval($meetPoint)));
	$db->setQuery($query);
	$res = $db->query();
	if ($db->getNumRows($res) == 1) {
	$row = $db->loadAssoc();
	$this->id = (int)$row['SequenceID'];
	$this->shortDesc = $row['ShortDesc'];
	$this->longDesc = $row['LongDesc'];
	
	if (isset($row['latitude']) && isset($row['longitude']))
	{
		$this->location = new LatLng($row['latitude'], $row['longitude']);
	}
	$this->meetTime = $meetTime;
	// Usually public transport info
	$this->extra = $meetPlaceTime;
	}
	else
	{
	$this->shortDesc = "NONE";
	$this->longDesc = "unspecified meeting place";
	} 
}

/**
* True if the meeting point is the walk start
*/
public function isAtWalkStart() {
	return $this->id == self::Meet_START;
}

/**
* True if the meeting point is "other" - usually we'll only output the extra info
*/
public function isOther() {
	return $this->id == self::Meet_OTHER;
}

/**
* True if there's an extra bit of info, e.g. public transport
*/
public function hasExtraInfo() {
	return !empty($this->extra);
}

public function __get($name)
{
	return $this->$name; // TODO: What params should be exposed?
}

public function setExtra($value)
{
	$this->extra = $value;
}
}
