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
$getNormal = $params->get("showNormal",true);
$getNewMember = $params->get("showNewMember",false);

// Load the JS stuff to run the popup
JHtml::_('behavior.framework', true);

JHTML::script('libraries/openlayers/OpenLayers.js');
JHTML::script("swg/js/maps.js",true);
JHTML::script("swg/js/events.js",true);
JHTML::script("modules/mod_swg_nextevents/script/nextevents.js",true);

// Load the menu item for the list page
$listPageID = $params->get("listPage");
$listPage = JRoute::_("index.php?Itemid={$listPageID}");
$showMoreLink = $params->get("moreLink", true);

$newMembers = false; // Set this as a default

switch($params->get('eventType')) {
  case SWG::EventType_Walk:
    JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
    $events = WalkInstance::getNext($numEvents);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'walks');
    break;
  case SWG::EventType_NewMemberSocial:
    $newMembers = true;
  case SWG::EventType_Social:
    JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
    $events = Social::getNext($numEvents,!$newMembers,$newMembers);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'socials');
    break;
  case SWG::EventType_Weekend:
    JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
    $events = Weekend::getNext($numEvents);
    require JModuleHelper::getLayoutPath('mod_swg_nextevents', 'weekends');
    break;
}


