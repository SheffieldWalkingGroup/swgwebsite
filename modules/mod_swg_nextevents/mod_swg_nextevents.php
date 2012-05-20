<?php
 
/**
 * @package     SWG
 * @subpackage  mod_swg_nextevents
 * @copyright   Copyright (C) 2012 Peter Copeland. All rights reserved.
 */
 
// No direct access to this file
defined('_JEXEC') or die;
require_once JPATH_BASE."/swg/swg.php";

$events = array();
$numEvents = $params->get("numberOfEvents",3);

switch($params->get('eventType')) {
  case SWG::EventType_Walk:
    JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
    $events = WalkInstance::getNext($numEvents);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'walks');
    break;
  case SWG::EventType_Social:
    JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
    $events = Social::getNext($numEvents);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'socials');
    break;
  case SWG::EventType_Weekend:
    JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
    $events = Weekend::getNext($numEvents);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'weekends');
    break;
}


