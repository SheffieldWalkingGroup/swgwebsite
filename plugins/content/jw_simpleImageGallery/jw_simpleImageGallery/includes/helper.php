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

class SimpleImageGalleryHelper {

	function renderGallery($srcimgfolder,$cacheFolder,$thb_width,$thb_height,$smartResize,$jpg_quality,$cache_expire_time){

		// API
		jimport('joomla.filesystem.folder');

		// Path assignment
    $sitePath = JPATH_SITE.'/';
    $siteUrl  = JURI::base(true).'/';

		// Internal parameters
		$prefix	= "jwsig_cache_";

		// Set the cache folder
		$cacheFolderPath = $sitePath.DS.str_replace('/',DS,$cacheFolder);
		if(file_exists($cacheFolderPath) && is_dir($cacheFolderPath)){
			// all OK
		} else {
			mkdir($cacheFolderPath);
		}

		// Check if the source folder exists and read it
		$srcFolder = JFolder::files($sitePath.$srcimgfolder);

		// Proceed if the folder is OK or fail silently
		if(!$srcFolder) return;

		// Loop through the source folder for images
		$fileTypes = array('jpg','jpeg','gif','png'); // Create an array of file types
		$found = array(); // Create an array for matching files
		foreach($srcFolder as $srcImage){
			$fileInfo = pathinfo($srcImage);
			if(array_key_exists('extension', $fileInfo) && in_array(strtolower($fileInfo['extension']),$fileTypes)){
				$found[] = $srcImage;
			}
		}

		// Bail out if there are no images found
		if(count($found)==0) return;

		// Sort the images
		sort($found);

		// Initiate array to hold gallery
		$gallery = array();

		// Loop through the image file list
		foreach($found as $key=>$filename){

			// Determing thumb image filename
			if(strtolower(substr($filename,-4,4))=='jpeg'){
				$thumbfilename = substr($filename,0,-4).'jpg';
			} elseif(strtolower(substr($filename,-3,3))=='gif' || strtolower(substr($filename,-3,3))=='png' || strtolower(substr($filename,-3,3))=='jpg'){
				$thumbfilename = substr($filename,0,-3).'jpg';
			}

			// Object to hold each image elements
			$gallery[$key] = new JObject;

			// Assign source image and path to a variable
			$original = $sitePath.$srcimgfolder.$filename;

			// Check if thumb image exists already
			$thumbimage = $sitePath.str_replace('/',DS,$cacheFolder).DS.$prefix.substr(md5($srcimgfolder),1,10).'_'.strtolower($thumbfilename);

			if(file_exists($thumbimage) && is_readable($thumbimage) && (filemtime($thumbimage)+$cache_expire_time) > time()){

				// do nothing

			} else {

				// Otherwise create the thumb image

				// begin by getting the details of the original
				list($width, $height, $type) = getimagesize($original);

				// strip the extension off the image filename (case insensitive)
				$imagetypes = array('/\.gif$/i', '/\.jpg$/i', '/\.jpeg$/i', '/\.png$/i');
				$name = preg_replace($imagetypes, '', basename($original));

				// create an image resource for the original
				switch($type){
					case 1:
						$source = @ imagecreatefromgif($original);
						/*
						if(!$source){
							$error = JText::_('GIF images cannot be processed by this server. Please use JPEG or PNG images.');
						}
						*/
						break;
					case 2:
						$source = imagecreatefromjpeg($original);
						break;
					case 3:
						$source = imagecreatefrompng($original);
						break;
					default:
						$source = NULL;
						//$error = JText::_('Cannot identify file type!');
				}

				// Bail out if the image resource is not OK
				if(!$source) return;

				// calculate thumbnails
				$thumbnail = SimpleImageGalleryHelper::thumbDimCalc($width,$height,$thb_width,$thb_height,$smartResize);

				$thumb_width = $thumbnail['width'];
				$thumb_height = $thumbnail['height'];

				// create an image resource for the thumbnail
				$thumb = imagecreatetruecolor($thumb_width, $thumb_height);

				// create the resized copy
				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

				// save the resized copy
				$thumbname = str_replace('/',DS,$cacheFolder).DS.$prefix.substr(md5($srcimgfolder),1,10).'_'.strtolower($name);

				// convert all thumbs to .jpg
				$success = imagejpeg($thumb, $sitePath.$thumbname.'.jpg', $jpg_quality);

				// Bail out if there is a problem in the GD conversion
				if(!$success) return;

				// remove the image resources from memory
				imagedestroy($source);
				imagedestroy($thumb);

			}

			// Assemble the image elements
			$gallery[$key]->filename = $filename;
			$gallery[$key]->sourceImageFilePath = $siteUrl.$srcimgfolder.SimpleImageGalleryHelper::replaceWhiteSpace($filename);
			$gallery[$key]->thumbImageFilePath = $siteUrl.$cacheFolder.'/'.$prefix.substr(md5($srcimgfolder),1,10).'_'.strtolower(SimpleImageGalleryHelper::replaceWhiteSpace($thumbfilename));

		} // foreach loop

		// OUTPUT
		return $gallery;

	}



