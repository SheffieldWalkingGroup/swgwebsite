<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

/**
 * One column for each day at the weekend/bank holiday, plus one column for each week.
 * i.e. 3 columns per full week
 */
$startDate = new DateTime('@'.$this->programme->startDate);
$endDate = new DateTime('@'.$this->programme->endDate);

$bankHoliday = BankHolidayService::getInstance(); 
$date = clone $startDate;
$columns = $this->proposals; // TODO: Will be populated by model

?>
<h1>Walk proposals for <?php echo $this->programme->title; ?></h1>
<table class="compileprogramme">
    <thead>
        <tr>
            <th>Date</th>
            <?php foreach ($columns as $proposal): ?>
                <th>
                    <p class="walkname"><?php echo $proposal->walk->name; ?></p>
                    <p class="details"><?php echo $proposal->walk->miles;?> miles, <?php echo $proposal->walk->distanceGrade.$proposal->walk->difficultyGrade; ?></p>
                    <p class="leader">Leader: <?php echo $proposal->leader->forename.' '.$proposal->leader->surname ?></p>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php while ($date <= $endDate): ?>
            <tr class="<?php if ($date->format('N') == 6):?>saturday weekend<?php elseif ($date->format('N') == 7):?>sunday weekend<?php elseif ($bankHoliday->isBankHoliday($date)):?>bankholiday weekend<?php else: ?>weekday<?php endif;?>">
                <th>
                    <?php echo ($bankHoliday->isBankHoliday($date) ? $bankHoliday->getBankHolidayName($date) : $date->format('l jS F')) ?>
                </th>
                <?php foreach ($columns as $proposal): ?>
                    <td class="<?php $avail = $proposal->getAvailabilityForDate($date); if ( $avail == WalkProposal::NOT_AVAILABLE) : ?>not-available<?php elseif ($avail == WalkProposal::AVAILABLE) : ?>available<?php else:?>preferred available<?php endif;?>">&nbsp;</td>
                <?php endforeach; ?>
            </tr>
        <?php $date->add(new DateInterval('P1D')); endwhile ?>
    </tbody>
</table>


