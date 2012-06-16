<?php
defined('_JEXEC') or die('Restricted access');
require_once("SWGBaseModel");

/**
 * A walk in our library.
 * @see WalkInstance for an instance of a walk, with a date and a leader etc.
 * @author peter
 *
 */
class Walk extends SWGBaseModel {
  private $walkName;
  private $distanceGrade;
  private $difficultyGrade;
  private $miles;
  private $location;
  private $isLinear;
  private $startGridRef;
  private $startPlaceName;
  private $endGridRef;
  private $endPlaceName;
  private $routeDescription;
  private $fileLinks;
  private $information;
  private $routeImage;
  private $suggestedBy;
  private $status;
  private $specialTBC;
  private $dogFriendly;
  private $transportByCar;
  private $transportPublic;
  private $childFriendly;
  
  
}