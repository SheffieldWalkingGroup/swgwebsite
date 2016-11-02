<?php
/*
// "Simple Picture Slideshow" Plugin for Joomla 3.1 - Version 1.5.8
// License: GNU General Public License version 2 or later; see LICENSE.txt
// Author: Andreas Berger - andreas_berger@bretteleben.de
// Copyright (C) 2013 Andreas Berger - http://www.bretteleben.de. All rights reserved.
// Project page and Demo at http://www.bretteleben.de
// ***Last update: 2013-08-23***
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.form.formfield');//import the necessary class definition for formfield
class JFormFieldbexml extends JFormField {
	
	protected $type = 'bexml';

	var	$_name = 'Simple Picture Slideshow';
	var $_version = '1.5.8';

	protected function getInput(){
		$view =$this->element['view'];
		$lang = JFactory::getLanguage();
		$lang = $lang->getTag();

		switch ($view){

		case 'intro':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".$this->_name." ".JText::_('PLG_BESPS_VERSION').": ".$this->_version."</b><br />";
            $html.=JText::_('PLG_BESPS_SUPPORT').":&nbsp;";
            $html.="<a href='http://www.bretteleben.de' target='_blank'>www.bretteleben.de</a>";
            $html.="</div>";
		break;

		case 'slideshow':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_SLIDESHOW')."</b> - ".JText::_('PLG_BESPS_SLIDESHOW_GENERAL').".";
            $html.="</div>";
		break;

		case 'animation':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_ANIMATE')."</b> - ".JText::_('PLG_BESPS_ANIMATE_GENERAL')." <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/installation-and-usage-plugin.html' target='_blank'>".JText::_('PLG_BESPS_ANIMATE_GENERAL_HOWTO')."</a>).";
            $html.="</div>";
		break;

		case 'captions':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_CAPTIONS')."</b> - ".JText::_('PLG_BESPS_CAPTIONS_GENERAL')." <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/-anleitung-plugin-code.html' target='_blank'>".JText::_('PLG_BESPS_CAPTIONS_GENERAL_HOWTO')."</a>).";
            $html.="</div>";
		break;

		case 'links':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_LINKS')."</b> - ".JText::_('PLG_BESPS_LINKS_GENERAL')." <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/-anleitung-plugin-code.html' target='_blank'>".JText::_('PLG_BESPS_LINKS_GENERAL_HOWTO')."</a>).";
            $html.="</div>";
		break;

		case 'controls':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_CONTROLS')."</b> - ".JText::_('PLG_BESPS_CONTROLS_GENERAL')." <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/installation-and-usage-plugin.html' target='_blank'>".JText::_('PLG_BESPS_CONTROLS_GENERAL_HOWTO')."</a>).";
            $html.="</div>";
		break;

		default:
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".JText::_('PLG_BESPS_OTHERSETTINGS')."</b>";
            $html.="</div>";
		break;

		}
		return $html;
	}
}