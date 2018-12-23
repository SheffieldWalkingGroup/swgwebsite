<?php
/**
 * @package		Joomla.Site
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
if (!isset($this->error)) {
	$this->error = JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	$this->debug = false;
}
//get language and direction
$doc = JFactory::getDocument();
$this->language = $doc->language;
$this->direction = $doc->direction;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" class="<?php echo $currentPage->alias; ?>"
   xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
    <head>
        <jdoc:include type="head" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
        <meta name="viewport" content="width=1016">
        <!--[if lt IE 9]>
            <script>
                document.createElement('header');
                document.createElement('nav');
                document.createElement('footer');
            </script>
        <![endif]-->
    </head>
    <body>
       <!-- TODO: Try to move the h1 tag to the top of the HTML. May require some absolute positioning, esp. of the right panel -->
        <div class="wrapper">
			<nav>
				<a href="/"><img src="/images/SWG_logo_2009_small.png" width="190" height="58" alt="SWG Logo" /></a>
				<div class="moduletable_menu">
					<h3>Escape to the Great Outdoors</h3>
					<ul class="menu">
						<li class="item-435"><a href="/">Home</a></li>
						<li class="item-468 parent"><a href="/walks">Walks</a></li>
						<li class="item-469 parent"><a href="/socials">Socials</a></li>
						<li class="item-483 parent"><a href="/weekends">Weekends Away</a></li>
						<li class="item-490 parent"><a href="/about-swg">About SWG</a></li>
						<li class="item-492 parent"><a href="/photos">Photos</a></li>
					</ul>
				</div>
			</nav>
			<div class="main">	
				<div class="errorimage">
					<h1><?php echo $this->error->getMessage(); ?></h1>
					<?php if (method_exists($this->error, "getSubHead")): ?>
						<h2><?php echo $this->error->getSubHead(); ?></h2>
					<?php endif; ?>
					<img src="/images/errors/error.jpg" width="766" height="374" alt="" />
				</div>
				
				<div class="errorinfo">
                    
                    <?php echo "<pre>".$this->error->getMessage() . ": ".$this->error->getFile().":".$this->error->getLine()."<br>".$this->error->getTraceAsString()."</pre>";?>
					<?php if (method_exists($this->error, "getDetails")): ?>
						<?php echo $this->error->getDetails(); ?>
					<?php else: ?>
						<p><strong><?php echo JText::_('JERROR_LAYOUT_NOT_ABLE_TO_VISIT'); ?></strong></p>
						<ol>
							<li><?php echo JText::_('JERROR_LAYOUT_AN_OUT_OF_DATE_BOOKMARK_FAVOURITE'); ?></li>
							<li><?php echo JText::_('JERROR_LAYOUT_SEARCH_ENGINE_OUT_OF_DATE_LISTING'); ?></li>
							<li><?php echo JText::_('JERROR_LAYOUT_MIS_TYPED_ADDRESS'); ?></li>
							<li><?php echo JText::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE'); ?></li>
							<li><?php echo JText::_('JERROR_LAYOUT_REQUESTED_RESOURCE_WAS_NOT_FOUND'); ?></li>
							<li><?php echo JText::_('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST'); ?></li>
						</ol>
					<?php endif; ?>
				</div>
				
				<div class="errorlinks">
					<p><strong><?php echo JText::_('JERROR_LAYOUT_PLEASE_TRY_ONE_OF_THE_FOLLOWING_PAGES'); ?></strong></p>

					<ul>
						<li><a href="<?php echo $this->baseurl; ?>/index.php" title="<?php echo JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE'); ?>"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></li>
					</ul>
				</div>
				
				<div class="errordebug">
					<p><?php echo JText::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR'); ?>.</p>
					<p><?php echo $this->error->getMessage(); ?></p>
					<p>
						<?php if ($this->debug) :
							echo $this->renderBacktrace();
						endif; ?>
					</p>
				</div>
				
			</div>
		</div>
    </body>
</html>
