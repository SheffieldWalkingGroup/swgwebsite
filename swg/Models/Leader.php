<?php

require_once("SWGBaseModel.php");
/**
 * A walk leader or backmarker
 */
class Leader extends SWGBaseModel {
  protected $id;
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
  
  function __construct($dbArr) {
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
    
    // Set a default display name
    // TODO: Could scan for multiple surnames and include all of them
    $this->displayName = ucwords($this->forename)." ".strtoupper(substr($this->surname,0,1));
  }
  
  /**
   * Customises the leader's display name.
   * If this isn't set, it defaults to Firstname S (initial)
   * @param string $displayName
   */
  function setDisplayName($displayName) {
    $this->displayName = $displayName;
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
}