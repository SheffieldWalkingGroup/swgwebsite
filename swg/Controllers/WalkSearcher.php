<?php

include_once(JPATH_BASE."/swg/lib/phpcoord/phpcoord-2.3.php");
include_once(JPATH_BASE."/swg/Models/Walk.php");

/**
 * This class handles searching for walks. 
 * You can set various parameters on it, and it queries the database then does extra filtering if needed. 
 * 
 * String fields are matched using case insensitive searches supporting SQL wildcards (%) and no other SQL characters
 * ENUM fields (difficulty/distance grades) take arrays with all valid options (or null to allow all, empty array to allow none)
 * Numeric fields take array('min'=>? 'max'=>?), or a single value
 * Boolean values take true, false, or null (don't care)
 * Other fields are explained on relative methods
 * 
 */
class WalkSearcher
{
  private $name = null;
  private $distanceGrade = null;
  private $difficultyGrade = null;
  private $miles = null;
  private $location = null;
  private $isLinear = null;
  private $startGridRef = null;
  private $endGridRef = null;
  private $description = null;
  private $information = null;
  private $suggestedBy = null;
  private $dogFriendly = null;
  private $transportByCar = null;
  private $transportPublic = null;
  private $childFriendly = null;
  
  // Special fields
  
  /**
   * Text search in public text fields (name, description). 
   * @var string
   */
  private $textPublic = null;
  
  /**
   * Text search in all text fields (name, description, information).
   * @var string
   */
  private $textAll = null;
  
  /**
   * Combination of distance and difficulty grades. Pass an array of all combinations,
   * e.g. array(array('distance'=>'A', 'difficulty'=>1),array('distance'=>'a', 'difficulty'=>2))
   * If this is set, distance and difficulty grades are ignored.
   * @var array
   */
  private $grade = null;
  
  
  /**
   * Performs a search with the current parameters
   */
  public function search()
  {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walks");
  
    if (isset($this->name))
      $query->where("walkName LIKE '".$db->escape($this->name))."'";
  
    if (isset($this->grade))
    {
      // An array of grade combinations in database format. These are imploded with an OR.
      $grades = array();
      foreach ($this->grade as $grade)
      {
        $grades[] = "(difficultygrade = '".$grade['difficulty']."' AND distancegrade = '".$grade['distance']."')";
      }
      $query->where("(".implode(" OR ", $grades).")");
    }
    else
    {
      if (isset($this->distanceGrade))
      {
        $query->where("distancegrade IN (".implode(",", $this->distanceGrade).")");
      }
      if (isset($this->difficultyGrade))
      {
        $query->where("difficultygrade IN (".implode(",", $this->difficultyGrade).")");
      }
    }
    
    // TODO: Length (miles)
    // Location (general area)
    if (isset($this->location))
      $query->where("location IN (".implode(",",$this->location).")");
    
    if (isset($this->isLinear))
    {
      $query->where("islinear = ".$this->isLinear);
    }
    
    // TODO: Start grid ref
    // TODO: End grid ref
    
    if (isset($this->description))
      $query->where("routedescription LIKE '".$db->escape($this->description))."'";
    
    if (isset($this->information))
      $query->where("information LIKE '".$db->escape($this->information))."'";
    
    if (isset($this->dogFriendly))
      $query->where("dogfriendly = ".$this->dogFriendly);
    if (isset($this->childFriendly))
      $query->where("childfriendly = ".$this->childFriendly);
    
    // TODO: Transport by car/public
    if (isset($this->transportByCar))
      $query->where("transportbycar = ".$this->transportByCar);
    if (isset($this->transportPublic))
      $query->where("transportpublic = ".$this->transportPublic);
    
    if (isset($this->textPublic))
    { 
      $query->where("(walkname LIKE '%".$db->escape($this->textPublic)."%' OR routedescription LIKE '%".$db->escape($this->textPublic)."%')");
    }
    
    if (isset($this->textAll))
    {
      $query->where("(walkname LIKE '%".$db->escape($this->textAll)."%' OR routedescription LIKE '%".$db->escape($this->textAll)."%' OR information LIKE '%".$db->escape($this->textAll)."%')");
    }
    
    // Suggested by
    if (isset($this->suggestedBy))
      $query->where("suggestedby = ".$this->suggestedBy->id);
    
    // TODO: Sorting
    $db->setQuery($query);
    $cursor = $db->query();
    
    // TODO: Filtering
    
    // Return results as array of walks
    $results = array();
    $walkData = $db->loadAssocList();
    foreach ($walkData as $row)
    {
      $results[] = new Walk($row);
    }
    return $results;
    
  }
  
  /**
   * Search for a walk by name. Wildcards (%) are supported. Case insensitive.
   * @param string $n
   */
  public function setName($n)
  {
    $this->name = $n;
  }
  
