<?php

require_once("SWGBaseModel.php");
/**
* A walk leader or backmarker
*/
class Leader extends SWGBaseModel {

protected $id;
private $username;
private $password;
private $surname;
private $forename;
protected $displayName;
protected $telephone;
private $email;
private $notes;
protected $noContactOfficeHours;
protected $active;
protected $dogFriendly;
protected $publishInOtherSites;
private $joomlaUserID;

private $hasDisplayName = false;

private $dbmappings = array(
	'id'					=> "ID",
	'surname'				=> "Surname",
	'forename'				=> "Forename",
	'username'				=> "loginid",
	// No display name in database
	'telephone'				=> "Telephone",
	'email'					=> "Email",
	'notes'					=> "Notes",
	'noContactOfficeHours'	=> "nocontactofficehours",
	'active'				=> "active",
	'dogFriendly'			=> "dogfriendly",
	'publishInOtherSites'	=> "publishinothersites",
	'joomlaUserID'			=> "joomlauser",
);
	
	/**
	 * The ID of the placeholder 'TBC' leader
	 */
	const TBC = 46;

	function __construct($dbArr = null) {
		if (isset($dbArr))
		{
			$this->id = $dbArr['ID'];
			$this->surname = $dbArr['Surname'];
			$this->forename = $dbArr['Forename'];
			$this->telephone = $dbArr['Telephone'];
			$this->email = $dbArr['Email'];
			$this->notes = $dbArr['Notes'];
			$this->noContactOfficeHours = (bool)$dbArr['nocontactofficehours'];
			$this->active = (bool)$dbArr['active'];
			$this->dogFriendly = (bool)$dbArr['dogfriendly'];
			$this->publishInOtherSites = (bool)$dbArr['publishinothersites'];
			$this->joomlaUserID = (int)$dbArr['joomlauser'];
			
			// Set a default display name
			// TODO: Could scan for multiple surnames and include all of them
			if ($this->id == self::TBC)
				$this->displayName = "TBC";
			else
				$this->displayName = ucwords($this->forename)." ".strtoupper(substr($this->surname,0,1));
			$this->hasDisplayName = false;
		}
	}


function __set($name, $value)
{
	switch ($name)
	{
		case "surname":
		case "forename":
		case "email":
		case "notes":
		case "telephone":
		case "username":
		case "password":
			// Text
			$this->$name = $value;
			break;
		case "joomlaUserID":
			// Integer
			$this->$name = (int)$value;
			break;
		case "noContactOfficeHours":
		case "active":
		case "dogFriendly":
		case "publishInOtherSites":
			// Boolean
			$this->$name = (bool)$value;
			break;
	}
}

/**
* Customises the leader's display name.
* If this isn't set, it defaults to Firstname S (initial)
* @param string $displayName
*/
function setDisplayName($displayName) {
	$this->displayName = $displayName;
	$this->hasDisplayName = true;
}

	/**
	* Return the leader associated with a particular Joomla user account
	* @param int $id Joomla user ID
	*/
	public static function getJoomlaUser($id) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walkleaders");

		$query->where(array("joomlauser = ".intval($id)));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 1)
			return new Leader($db->loadAssoc());
		else
			return null;
	}

	public static function getLeader($id) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walkleaders");

		$query->where(array("ID = ".intval($id)));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 1)
			return new Leader($db->loadAssoc());
		else
			return null;

	}


public function __get($name)
{
	return $this->$name; // TODO: What params should be exposed?
}

/**
* Save this leader to the database
*/
public function save($incrementVersion = true) {
	$db = JFactory::getDbo();
	
	// Commit everything as one transaction
	$db->transactionStart();
	$query = $db->getQuery(true);
	
	// Update or insert?
	if (!isset($this->id))
	{
		$query->insert("walkleaders");
	}
	else 
	{
		$query->where("ID = ".(int)$this->id);
		$query->update("walkleaders");
	}
	
	foreach ($this->dbmappings as $var => $dbField)
	{
		if (isset($this->$var))
			$query->set($dbField." = '".$query->escape($this->$var)."'");
	}
	
	// Hash the password
	if (isset($this->password))
	{
		$query->set("password = SHA1('".$query->escape($this->password)."')");
	}

	$db->setQuery($query);
	$db->query();
	
	if (!isset($this->id))
	{
		// Get the ID from the database
		$this->id = $db->insertid();
	}
	
	// TODO: Handle failure
	
	$db->transactionCommit();
}
}