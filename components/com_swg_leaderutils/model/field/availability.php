<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkProgramme', JPATH_BASE."/swg/Models/WalkProgramme.php");
JFactory::getDocument()->addScript(JURI::base().'/components/com_swg_leaderutils/model/field/availability.js');

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
        $start = new DateTimeImmutable('@'.$this->programme->startDate);
        $end = new DateTimeImmutable('@'.$this->programme->endDate);
        
        // Start at the Monday before or on the date the programme starts
        if ($start->format('N') == 1) {
            $date = new DateTime($start);
        } else {
            $date = new DateTime('@'.strtotime('last Monday', $this->programme->startDate));
        }
        $day = new DateInterval('P1D');
        
        $html = <<<HTM
<table class='availabilitycalendar'>
    <tbody>
HTM;
        $html .= $this->monthHeaderRow($date);
        do {
            $html .= "<tr>";
            $html .= "<td><input id='".$this->id."_wc_".$date->format('Y-m-d')."' type='checkbox' checked='checked'/></td>";
            for ($i=0; $i<7; $i++) {
                if ($date <= $end) {
                    // TODO: initial values
                    $html .= "<td>
                    <input id='".$this->id."_".$date->format('Y-m-d')."_real' type='hidden' name='".$this->name."[".$date->format('Y-m-d')."]' value='0' data-dow='".$date->format('N')."'>
                    <input id='".$this->id."_".$date->format('Y-m-d')."' type='checkbox' onclick='triState(this)' data-dow='".$date->format('N')."'/>
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
                    $html .= "<td class='notes'>Notes here</td>";
                    $html .= $this->monthHeaderRow($date);
                    $html .= "<td><input id='".$this->id."_wc_".$date->format('Y-m-d')."' type='checkbox' checked='checked'/></td>";
                    for ($j=0; $j<=$i; $j++) {
                        $html .= "<td>&nbsp;</td>"; // Reset position
                    }
                }
            }
            $html .= "<td class='notes'>Notes here</td>";
            $html .= "</tr>";
        } while ($date < $end);
$html .= "</tbody></table>";
        return $html;
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
