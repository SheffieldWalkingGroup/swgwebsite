<?php
jimport('joomla.application.component.modelitem');
require_once("SWGBaseModel.php");
include_once(JPATH_BASE."/swg/lib/phpcoord/phpcoord-2.3.php");
/**
* Any event organised by the group
* @author peter
*
*/
abstract class Event extends SWGBaseModel {

	// Event properties
	protected $id;
	protected $name;
	protected $start;
	protected $description;
	protected $okToPublish;
	protected $alterations; 
	protected $numAttendees;
	protected $attendedBy;
	// True if all attendees have been loaded. We can cache individual attendees without loading them all.
	private $loadedAttendees = false;

	const DateToday = -1;
	const DateYesterday = -2;
	const DateTomorrow = -3;
	const DateEnd = 2147483647;

	const TypeWalk = 1;
	const TypeSocial = 2;
	const TypeWeekend = 3;
	const TypeDummy = -1;
	
	const TypeNewMemberSocial = 21;
	
	// Standard mappings for events table. Events that don't use this should overwrite, events that do should extend/merge.
	public $dbmappings = array(
		'id'			=> 'id',
		'name'			=> 'name',
		'start'			=> 'start',
		'description'	=> 'description',
		'okToPublish'	=> 'okToPublish',
	);
	
	public function __construct() {
		$this->alterations = new EventAlterations();
		
		if (isset($this->dbMappingsExt))
		{
			$this->dbmappings = array_merge($this->dbmappings, $this->dbMappingsExt);
		}
	}
	
	public function fromDatabase(array $dbArr)
	{
		if (isset($dbArr['numattendees']))
		{
			$this->numAttendees = (int)$dbArr['numattendees'];
		}
		parent::fromDatabase($dbArr);
	}

	/**
	* Default mutator, for basic properties
	*/
	public function __set($name, $value) {
		switch ($name)
		{
			case "description":
				// If the description is plain text, wrap it in <p>s and parse with nl2br
				if (strpos($value, "<p") !== 0)
				{
					$value = "<p>".nl2br($value)."</p>";
				}
				// Fall through
			case "name":
				$this->$name = $value;
				break;
			case "start":
			case "numAttendees":
				$this->$name = (int)$value;
				break;
			case "okToPublish":
			case "attendedby":
				$this->$name = (bool)$value;
				break;
		}
	}

	// TODO: Remove $type variable from each event, and anything calling it
	public function getType()
	{
		
	}

	/**
	* Takes a timestamp, and returns that date
	* @param int $time Timestamp. Supports DateToday constant.
	* @param bool $after True to return the day after this timestamp, false (default) to return the day of the timestamp
	*/
	public static function timeToDate($time, $after=false) {
		$time = intval($time);
		if ($time == self::DateToday)
			$rawDate = getdate();
		else if ($time == self::DateYesterday)
			$rawDate = getdate(time()-86400);
		else if ($time == self::DateTomorrow)
			$rawDate = getdate(time()+86400);
		else
			$rawDate = getdate($time);
		
		// Add on one day
		if ($after)
			$rawDate += 86400;
		
		$dateString = $rawDate['year']."-".$rawDate['mon']."-".$rawDate['mday'];
		return $dateString;
	}

	public function getEventType() {
		return strtolower(get_class($this));
	}

	public function isCancelled() {
		return $this->alterations->cancelled;
	}
	
