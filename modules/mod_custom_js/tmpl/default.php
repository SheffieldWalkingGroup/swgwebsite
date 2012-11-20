<?php
/**
 * @version		$Id: default.php 20196 2011-01-09 02:40:25Z ian $
 * @package		Joomla.Site
 * @subpackage	mod_custom
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
?>
<?php 
if ($custom_js){
	if (strpos($custom_js,'<')===0) {
//	echo "<script type=\"text/javascript\"><!--\r\n";
	//echo str_replace('&quot;','"',$custom_js);
	echo $custom_js;
//	echo "'//--> \r\n"
//	. "</script>\r\n"
//	. "<script type=\"text/javascript\" src=\"http://pagead2.googlesyndication.com/pagead/show_ads.js\">\r\n"
//	. "</script>\r\n";
	} else {
		echo "<script type=\"text/javascript\"><!--\r\n";
		echo $custom_js;
		echo "'//--> \r\n"
		. "</script>\r\n";
	}
}

if ($custom_js_src){
	echo "<script type=\"text/javascript\" src=\"".$custom_js_src."\">\r\n"
	. "</script>\r\n";
}
?>

