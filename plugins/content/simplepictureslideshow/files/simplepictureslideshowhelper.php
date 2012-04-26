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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

// Helper Class
class plgContentSimplepictureslideshowHelper{

		// replace plugin-calls and try to remove enclosing paragraphs
    public static function beReplaceCall( $myneedle, $myreplacement, $myhaystack) {

		/* parameters
    $myneedle				the string to replace
    $myreplacement	what to insert
    $myhaystack			where to search
    */

			$myneedle = preg_quote($myneedle, '#');
			if(preg_match("#<p>(\s|<br />)*".$myneedle."(\s|<br />)*</p>#s", $myhaystack)>=1){
				$myhaystack = preg_replace( "#<p>(\s|<br />)*".$myneedle."(\s|<br />)*</p>#s", $myreplacement , $myhaystack ,1);
			}
			else{
				$myhaystack = preg_replace( "#".$myneedle."#s", $myreplacement , $myhaystack ,1);
			}
			return $myhaystack;
		}

		// sort image array according the set sort order
    public static function beSortImages( $myarray, $myorder) { //20090828

		/* parameters
    $myarray			the array to sort
			($images[] = array('filename' => VALUE, 'flastmod' => VALUE);)
    $myorder			the sort order
    */

			unset($theage); //unset temporary array
			unset($thename); //unset temporary array
			switch ($myorder) {
				case 1: //alphabetic descending caseinsensitive
					foreach ($myarray as $key => $val) {$thename[$key]=substr(strtolower($val['filename']),0,-4);}
					array_multisort($thename, SORT_DESC, $myarray);
					break;
				case 2: //old to new
					foreach ($myarray as $key => $val) {$theage[$key]=$val['flastmod'];}
					array_multisort($theage, SORT_ASC, $myarray);
					break;
				case 3: //new to old
					foreach ($myarray as $key => $val) {$theage[$key]=$val['flastmod'];}
					array_multisort($theage, SORT_DESC, $myarray);
					break;
				case 4: //random
					shuffle($myarray);
					break;
				default: //alphabetic ascending caseinsensitive
					foreach ($myarray as $key => $val) {$thename[$key]=substr(strtolower($val['filename']),0,-4);}
					array_multisort($thename, SORT_ASC, $myarray);
					break;
			}
			return $myarray;
		}

		// replace quotes
    public static function beKickQuotes($e) { //20090828

		/* parameters
    $e	string
    */

			$e = str_replace('"', '\\"', $e);
			$e = str_replace("'", "&#39;", $e);
			return $e;
		}

		// check for mb_strtolower and use it if available
    public static function beStrtolower($mystring) {

		/* parameters
    $mystring				the string to convert
    */
		$mystring=(plgContentSimplepictureslideshowHelper::beIs_utf8($mystring))?($mystring):(utf8_encode($mystring));

		$mystring=(function_exists('mb_strtolower'))?(mb_strtolower($mystring)):(strtolower($mystring));
		return $mystring;
		}

		// Returns true if $string is valid UTF-8 and false otherwise.
		public static function beIs_utf8($string) {
    // From http://w3.org/International/questions/qa-forms-utf-8.html
    return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);
		}

}
?>