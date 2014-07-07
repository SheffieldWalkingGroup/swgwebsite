<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once(JPATH_SITE."/swg/swg.php");
require_once __DIR__ . '/helper.php';
JHTML::script("modules/mod_swg_userstats/script/userstats.js",true);
$stats = ModSWG_UserStatsHelper::getStats(JFactory::getUser(), UnitConvert::Mile);

require( JModuleHelper::getLayoutPath( 'mod_swg_userstats' ) );