<?php 
  defined( '_JEXEC' ) or die( 'Restricted access' );
  $currentPage = JFactory::getApplication()->getMenu()->getActive();

  JHTML::_('behavior.framework', true);
  $document = &JFactory::getDocument();
  $document->addScript($this->baseurl . '/templates/swgpeter/script/template.js');
  JLoader::register('SWG', JPATH_SITE."/swg/swg.php");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" class="<?php echo $currentPage->alias; ?>"
   xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
    <head>
        <jdoc:include type="head" />
        <meta name="viewport" content="width=device-width, user-scalable=no">
        <meta name="format-detection" content="telephone=no">
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
        <style type="text/css">
			@import url("<?php echo $this->baseurl;?>/templates/<?php echo $this->template;?>/css/template.css");
			@import url("<?php echo $this->baseurl;?>/templates/<?php echo $this->template;?>/css/mobile.css") screen and (max-device-width:600px);
        </style>
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
                <a href="/" class="logo"><img src="/images/SWG_logo_2009_small.png" width="190" height="58" alt="SWG Logo" /></a>
                <a href="#menu" class="mobile menu-button">Menu</a>
                <div class="menu-wrap">
					<div class="mobile menu-tab-join"></div>
					<jdoc:include type="modules" name="left" style="xhtml" />
				</div>
            </nav>
            <div class="main <?php echo $currentPage->alias; ?>">
				<h1 class="mobile mobile-header">Sheffield Walking Group</h1>
                
                <jdoc:include type="modules" name="top" style="xhtml" />
                
                <?php if (isset($currentPage->alias) && $currentPage->alias == "home"): ?>
                <div class="intro">
                    <jdoc:include type="modules" name="right" style="xhtml" />
                </div>
                <?php endif ?>
                
		<div class="precontent">
		  <jdoc:include type="modules" name="precontent" style="xhtml" />
		</div>
                <jdoc:include type="message" />
                <jdoc:include type="component" />
                
                <div class="postcontent">
		  <jdoc:include type="modules" name="postcontent" style="xhtml" />
		</div>
                
                <div class="boxes">
                    <jdoc:include type="modules" name="boxes" style="xhtml" />
                    <div class="clear" style="clear:right;">&nbsp;</div>
                </div>
              </div>
            <footer>
                <div class="wrap">
                  <jdoc:include type="modules" name="footer" style="xhtml" />
                  <p class="footer">The&nbsp;Ramblers&nbsp;Association is a company limited by guarantee, registered in England&nbsp;and&nbsp;Wales. Company&nbsp;registration&nbsp;number:&nbsp;4458492.</p>
                  <p class="footer">Registered&nbsp;Charity in England&nbsp;and&nbsp;Wales number:&nbsp;1093577. Registered&nbsp;office: 2nd&nbsp;floor, Camelford&nbsp;House, 87-90&nbsp;Albert&nbsp;Embankment, London&nbsp;SE1&nbsp;7TW</p>
                </div>
                <div class="clear"></div>
            </footer>
            </div>
        </div>
        
        <!-- Google analytics -->
        <script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-816266-1', 'sheffieldwalkinggroup.org.uk');
ga('send', 'pageview');

		</script>
    </body>
</html>