	/**
	 * Returns an array of users who attended this event
	 * TODO: Check timings - does loading each user individually slow it down enough to be worth writing our own code to load users?
	 * @return int[] Array of user IDs
	 */
	public function getAttendees()
	{
		if (!$this->loadedAttendees)
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select("user as id");
			$query->from("eventattendance");
			$query->where(array(
				"eventtype = ".$this->getType(),
				"eventid = ".$this->id,
			));
			$db->setQuery($query);
			$attendIDs = $db->loadColumn(0);
			$this->attendedBy = array();
			foreach ($attendIDs as $attendee)
			{
				$this->attendedBy[$attendee] = JUser::getInstance($attendee);
			}
			$this->loadedAttendees = true;
		}
		return $this->attendedBy;
	}
	
	/**
	 * Finds if a specified user attended this event
	 * @param int $userId
	 * @return bool
	 */
	public function wasAttendedBy($userID)
	{
		// Check if we've already got that info
		if (isset($this->attendedBy[$userID]))
		{
			return $this->attendedBy[$userID];
		}
		else if (!$this->loadedAttendees)
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select("*");
			$query->from("eventattendance");
			$query->where(array(
				"user = ".(int)$userID,
				"eventtype = ".$this->getType(),
				"eventid = ".$this->id,
			));
			$db->setQuery($query, 0, 1);
			$attended = $db->loadAssocList();
			$this->attendedBy[$userID] = count($attended) > 0;
			return $this->attendedBy[$userID];
		}
		else
			return false;
	}
	
	/**
	 * Set whether an individual attended this event.
	 * THIS IS NOT SAVED: Use EventAttendance for this.
	 * @param int $userID
	 * @param bool $attended
	 */
	public function setAttendedBy($userID, $attended)
	{
		$this->attendedBy[$userID] = (bool)$attended;
	}
	
	public function setAttendees(array $attendees)
	{
		$this->attendedBy = $attendees;
	}
	
	/**
	 * Get the organiser of this event
	 * @return JUser
	 */
	public abstract function getOrganiser();
	
	/**
	 * Returns a word to describe the organiser of the event.
	 * Lower case - manipulate if needed
	 * @return string
	 */
	public function getOrganiserWord()
	{
		return "organiser";
	}
	
	/**
	 * True if the given user is the organiser of this event
	 * @param JUser|Leader $user User to check
	 * @return boolean
	 */
	public abstract function isOrganiser($user);

	/**
	* Whether this event can display a map
	* @return boolean
	*/
	public abstract function hasMap();

	/**
	* Converts the values of this event to an array suitable for outputting to a form
	* @return array
	*/
	public abstract function valuesToForm();

	/**
	* Determine if this event is valid and suitable for use
	* @return boolean
	*/
	public abstract function isValid();

	/**
	* Save this event to the database
	* Also handles versioning automatically
	*/
	public function save($incrementVersion = true) {
		$db = JFactory::getDbo();
		
		// Handle versioning & last modified
		if ($incrementVersion)
		{
			$this->alterations->incrementVersion();
			$this->alterations->setLastModified(time());
		}
		
		// Commit everything as one transaction
		$db->transactionStart();
		$query = $db->getQuery(true);
		
		$this->toDatabase($query);
		
		// What table?
		if ($this instanceof WalkInstance)
		{
			$table = "walkprogrammewalks";
			$idField = "SequenceID";
		}
		else if ($this instanceof Social)
		{
			$table = "socialsdetails";
			$idField = "SequenceID";
		}
		else if ($this instanceof Weekend)
		{
			$table = "weekendsaway";
			$idField = "ID";
		}
		else
		{
			$table = "events";
			$query->leftJoin(strtolower(get_class($this)." USING (id)"));
			$query->set("type = ".$this->getType());
		}
		
		// Update or insert?
		if (!isset($this->id))
		{
			$query->insert($table);
		}
		else 
		{
			$query->where($idField." = ".(int)$this->id);
			$query->update($table);
		}
		
		$db->setQuery($query);
		$db->query();
		
		if (!isset($this->id))
		{
			// Get the ID from the database
			$this->id = $db->insertid();
			$new = true;
		}
		else 
		{
			$new = false;
		}
		
		
		// Handle joined tables
		// TODO: Don't explicitly reference event Types, better handling without calling toDatabase twice
		if ($this instanceof DummyEvent)
		{
			$query = $db->getQuery(true);
			$query->replace(strtolower(get_class($this)));
			$query->set("id = ".$this->id);
			$this->toDatabase($query);
			$db->setQuery($query);
			$db->query();
		}
		
		// TODO: Handle failure
		
		// Commit the transaction
		$db->transactionCommit();
	}

	public function sharedProperties()
	{
		$prop = parent::sharedProperties();
		$prop['hasMap'] = $this->hasMap();
		return $prop;
	}
}

/**
* Keeps track of alterations to an event
* @author peter
*
*/
class EventAlterations extends SWGBaseModel {

	protected $version = 0;
	protected $lastModified = null;

	protected $details = false;
	protected $cancelled = false;
	protected $placeTime = false;
	protected $organiser = false;
	protected $date = false;
	
	/**
	 * Returns the boolean fields as a bitwise field (binary values only).
	 * Fields are returned in the order:
	 * 1. Details
	 * 2. Cancelled
	 * 3. PlaceTime
	 * 4. Organiser
	 * 5. Date
	 */
	public function getBitwise()
	{
		$valArr = array(
			(int)$this->details,
			(int)$this->cancelled,
			(int)$this->placeTime,
			(int)$this->organiser,
			(int)$this->date,
		);
		return implode("", $valArr);
	}

	public function __construct() {
		$this->version = 1;
	}

	public function setVersion($v) {
		$this->version = (int)$v;
	}

	public function setLastModified($d) {
		if (is_int($d))
		$this->lastModified = $d;
		else {
			$this->lastModified = strtotime($d);
		}
	}

	public function incrementVersion()
	{
		$this->version++;
		$this->setLastModified(time());
	}
		
	public function setDetails($d) {
		$this->details = (bool)$d; 
	}

	public function setCancelled($c) {
		$this->cancelled = (bool)$c;
	}

	public function setPlaceTime($m) {
		$this->placeTime = (bool)$m;
	}

	public function setOrganiser($l) {
		$this->organiser = (bool)$l;
	}

	public function setDate($d) {
		$this->date = (bool)$d;
	}

	public function __get($name)
	{
		return $this->$name; // TODO: What params should be exposed?
	}

	public function anyAlterations()
	{
		return ($this->details || $this->cancelled || $this->placeTime || $this->organiser || $this->date);
	}

	/**
	* Add in anyAlterations
	* @see SWGBaseModel::sharedProperties()
	*/
	public function sharedProperties() {
		$prop = parent::sharedProperties();
		$prop['any'] = $this->anyAlterations();
		return $prop;
	}
}