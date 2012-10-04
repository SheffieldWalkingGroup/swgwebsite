<?php 
  defined( '_JEXEC' ) or die( 'Restricted access' );
  $currentPage = JFactory::getApplication()->getMenu()->getActive();
  
  JHTML::_('behavior.mootools');
  $document = &JFactory::getDocument();
  $document->addScript('/templates/swgpeter/script/template.js');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" class="<?php echo $currentPage->alias; ?>"
   xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
    <head>
        <jdoc:include type="head" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
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
                <jdoc:include type="modules" name="left" style="xhtml" />
            </nav>
            <div class="main <?php echo $currentPage->alias; ?>">
                
                <jdoc:include type="modules" name="top" style="xhtml" />
                
                <?php if (isset($currentPage->alias) && $currentPage->alias == "home"): ?>
                <div class="intro">
                    <jdoc:include type="modules" name="right" style="xhtml" />
                </div>
                <?php endif ?>
                
		<div class="precontent">
		  <jdoc:include type="modules" name="precontent" style="xhtml" />
		</div>
                
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
    </body>
</html>
