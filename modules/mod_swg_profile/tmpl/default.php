<?php // no direct access
defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<div class="profile" id="swg_profile">
	<p>Welcome, <?php echo $user->name;?></p>
	<ul class="stats">
		<?php if (isset($joindate)): ?>
			<li>Joined <?php echo strftime("%B %Y", $joindate); ?></li>
		<?php endif; ?>
		<li class="walks">Done <span class="num"><?php echo $numWalks;?></span> walks <span class="distance"><?php if ($numWalks) echo "(".$walkStats['sum_miles']." miles)";?></span></li>
	</ul>
</div>
		
