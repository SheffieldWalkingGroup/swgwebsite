<?php
class ModSWG_UserStatsHelper
{
	/**
	 * Outputs arrays of user stats
	 * @param JUser $user 			User to get stats for. Default is current user.
	 * @param int 	$distanceUnits	Convert distance units to this unit. Includes unit suffix, abbreviated - intended for display
	 */
	static function getStats($user = null, $distanceUnits = null)
	{
		// Get attended walks
		if (!isset($user))
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
			
			$walkData = $wiFact->cumulativeStats();
			$socials[$period] = $soFact->cumulativeStats();
			$weekend[$period] = $weFact->cumulativeStats();
			
			// Stats for all-day walks (walks starting at or before 14:00)
			$wiFact->startTimeMax = 14*3600;
			$dayWalkData = $wiFact->cumulativeStats();
			$wiFact->startTimeMax = null;
			
			// TODO: Check this doesn't fail for non-leaders: should say "0"
			$wiFact->leader = Leader::fromJoomlaUser($user->id);
			if (!empty($wiFact->leader))
				$ledData = $wiFact->cumulativeStats();
			else
				$ledData = array("count"=>0,"sum_miles"=>0,);
			
			// Convert walk units if needed
			if (isset($distanceUnits))
			{
				$walkData['sum_miles'] = UnitConvert::displayDistance($walkData['sum_miles'], UnitConvert::Mile, $distanceUnits, false);
				$walkData['mean_miles'] = UnitConvert::displayDistance($walkData['mean_miles'], UnitConvert::Mile, $distanceUnits, false);
				$walkData['sum_distance'] = UnitConvert::displayDistance($walkData['sum_distance'], UnitConvert::Metre, $distanceUnits, false);
				$walkData['mean_distance'] = UnitConvert::displayDistance($walkData['mean_distance'], UnitConvert::Metre, $distanceUnits, false);
				
				$dayWalkData['sum_miles'] = UnitConvert::displayDistance($dayWalkData['sum_miles'], UnitConvert::Mile, $distanceUnits, false);
				$dayWalkData['mean_miles'] = UnitConvert::displayDistance($dayWalkData['mean_miles'], UnitConvert::Mile, $distanceUnits, false);
				$dayWalkData['sum_distance'] = UnitConvert::displayDistance($dayWalkData['sum_distance'], UnitConvert::Metre, $distanceUnits, false);
				$dayWalkData['mean_distance'] = UnitConvert::displayDistance($dayWalkData['mean_distance'], UnitConvert::Metre, $distanceUnits, false);
				
				if (!empty($wiFact->leader)) // Note: leader hasn't been unset from factory yet
				{
					$ledData['sum_miles'] = UnitConvert::displayDistance($ledData['sum_miles'], UnitConvert::Mile, $distanceUnits, false);
					$ledData['mean_miles'] = UnitConvert::displayDistance($ledData['mean_miles'], UnitConvert::Mile, $distanceUnits, false);
					$ledData['sum_distance'] = UnitConvert::displayDistance($ledData['sum_distance'], UnitConvert::Metre, $distanceUnits, false);
					$ledData['mean_distance'] = UnitConvert::displayDistance($ledData['mean_distance'], UnitConvert::Metre, $distanceUnits, false);
				}
			}
			$walks[$period] = $walkData;
			$dayWalks[$period] = $dayWalkData;
			$led[$period] = $ledData;
			$wiFact->leader = null; // Note: this is AFTER the conversions, we check if the leader is set in there
		}
		
		return array(
			'walks'		=> $walks,
			'daywalks'	=> $dayWalks,
			'led'		=> $led,
			'socials'	=> $socials,
			'weekends'	=> $weekend,
		);
	}
	
	static function statsToGrid($stats)
	{
	
	}
}