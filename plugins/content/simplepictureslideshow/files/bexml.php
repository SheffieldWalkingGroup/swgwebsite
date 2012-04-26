<?php
/*
// "Simple Picture Slideshow" Plugin for Joomla 2.5 - Version 1.5.5
// License: GNU General Public License version 2 or later; see LICENSE.txt
// Author: Andreas Berger - andreas_berger@bretteleben.de
// Copyright (C) 2012 Andreas Berger - http://www.bretteleben.de. All rights reserved.
// Project page and Demo at http://www.bretteleben.de
// ***Last update: 2012-03-19***
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.form.formfield');//import the necessary class definition for formfield
class JFormFieldbexml extends JFormField {
	
	protected $type = 'bexml';

	var	$_name = 'Simple Picture Slideshow';
	var $_version = '1.5.5';

	protected function getInput(){
		$view =$this->element['view'];

		switch ($view){

		case 'intro':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>".$this->_name." Version: ".$this->_version."</b><br />";
            $html.="for support and updates visit:&nbsp;";
            $html.="<a href='http://www.bretteleben.de' target='_blank'>www.bretteleben.de</a>";
            $html.="</div>";
		break;

		case 'slideshow':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Slideshow</b> - Settings regarding the slideshow in general.";
            $html.="</div>";
		break;

		case 'animation':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Animation</b> - Settings regarding the animation (duration, steps, ... see <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/installation-and-usage-plugin.html' target='_blank'>Howto Plugin</a>).";
            $html.="</div>";
		break;

		case 'captions':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Captions</b> - Show title and/or text for images. Captions are set in the article using the code {besps_c}parameters{besps_c} (see <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/-anleitung-plugin-code.html' target='_blank'>Howto Plugin Code</a>).";
            $html.="</div>";
		break;

		case 'links':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Links</b> - Link images to any target (default or selective). Links are set in the article using the code {besps_l}parameters{besps_l} (see <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/-anleitung-plugin-code.html' target='_blank'>Howto Plugin Code</a>).";
            $html.="</div>";
		break;

		case 'controls':
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Controls</b> - Set which navigation elements you want to show, the labels, their sort order (see <a href='http://www.bretteleben.de/lang-en/joomla/simple-picture-slideshow/installation-and-usage-plugin.html' target='_blank'>Howto Plugin</a>).";
            $html.="</div>";
		break;

		default:
            $html="<div style='background-color:#c3d2e5;margin:0;padding:2px;display:block;clear:both;'>";
            $html.="<b>Other settings</b>";
            $html.="</div>";
		break;

		}
		return $html;
	}
}