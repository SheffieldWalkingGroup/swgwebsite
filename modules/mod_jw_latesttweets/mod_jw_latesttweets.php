<?php
/**
 * @version		2.3
 * @package		Latest Tweets (module) for Joomla! 2.5 & 3.x
 * @author		JoomlaWorks - http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2015 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// JoomlaWorks reference parameters
$mod_name               = "mod_jw_latesttweets";
$mod_copyrights_start   = "\n\n<!-- JoomlaWorks \"Latest Tweets\" Module (v2.3) starts here -->\n";
$mod_copyrights_end     = "\n<!-- JoomlaWorks \"Latest Tweets\" Module (v2.3) ends here -->\n\n";

// API
jimport('joomla.filesystem.file');
$mainframe = JFactory::getApplication();
$document = JFactory::getDocument();

// Assign paths
$sitePath = JPATH_SITE;
$siteUrl = substr(JURI::base(), 0, -1);

// Module parameters
$moduleclass_sfx	= $params->get('moduleclass_sfx','');
$ltCSSStyling		= (int) $params->get('ltCSSStyling',1);
$ltTimeout			= (int) $params->get('ltTimeout',2) * 1000;
$ltUsername			= strtolower(trim($params->get('ltUsername','joomlaworks')));
$ltKey				= $params->get('ltKey');
$ltCount			= (int) $params->get('ltCount',5);

// Output some Twitter API stuff to the document's head
$headScripts = "
	jwLatestTweets.ready(function(){
		jwLatestTweets.lang = {
			lessthanaminute: \"".JText::_('MOD_JW_LT_LESS_THAN_A_MINUTE_AGO')."\",
			minute: \"".JText::_('MOD_JW_LT_ABOUT_A_MINUTE_AGO')."\",
			minutes: \"".JText::_('MOD_JW_LT_MINUTES_AGO')."\",
			hour: \"".JText::_('MOD_JW_LT_ABOUT_AN_HOUR_AGO')."\",
			hours: \"".JText::_('MOD_JW_LT_HOURS_AGO')."\",
			day: \"".JText::_('MOD_JW_LT_1_DAY_AGO')."\",
			days: \"".JText::_('MOD_JW_LT_DAYS_AGO')."\"
		};
		jwLatestTweets.fetchTweets({
			screen_name: '".$ltUsername."',
			key: '".$ltKey."',
			count: '".$ltCount."',
			callback: 'jwLtCb".$module->id."',
			moduleID: 'ltUpdateId".$module->id."',
			timeout: ".$ltTimeout."
		});
	});
";

// Append JS to the document's head
$document->addScript($siteUrl.'/modules/mod_jw_latesttweets/includes/js/behaviour.js?v=2.3');
$document->addScriptDeclaration($headScripts);

// Append CSS to the document's head
if($ltCSSStyling) $document->addStyleSheet($siteUrl.'/modules/mod_jw_latesttweets/tmpl/css/style.css?v=2.3');

// Output content with template
echo $mod_copyrights_start;
require(JModuleHelper::getLayoutPath($mod_name,'default'));
echo $mod_copyrights_end;
