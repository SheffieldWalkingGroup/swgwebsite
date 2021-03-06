<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkProgramme', JPATH_BASE."/swg/Models/WalkProgramme.php");
JFactory::getDocument()->addScript(JURI::base().'components/com_swg_leaderutils/model/field/availability.js');

class JFormFieldAvailability extends JFormField
{

    protected $type = 'Availability';
    
    /** @var WalkProgramme */
    private $programme;
    
    public function setProgramme(WalkProgramme $p)
    {
        $this->programme = $p;
    }
    
    public function getProgramme()
    {
        return $this->programme;
    }
    
    public function getInput()
    {
        // TODO: Configurable, no idea why we can't just use setProgramme. Joomla probably resets the object to stop you doing useful shit
        $this->programme = WalkProgramme::get(WalkProgramme::getNextProgrammeId());
        if (!isset($this->programme))
        {
            throw new LogicException("Walk programme was not specified: call setProgramme() first");
        }
        // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
        $start = $this->programme->startDate;
        $end   = $this->programme->endDate;
        
        // Start at the Monday before or on the date the programme starts
        if ($start->format('N') == 1) {
            $date = $start;
        } else {
            $date = clone($this->programme->startDate);
            $date->modify('last Monday');
        }
        $day = new DateInterval('P1D');
        
        // Look up weekends during this month
        $weekendFactory = new WeekendFactory();
        $weekendFactory->startDate = $start->format('U');
        $weekendFactory->endDate = $end->format('U');
        $weekends = $weekendFactory->get();
        
        $html = <<<HTM
<table class='availabilitycalendar'>
    <tbody>
HTM;
        $html .= $this->monthHeaderRow($date);
        $bhFinder = BankHolidayService::getInstance();
        do {
            $html .= "<tr>";
            $html .= "<td><input id='".$this->id."_wc_".$date->format('Y-m-d')."' type='checkbox' checked='checked'/></td>";
            
            // Week loop
            $notes = array();
            for ($i=0; $i<7; $i++) {
                if ($date <= $end) {
                    // Check for notes to apply to this week
                    if ($bhFinder->isBankHoliday($date)) {
                        $notes[] = $bhFinder->getBankHolidayName($date);
                    }
                    foreach ($weekends as $we) {
                        if ($we->start == $date->format('U'))
                            $notes[] = "Weekend away: ".$we->name;
                    }
                    if ($this->isNewMembersWalkWeekend($date)) {
                        $notes[] = "New members walk normally happens weekend after pub meet";
                    }

                    $html .= "<td>".$this->makeInputField($date)."
                    <label onclick='console.log(e)' for='".$this->id."_".$date->format('Y-m-d')."'>".$date->format('d')."</label>
                    </td>";
                } else {
                    $html .= "<td>&nbsp;</td>";
                }
                $date->add($day);
                if ($date->format('d') == 1 && $date < $end) {
                    // New month header
                    for ($j=$i; $j<6; $j++) {
                        $html .= "<td>&nbsp;</td>"; // Clear row
                    }
                    $html .= "<td class='notes'>".implode('<br>', $notes)."</td>";
                    $html .= $this->monthHeaderRow($date);
                    $html .= "<td><input id='".$this->id."_wc_".$date->format('Y-m-d')."' type='checkbox' checked='checked'/></td>";
                    for ($j=0; $j<=$i; $j++) {
                        $html .= "<td>&nbsp;</td>"; // Reset position
                    }
                }
            }
            $html .= "<td class='notes'>".implode('<br>', $notes)."</td>";
            $html .= "</tr>";
        } while ($date < $end);
$html .= "</tbody></table>";
        return $html;
    }
    
    private function makeInputField(DateTime $date)
    {
        $bhFinder = BankHolidayService::getInstance();
        $isBH = ($bhFinder->isBankHoliday($date));
        
        $hiddenField = array(
            'id'                => $this->id.'_'.$date->format('Y-m-d').'_real',
            'type'              => 'hidden',
            'name'              => $this->name.'['.$date->format('Y-m-d').']',
            'data-dow'          => $date->format('N'),
            'data-bankholiday'  => ($isBH ? htmlentities($bhFinder->getBankHolidayName($date), ENT_QUOTES) : ""),
            'value'             => (isset($this->data) ? $this->data->getAvailabilityForDate($date) : 0)
        );
        
        $checkbox = array(
            'id'                => $this->id.'_'.$date->format('Y-m-d'),
            'type'              => 'checkbox',
            'onclick'           => 'triState(this)',
            'data-dow'          => $date->format('N'),
            'data-bankholiday'  => ($isBH ? htmlentities($bhFinder->getBankHolidayName($date), ENT_QUOTES) : ''),
        );
        
        if (!empty($this->value)) {
            $availability = $this->value[$date->format('Y-m-d')];
            if (!isset($availability)) {
                $availability = 0;
            }
            
            $hiddenField['value'] = $availability;
        } else {
            if ($date->format('N') == 6 || $date->format('N') == 7 || $isBH) {
                $hiddenField['value'] = WalkProposal::AVAILABLE;
            }
        }
        
        array_walk($hiddenField, function(&$value, $key) {
            $value = $key.'="'.$value.'"';
        });
        array_walk($checkbox, function(&$value, $key) {
            $value = $key.'="'.$value.'"';
        });
        
        return '<input '.implode(' ', $hiddenField).'><input '.implode(' ', $checkbox).'>';
    }
    
    /**
     * Check whether the current day is on the weekend after the pub meet
     *
     * Pub meet is currently set as the first Tuesday of the month, which may not be the case around bank holidays
     *
     * @param DateTime $date Date to check // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     *
     * @return boolean True if new members walk weekend
     */
    private function isNewMembersWalkWeekend(DateTime $date)
    {
        $firstTuesday = new DateTime($date->format('Y-m-01'));
        $firstTuesday->modify('First tuesday');
        return ($firstTuesday->add(new DateInterval('P4D')) == $date); // Use Saturday to represent the weekend
    }
    
    private function monthHeaderRow(DateTime $date)
    {
    $month = $date->format('F');
        return <<<HTM
        <tr class='month'>
            <th rowspan="2">
                Available this week
            </th>
            <th colspan="8">
                {$month}
            </th>
        </tr>
        <tr class='days'>
            <th>Mon</th>
            <th>Tue</th>
            <th>Wed</th>
            <th>Thu</th>
            <th>Fri</th>
            <th>Sat</th>
            <th>Sun</th>
            <th>Notes</th>
        </tr>
HTM;
    }
}
