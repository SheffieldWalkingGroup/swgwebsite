<?php // no direct access
defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<div class="profile">
	<p>Welcome, <?php echo $user->name;?></p>
	<ul class="stats">
		<?php if (isset($joindate)): ?>
			<li>Joined <?php echo strftime("%B %Y", $joindate); ?></li>
		<?php endif; ?>
		<li>Done <?php echo $numWalks;?> walks <?php if ($numWalks):?><span class="distance">(<?php echo $walkStats['sum_miles'];?> miles)</span><?php endif;?></li>
	</ul>
</div>
		
