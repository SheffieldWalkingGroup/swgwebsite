<?php // no direct access
defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<div class="userstats">
	<table>
		<thead>
			<tr>
				<th></th>
				<th scope="col"><abbr title="Last 30 days">This month</abbr></th>
				<th scope="col"><abbr title="Last 90 days">Last 3 months</abbr></th>
				<th scope="col"><abbr title="Last 365 days">This year</abbr></th>
				<th scope="col">All time</th>
			</tr>
		</thead>
		<tbody>
			<?php $cols = array("month", "3month", "year", "alltime"); ?>
			<tr>
				<th scope="row">Walks done</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo $walks[$period]['count'].($walks[$period]['count'] ? " (".UnitConvert::DisplayDistance($walks[$period]['sum_distance'], UnitConvert::Metre, UnitConvert::Mile, false).")" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row"><abbr title="Day walks are those starting no later than 14:00 - i.e. not evening walks">All-day walks</abbr></th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo $daywalks[$period]['count'].($daywalks[$period]['count'] ? " (".UnitConvert::DisplayDistance($daywalks[$period]['sum_distance'], UnitConvert::Metre, UnitConvert::Mile, false).")" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Average walk</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo UnitConvert::DisplayDistance($walks[$period]['mean_distance'], UnitConvert::Metre, UnitConvert::Mile, false);?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Average day walk</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo UnitConvert::DisplayDistance($daywalks[$period]['mean_distance'], UnitConvert::Metre, UnitConvert::Mile, false);?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Walks led</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo $led[$period]['count']. ($led[$period]['count'] ? " (".$led[$period]['sum_miles']." miles)" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Socials attended</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo $socials[$period]['count'];?></td>
				<?php endforeach ?>
			</tr>
			<tr>
				<th scope="row">Weekends visited</th>
				<?php foreach ($cols as $period): ?>
					<td><?php echo $weekend[$period]['count'];?></td>
				<?php endforeach; ?>
			</tr>
			
	</table>
</div>