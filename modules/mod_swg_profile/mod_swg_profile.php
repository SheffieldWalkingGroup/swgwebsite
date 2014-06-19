<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once(dirname(__FILE__).'/helper.php');
require_once(JPATH_BASE."/swg/swg.php");

// TODO: Move main code to helper file or delete helper file
$user = JFactory::getUser();

// TODO: Get as much as possible with a single query
$db = JFactory::getDbo();
$db->setQuery(
	'SELECT profile_value FROM #__user_profiles' .
	' WHERE user_id = '.(int) $user->id." AND profile_key = 'swg_extras.joindate'" .
	' LIMIT 1'
);
$db->query();
if ($db->getNumRows())
{
	$results = $db->loadAssoc();
	$joindate = strtotime($results['profile_value']);
}

// Load JS to handle attendance changes
JHtml::_('behavior.framework', true);

JHTML::script("swg/js/events.js",true);
JHTML::script("modules/mod_swg_profile/script/profile.js",true);

// Get attended walks
$wiFact = SWG::WalkInstanceFactory();
$wiFact->reset();
$wiFact->startDate = 0;
$wiFact->endDate = Event::DateEnd;
$wiFact->addAttendee($user->id);
$numWalks = $wiFact->numEvents();
$walkStats = $wiFact->cumulativeStats();

require( JModuleHelper::getLayoutPath( 'mod_swg_profile' ) );