	/* ------------------ Helper Functions ------------------ */

	// Calculate thumbnail dimensions
	function thumbDimCalc($width,$height,$thb_width,$thb_height,$smartResize){

		if($smartResize){

			// thumb ratio bigger that container ratio
			if($width/$height > $thb_width/$thb_height){
				// wide containers
				if($thb_width>=$thb_height){
					// wide thumbs
					if($width > $height){ $thumb_width = $thb_height*$width/$height; $thumb_height = $thb_height; }
					// high thumbs
					else { $thumb_width = $thb_height*$width/$height; $thumb_height = $thb_height; }
				// high containers
				} else {
					// wide thumbs
					if($width > $height){ $thumb_width = $thb_height*$width/$height; $thumb_height = $thb_height; }
					// high thumbs
					else { $thumb_width = $thb_height*$width/$height; $thumb_height = $thb_height; }
				}
			} else {
				// wide containers
				if($thb_width>=$thb_height){
					// wide thumbs
					if($width > $height){ $thumb_width = $thb_width; $thumb_height = $thb_width*$height/$width; }
					// high thumbs
					else { $thumb_width = $thb_width; $thumb_height = $thb_width*$height/$width; }
				// high containers
				} else {
					// wide thumbs
					if($width > $height){ $thumb_width = $thb_height*$width/$height; $thumb_height = $thb_height; }
					// high thumbs
					else { $thumb_width = $thb_width; $thumb_height = $thb_width*$height/$width; }
				}
			}

		} else {

			if($width > $height){
				$thumb_width = $thb_width;
				$thumb_height = $thb_width*$height/$width;
			} elseif($width < $height){
				$thumb_width = $thb_height*$width/$height;
				$thumb_height = $thb_height;
			} else {
				$thumb_width = $thb_width;
				$thumb_height = $thb_height;
			}

		}

		$thumbnail = array();
		$thumbnail['width'] = round($thumb_width);
		$thumbnail['height'] = round($thumb_height);

		return $thumbnail;

	}

	// Load Includes
	function loadHeadIncludes($headIncludes){
		global $loadSIGIncludes;
		$document = &JFactory::getDocument();
		if(!$loadSIGIncludes){
			$loadSIGIncludes=1;
			$document->addCustomTag($headIncludes);
		}
	}

	// Load Module Position
	function loadModulePosition($position,$style=''){
		$document	= &JFactory::getDocument();
		$renderer	= $document->loadRenderer('module');
		$params		= array('style'=>$style);

		$contents = '';
		foreach (JModuleHelper::getModules($position) as $mod){
			$contents .= $renderer->render($mod, $params);
		}
		return $contents;
	}

	// Word Limiter
	function wordLimiter($str,$limit=100,$end_char='&#8230;'){
		if (trim($str) == '') return $str;
		$str = strip_tags($str);
		preg_match('/\s*(?:\S*\s*){'. (int) $limit .'}/', $str, $matches);
		if (strlen($matches[0]) == strlen($str)) $end_char = '';
		return rtrim($matches[0]).$end_char;
	}

	// Path overrides
	function getTemplatePath($pluginName,$file,$tmpl){
	
		$mainframe = &JFactory::getApplication();
		$p = new JObject;
		$pluginGroup = 'content';

		if(file_exists(JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.$pluginName.DS.$tmpl.DS.str_replace('/',DS,$file))){
			$p->file = JPATH_SITE.DS.'templates'.DS.$mainframe->getTemplate().DS.'html'.DS.$pluginName.DS.$tmpl.DS.$file;
			$p->http = JURI::base(true)."/templates/".$mainframe->getTemplate()."/html/{$pluginName}/{$tmpl}/{$file}";
		} else {
			if(version_compare(JVERSION,'1.6.0','ge')) {
				// Joomla! 1.6
				$p->file = JPATH_SITE.DS.'plugins'.DS.$pluginGroup.DS.$pluginName.DS.$pluginName.DS.'tmpl'.DS.$tmpl.DS.$file;
				$p->http = JURI::base(true)."/plugins/{$pluginGroup}/{$pluginName}/{$pluginName}/tmpl/{$tmpl}/{$file}";
			} else {
				// Joomla! 1.5
				$p->file = JPATH_SITE.DS.'plugins'.DS.$pluginGroup.DS.$pluginName.DS.'tmpl'.DS.$tmpl.DS.$file;
				$p->http = JURI::base(true)."/plugins/{$pluginGroup}/{$pluginName}/tmpl/{$tmpl}/{$file}";
			}
		}
		return $p;
	}

	// Entity replacements
	function replaceHtml($text_to_parse){
		$source_html = array("&","\"","'","<",">","\r","\t","\n");
		$replacement_html = array("&amp;","&quot;","&#039;","&lt;","&gt;","","","");
		return str_replace($source_html,$replacement_html,$text_to_parse);
	}

	// Replace white space
	function replaceWhiteSpace($text_to_parse){
		$source_html = array(" ");
		$replacement_html = array("%20");
		return str_replace($source_html,$replacement_html,$text_to_parse);
	}

} // End class