  /**
   * Search for a walk by distance grade. Include all valid options.
   * @param array $dg
   */
  public function setDistanceGrade(array $dg)
  {
    if (is_array($dg))
    {
      $this->distanceGrade = array();
      foreach ($dg as $grade)
      {
        $grade = strtoupper($grade);
        if (in_array($grade, array("A","B","C")))
          $this->distanceGrade[] = $grade;
      }
    }
    else if (is_null($dg))
      $this->distanceGrade = null;
  }
  
  /**
   * Search for a walk by difficulty grade. Include all valid options.
   * @param array $dg
   */
  public function setDifficultyGrade(array $dg)
  {
    if (is_array($dg))
    {
      $this->difficultyGrade = array();
      foreach ($dg as $grade)
      {
        $grade = (int)$grade;
        if ($grade >= 1 && $grade <= 3)
          $this->difficultyGrade[] = $grade;
      }
    }
    else if (is_null($dg))
      $this->difficultyGrade = null;
  }
  
  /**
   * Search for a walk by combined distance and difficulty grades. Include all valid options,
   * e.g. array(array('distance'=>'A', 'difficulty'=>1),array('distance'=>'a', 'difficulty'=>2))
   * OR array('A1','a2')
   * If this is set, distance and difficulty grade settings are ignored.
   * Case insensitive
   * @param array $g
   */
  public function setGrade(array $g)
  {
    $valid = array();
    foreach ($g as &$grade)
    {
      if (is_string($grade))
      {
        // Parse this grade into an array
        if (preg_match("/([ABC])([123])/i",$grade,$parsed))
          $valid[] = array('distance'=>strtoupper($parsed[1]), 'difficulty'=>(int)$parsed[2]);
      }
      else if (is_array($grade) && key_exists("distance", $grade) && key_exists("difficulty", $grade))
      {
        $distance = strtoupper($grade['distance']);
        $difficulty = (int)$grade['difficulty'];
        
        if (in_array($distance, array("A","B","C")) && $difficulty >= 1 && $difficulty <= 3)
          $valid[] = $grade;
      }
    }
    
    if (!empty($valid))
      $this->grade = $valid;
  }
  
  /**
   * Search for a walk by the length in miles
   * @param float|array $m
   */
  public function setMiles($m)
  {
    if (is_numeric($m) || is_array($m))
      $this->miles = $m;
    else
      throw new OutOfBoundsException("Expected a number or a range");
  }
  
  /**
   * Limits to walks in certain general areas
   *
   * @param array $l
   */
  public function setLocation(array $l)
  {
    $valid = array();
    foreach ($l as $loc)
    {
      if (is_int($loc))
        $valid[] = $loc;
    }
    $this->location = $valid;
  }

  /**
   * Limits to linear or non-linear walks
   * @param bool $l
   */
  public function setIsLinear($l)
  {
    if (is_bool($l) || is_null($l))
      $this->isLinear = $l;
  }
  
  /**
   * Sets a required start point
   * TODO: build in a reasonable tolerance
   * @param OSRef $ref
   */
  public function setStartGridRef(OSRef $ref)
  {
    $this->startGridRef = $ref;
  }
  
  /**
   * Sets a required start point
   * TODO: build in a reasonable tolerance
   * @param OSRef $ref
   */
  public function setEndGridRef(OSRef $ref)
  {
    $this->endGridRef = $ref;
  }

  /**
   * Search for a walk by description. Wildcards (%) are supported. Case insensitive.
   * @param string $d
   */
  public function setDescription ($d)
  {
    $this->description = $d;
  }

  /**
   * Search for a walk by information for leaders. Wildcards (%) are supported. Case insensitive.
   * @param string $n
   */
  public function setInformation ($i)
  {
    $this->information = $i;
  }

  /**
   * Search for a walk by the leader who suggested it
   * @param Leader $l
   */
  public function setSuggestedBy (Leader $l)
  {
    $this->suggestedBy = $l;
  }

  public function setDogFriendly ($df)
  {
    if (is_bool($df) || is_null($df))
      $this->dogFriendly = $df;
  }
  
  public function setTransportByCar($car)
  {
    if (is_bool($car) || is_null($car))
      $this->transportByCar = $car;
  }
  
  public function setTransportPublic($public)
  {
    if (is_bool($public) || is_null($public))
      $this->transportPublic = $public;
  }
  
  public function setChildFriendly($cf)
  {
    if (is_bool($cf) || is_null($cf))
      $this->childFriendly = $cf;
  }

  public function setPublicText($t)
  {
    $this->textPublic = $t;
  }
  
  public function setAnyText($t)
  {
    $this->textAll = $words;
  }
  
}