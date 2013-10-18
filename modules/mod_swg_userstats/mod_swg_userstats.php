<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once(JPATH_BASE."/swg/swg.php");

// Get attended walks
$user = JFactory::getUser();
$wiFact = SWG::WalkInstanceFactory();
$wiFact->reset();
$wiFact->endDate = Event::DateEnd;
$wiFact->addAttendee($user->id);
$soFact = SWG::SocialFactory();
$soFact->reset();
$soFact->endDate = Event::DateEnd;
$soFact->addAttendee($user->id);
$weFact = SWG::WeekendFactory();
$weFact->reset();
$weFact->endDate = Event::DateEnd;
$weFact->addAttendee($user->id);

// TODO: Put into loops
$startDates = array('alltime'=>0, 'year'=>365, '3month'=>90, 'month'=>30);
foreach ($startDates as $period => $days)
{
	if (empty($days))
		$start = 0;
	else
		$start = time() - $days * 86400;
	
	$wiFact->startDate = $start;
	$weFact->startDate = $start;
	$soFact->startDate = $start;
	
	$walks[$period] = $wiFact->cumulativeStats();
	$socials[$period] = $soFact->cumulativeStats();
	$weekend[$period] = $weFact->cumulativeStats();
	
	// TODO: Check this doesn't fail for non-leaders: should say "0"
	$wiFact->leader = Leader::getJoomlaUser($user->id);
	if (!empty($wiFact->leader))
		$led[$period] = $wiFact->cumulativeStats();
	else
	    $led[$period] = array("count"=>0,"sum_miles"=>0,);
	
	$wiFact->leader = null;
}

require( JModuleHelper::getLayoutPath( 'mod_swg_userstats' ) );