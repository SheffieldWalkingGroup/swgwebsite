<?php 
  defined( '_JEXEC' ) or die( 'Restricted access' );
  $currentPage = JFactory::getApplication()->getMenu()->getActive();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" 
   xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
    <head>
        <jdoc:include type="head" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
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
                
                <?php if ($currentPage->alias == "homepage"): ?>
                <div class="intro">
                    <jdoc:include type="modules" name="right" style="xhtml" />
                </div>
                <?php endif ?>
                
                <jdoc:include type="modules" name="precontent" style="xhtml" />
                
                <jdoc:include type="component" />
                
                <div class="boxes">
                    <jdoc:include type="modules" name="boxes" style="xhtml" />
                    <div class="clear">&nbsp;</div>
                </div>
                <div class="clear">&nbsp;</div>
                </div>
            </div>
        </div>
    </body>
</html>