<?php
/**
 * @version		2.2
 * @package		Simple Image Gallery (plugin)
 * @author    JoomlaWorks - http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2011 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.plugin.plugin' );

class plgContentJw_simpleImageGallery extends JPlugin {

  // JoomlaWorks reference parameters
	var $plg_name								= "jw_simpleImageGallery";
	var $plg_copyrights_start		= "\n\n<!-- JoomlaWorks \"Simple Image Gallery\" Plugin (v2.2) starts here -->\n";
	var $plg_copyrights_end			= "\n<!-- JoomlaWorks \"Simple Image Gallery\" Plugin (v2.2) ends here -->\n\n";
	var $plg_tag								= "gallery";

	function plgContentJw_simpleImageGallery( &$subject, $params ){
		parent::__construct( $subject, $params );
	}

	function onContentPrepare($context, &$row, &$params, $page = 0){

		// API
		jimport('joomla.filesystem.file');
		$mainframe	= &JFactory::getApplication();
		$document 	= &JFactory::getDocument();

		// Requests
		$option 		= JRequest::getCmd('option');
		$view 			= JRequest::getCmd('view');
		$layout 		= JRequest::getCmd('layout');
		$page 			= JRequest::getCmd('page');
		$secid 			= JRequest::getInt('secid');
		$catid 			= JRequest::getInt('catid');
		$itemid 		= JRequest::getInt('Itemid');
		if(!$itemid) $itemid = 999999;

		// Assign paths
		$sitePath = JPATH_SITE;
		$siteUrl  = JURI::base(true);

		// Check if plugin is enabled
		if(JPluginHelper::isEnabled('content',$this->plg_name)==false) return;

		// Bail out if the page is not HTML
		if(JRequest::getCmd('format')!='html' && JRequest::getCmd('format')!='') return;

		// Load the plugin language file the proper way
		JPlugin::loadLanguage('plg_content_'.$this->plg_name, JPATH_ADMINISTRATOR);

		// simple performance check to determine whether plugin should process further
		if(JString::strpos($row->text, $this->plg_tag) === false) return;

		// expression to search for
		$regex = "#{".$this->plg_tag."}(.*?){/".$this->plg_tag."}#s";

		// find all instances of the plugin and put them in $matches
		preg_match_all($regex,$row->text,$matches);

		// Number of plugins
		$count = count($matches[0]);

		// Plugin only processes if there are any instances of the plugin in the text
		if(!$count) return;

		// Check for basic requirements
		if(!extension_loaded('gd') && !function_exists('gd_info')) {
			JError::raiseNotice('', JText::_('JW_SIG_NGD'));
		}
		if(!is_writable($sitePath.DS.'cache')){
			JError::raiseNotice('', JText::_('JW_SIG_CFU'));
		}



		// ----------------------------------- Get plugin parameters -----------------------------------

		$galleries_rootfolder 	= $this->params->get('galleries_rootfolder','images');
		$thb_width 							= (int) $this->params->get('thb_width', 200);
		$thb_height 						= (int) $this->params->get('thb_height', 160);
		$smartResize 						= $this->params->get('smartResize', 1);
		$jpg_quality 						= (int) $this->params->get('jpg_quality', 80);
		$galleryMessages				= $this->params->get('galleryMessages', 1);
		$cache_expire_time	 		= (int) $this->params->get('cache_expire_time',120) * 60; // Cache expiration time in minutes

		// Advanced
		$memoryLimit						= (int) $this->params->get('memoryLimit');
		if($memoryLimit) ini_set("memory_limit",$memoryLimit."M");

		// Preset
		$thb_template 					= $this->params->get('thb_template', 'Polaroids');
		$cacheFolder						= 'cache/jw_simpleImageGallery';

		// Other assignments
		$transparent						= $siteUrl."/plugins/content/".$this->plg_name."/".$this->plg_name."/includes/images/transparent.gif";

		// Includes
		require_once(dirname(__FILE__).DS.$this->plg_name.DS.'includes'.DS.'helper.php');



		// ----------------------------------- Head tag includes -----------------------------------
		$pluginCSS 		= SimpleImageGalleryHelper::getTemplatePath($this->plg_name,'css/template.css',$thb_template);
		$pluginCSS 		= $pluginCSS->http;

		$pluginCSSie6 = SimpleImageGalleryHelper::getTemplatePath($this->plg_name,'css/template_ie6.css',$thb_template);
		$pluginCSSie6 = $pluginCSSie6->http;

		$pluginCSSie7 = SimpleImageGalleryHelper::getTemplatePath($this->plg_name,'css/template_ie7.css',$thb_template);
		$pluginCSSie7 = $pluginCSSie7->http;

		$headIncludes = '
		'.JHTML::_('behavior.framework').'
		<script type="text/javascript" src="'.$siteUrl.'/plugins/content/'.$this->plg_name.'/'.$this->plg_name.'/includes/jquery/jquery-1.4.4.min.js"></script>
		<script type="text/javascript" src="'.$siteUrl.'/plugins/content/'.$this->plg_name.'/'.$this->plg_name.'/includes/slimbox-2.04/js/slimbox2.js"></script>
		<link rel="stylesheet" type="text/css" href="'.$siteUrl.'/plugins/content/'.$this->plg_name.'/'.$this->plg_name.'/includes/slimbox-2.04/css/slimbox2.css" />
		<link rel="stylesheet" type="text/css" href="'.$pluginCSS.'" />
		<!--[if lte IE 6]>
		<link rel="stylesheet" type="text/css" href="'.$pluginCSSie6.'" />
		<![endif]-->
		<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" href="'.$pluginCSSie7.'" />
		<![endif]-->
		';



		// ----------------------------------- Prepare the output -----------------------------------

		// Process plugin tags
		if(preg_match_all($regex, $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
			// start the replace loop
			foreach ($matches[0] as $key => $match) {

				$tagcontent 		= preg_replace("/{.+?}/", "", $match);
				$tagparams 			= explode(':',$tagcontent);
				$galleryFolder 	= $tagparams[0];

				// Gallery specific
				$srcimgfolder = $galleries_rootfolder.'/'.$galleryFolder.'/';
				$galleryID = substr(md5($key.$srcimgfolder),1,10); // create a unique 8-digit identifier for each gallery

				$gallery = SimpleImageGalleryHelper::renderGallery($srcimgfolder,$cacheFolder,$thb_width,$thb_height,$smartResize,$jpg_quality,$cache_expire_time);

				if(!$gallery){
					JError::raiseNotice('',JText::_('JW_SIG_PRG'));
					return;
				}

				// Fetch the template
				ob_start();
				$templatePath = SimpleImageGalleryHelper::getTemplatePath($this->plg_name,'default.php',$thb_template);
				$templatePath = $templatePath->file;
				include($templatePath);
				$getTemplate = $this->plg_copyrights_start.ob_get_contents().$this->plg_copyrights_end;
				ob_end_clean();

				// Output
				$plg_html = $getTemplate;

				// Do the replace
				$row->text = preg_replace( "#{".$this->plg_tag."}".$tagcontent."{/".$this->plg_tag."}#s", $plg_html , $row->text );

			} // end foreach

		} // end if

		// Append head includes, but not when we're outputing raw content in K2
		if(JRequest::getCmd('format')=='' || JRequest::getCmd('format')=='html'){
			SimpleImageGalleryHelper::loadHeadIncludes($this->plg_copyrights_start.$headIncludes.$this->plg_copyrights_end);
		}

	}

} // End class
