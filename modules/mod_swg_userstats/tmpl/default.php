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
					<td id="stat_walks_<?php echo $period;?>"><?php echo $stats['walks'][$period]['count'].($stats['walks'][$period]['count'] ? " (".$stats['walks'][$period]['sum_distance'].")" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row"><abbr title="Day walks are those starting no later than 14:00 - i.e. not evening walks. This does include morning or afternoon only walks.">Day walks</abbr></th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_daywalks_<?php echo $period;?>"><?php echo $stats['daywalks'][$period]['count'].($stats['daywalks'][$period]['count'] ? " (".$stats['daywalks'][$period]['sum_distance'].")" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Average walk</th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_avgwalk_<?php echo $period;?>"><?php echo $stats['walks'][$period]['mean_distance'];?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Average day walk</th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_avgdaywalk_<?php echo $period;?>"><?php echo $stats['daywalks'][$period]['mean_distance']; ?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Walks led</th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_walksled_<?php echo $period;?>"><?php echo $stats['led'][$period]['count']. ($stats['led'][$period]['count'] ? " (".$stats['led'][$period]['sum_distance'].")" : "");?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<th scope="row">Socials attended</th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_socials_<?php echo $period;?>"><?php echo $stats['socials'][$period]['count'];?></td>
				<?php endforeach ?>
			</tr>
			<tr>
				<th scope="row">Weekends visited</th>
				<?php foreach ($cols as $period): ?>
					<td id="stat_weekends_<?php echo $period;?>"><?php echo $stats['weekends'][$period]['count'];?></td>
				<?php endforeach; ?>
			</tr>
			
	</table>
</div>