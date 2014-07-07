<?php

require_once("Event.php");

/**
 * A dummy event, used to add messages to the events page.
 * The event always appears as the last event of the selected day,
 * and will not be exported to calendar or external feeds.
 */
class DummyEvent extends Event
{
	private $showWithWalks;
	private $showWithSocials;
	private $showWithWeekends;
	
	protected $dbMappingsExt = array(
		'showWithWalks'		=> 'showWithWalks',
		'showWithSocials'	=> 'showWithSocials',
		'showWithWeekends'	=> 'showWithWeekends',
	);
	
	public $type = "Dummy";
	
	public function getType()
	{
		return self::TypeDummy;
	}
	
	public function __set($name, $value)
	{
		switch ($name)
		{
			case "start":
				// Set start time to 23:59:59
				$dateArr = getdate($value);
				$value = mktime(23, 59, 59, $dateArr['mon'], $dateArr['mday'], $dateArr['year']);
				$this->$name = $value;
				break;
			case "showWithWalks":
			case "showWithSocials":
			case "showWithWeekends":
				$this->$name = (bool)$value;
				break;
			default:
				parent::__set($name, $value);
		}
	}
	
	public function __get($name)
	{
		return $this->$name; // TODO: What params should be exposed?
	}
	
	public function toDatabase(JDatabaseQuery &$query)
	{
		parent::toDatabase($query);
		
		// Handle bitwise mappings. TODO: This should be moved into a base class when it's used elsewhere.
		$query->set("alterations = b'".$this->alterations->getBitwise()."'");
	}
	
	public function fromDatabase(array $dbArr)
	{
		// Date format conversion
		$this->start = strtotime($dbArr['start']);
		unset($dbArr['start']);
		
		parent::fromDatabase($dbArr);
		
		// Handle bitwise mappings. TODO: This should be moved into a base class when it's used elsewhere.
		
	}
	
	public function valuesToForm()
	{
		$values = array(
			'id'			=> $this->id,
			'name'			=> $this->name,
			'description'	=> $this->description,
			'okToPublish'	=> $this->okToPublish,
		);
		
		if (!empty($this->start))
			$values['date'] = strftime("%Y-%m-%d", $this->start);
		return $values;
	}
	
	// TODO: ValuesToForm, isValid
	
	public function isValid()
	{
		return (!empty($this->name) && !empty($this->start));
	}
	
	// Methods that don't apply
	public function getOrganiser()	{ return null; }
	public function isOrganiser($user) { return false; }
	public function hasMap() { return false; }

}