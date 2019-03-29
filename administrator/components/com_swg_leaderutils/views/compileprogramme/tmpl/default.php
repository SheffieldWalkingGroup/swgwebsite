<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

/**
 * One column for each day at the weekend/bank holiday, plus one column for each week.
 * i.e. 3 columns per full week
 */
$startDate = $this->programme->startDate;
$endDate = $this->programme->endDate;

$bankHoliday = BankHolidayService::getInstance(); 
$date = clone $startDate;
$columns = $this->proposals; // Will be populated by model
$occupiedDates = array();

?>
<h1>Walk proposals for <?php echo $this->programme->title; ?></h1>
<form name="compileprogramme" action="<?php echo JRoute::_('index.php')?>" method="post" id="compileprogramme">
    <input type="hidden" name="view" value="compileprogramme">
    <input type="hidden" name="option" value="com_swg_leaderutils">
    <input type="hidden" name="task" value="compileprogramme.submit">
    <?php echo JHtml::_( 'form.token' ); ?>
    <table class="compileprogramme">
        <thead>
            <tr>
                <th>Date</th>
                <?php foreach ($columns as $proposal): ?>
                    <?php if ($proposal->isInProgramme()) $occupiedDates[] = date('Y-m-d', $proposal->walkInstance->start); ?>
                    <th class='proposal' id="walkcol_<?php echo $proposal->walk->id?>">
                        <p class="walkname"><?php echo $proposal->walk->name; ?></p>
                        <p class="details"><?php echo $proposal->walk->miles;?> miles, <?php echo $proposal->walk->distanceGrade.$proposal->walk->difficultyGrade; ?></p>
                        <p class="leader">Leader: <?php echo $proposal->leader->forename.' '.$proposal->leader->surname ?></p>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($date <= $endDate): ?>
                <tr class="<?php if ($date->format('N') == 6):?>saturday weekend<?php elseif ($date->format('N') == 7):?>sunday weekend<?php elseif ($bankHoliday->isBankHoliday($date)):?>bankholiday weekend<?php else: ?>weekday<?php endif;?><?php if (in_array($date->format('Y-m-d'), $occupiedDates)):?> occupied<?php endif;?>" id="daterow_<?php echo $date->format('Y-m-d');?>">
                    <th>
                        <?php echo ($bankHoliday->isBankHoliday($date) ? $bankHoliday->getBankHolidayName($date) : $date->format('D j M')) ?>
                    </th>
                    <?php foreach ($columns as $proposal): ?>
                        <td id="cell_<?php echo $date->format('Y-m-d').'_'.$proposal->walk->id;?>" class="<?php $avail = $proposal->getAvailabilityForDate($date); if ( $avail == WalkProposal::NOT_AVAILABLE) : ?>not-available<?php elseif ($avail == WalkProposal::AVAILABLE) : ?>available<?php else:?>preferred available<?php endif;?>">
                            <div>&nbsp;
                                <label>
                                    <input type='radio' name='proposals[<?php echo $proposal->id; ?>]' value='<?php echo $date->format('Y-m-d'); ?>'<?php if ($proposal->isInProgramme() && date('Y-m-d', $proposal->walkInstance->start) == $date->format('Y-m-d')):?> checked='checked'<?php endif; ?>/>
                                </label>
                            </div>
                        <?php /*if ($proposal->isInProgramme() && date('Y-m-d', $proposal->walkInstance->start) == $date->format('Y-m-d')): ?>
                            <div class='instance'>
                                <p class="walkname"><?php echo $proposal->walkInstance->name;?></p>
                                <p class="details"><?php echo $proposal->walkInstance->miles;?> miles, <?php echo $proposal->walkInstance->distanceGrade.$proposal->walkInstance->difficultyGrade;?></p>
                                <p class="leader">Leader: <?php echo $proposal->walkInstance->leader->forename.' '.$proposal->walkInstance->leader->surname;?></p>
                            </div>
                        <?php else:?>
                            &nbsp;
                        <?php endif;*/?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php $date->add(new DateInterval('P1D')); endwhile ?>
        </tbody>
    </table>
    <input type="submit" class="submit" value="Save" />
</form>


