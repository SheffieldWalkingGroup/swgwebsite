<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');

JLoader::register('Leader', JPATH_BASE."/swg/Models/Weekend.php");
JLoader::register('Leader', JPATH_BASE."/swg/Models/WalkInstance.php");

include_once(JPATH_SITE."/components/com_swg_events/helpers/views/eventinfo.html.php");
include_once(JPATH_SITE."/components/com_swg_leaderutils/controller.php");

 
/**
 * HTML View class for the HelloWorld Component
 */
class SWG_LeaderUtilsViewProposeWalk extends SWG_EventsHelperEventInfo
{
	/** @var Weekend[] */
	private $weekends;
	
	/**
	 * Maps dates (int) => Weekend
	 */
	private $dateWeekend;
	
	/**
	 * True if we've just saved
	 */
	public $saved = false;
	
	function display($tpl = null)
	{
		// Model defaults are the current user and the next unpublished programme. That's what we want, so don't change it
		$this->controller = JControllerLegacy::getInstance('SWG_LeaderUtils');
		
		$this->form = $this->get("form");
		$this->programme = $this->get("programme");
		$this->walk = $this->get("walk");
		
		$this->walkInstance = $this->get("walkInstance");
		
		$this->form->getField('availability')->setProgramme($this->programme);
		// Set default leader (only relevant if user can select leader)
		$myLeader = Leader::fromJoomlaUser(JFactory::getUser()->id);
		$this->form->setValue("leader", "leader", $myLeader->id);
		$this->leader = $this->get("leader");
		
		// Pre-load the dates of weekends away during this programme (only check start and end dates now, we'll check exact dates when using them)
		$weFactory = SWG::WeekendFactory();
		$weFactory->reset();
		$weFactory->startDate = $this->programme->startDate;
		$weFactory->endDate = $this->programme->endDate;
		$weFactory->showUnpublished = true;
		$this->weekends = $weFactory->get();
		
		parent::display($tpl);
	}
	
	/**
	 * Outputs a date class for this date's row in the table
	 * Matches a weekend away: weekend
	 * Saturday: walksaturday
	 * Sunday: walksunday
	 * Weekday: walkweekday
	 */
	function dayClass($date)
	{
		if ($this->isWeekendAway($date))
			return "weekend";
		elseif (strftime("%w", $date) == 6)
			return "walksaturday";
		else if (strftime("%w", $date) == 0)
			return "walksunday";
		else {
		    return "walkweekday";
		}
		
	}
	
	/**
	 * Returns the number of days in this programme that the weekend on this date will run for,
	 * starting with the given date.
	 * i.e. if the date is Sunday, a normal weekend will have 1 more day
	 */
	public function weekendLength($date)
	{
		$we = $this->getWeekend($date);
		if ($we != null)
		{
			// Find this date in the programme
			$dateIndex = array_search($date, $this->programme->dates);
			$numDays = 1;
			$dateIndex++;
			while ($this->getWeekend($this->programme->dates[$dateIndex]) != null)
			{
				$numDays++;
				$dateIndex++;
			}
			
			return $numDays;
		}
	}
	
	/**
	 * Gets a weekend away happening on this date. Caches.
	 * Returns null if no weekend
	 */
	private function getWeekend($date)
	{
		// array_key_exists returns true if the value is null
		if (array_key_exists($date, $this->dateWeekend))
			return $this->dateWeekend[$date];
		
		foreach ($this->weekends as $we)
		{
			if ($we->start <= $date && $we->endDate+86400 >= $date)
			{
				$this->dateWeekend[$date] = $we;
				return $we;
			}
		}
		$this->dateWeekend[$date] = null;
		return null;
	}
	
	function isWeekendAway($date)
	{
		return ($this->getWeekend($date) != null);
	}
	
	function weekendInfo($date)
	{
		$we = $this->getWeekend($date);
		
		if (isset($we))
			return $we->name.", ".$we->area;
		else
		    return null;
		
	}
	
	function getField($date)
	{
		return $this->form->getField("availability_".$date);
	}
	
	public function canChooseLeader()
	{
        return $this->get("canChooseLeader");
	}
}
