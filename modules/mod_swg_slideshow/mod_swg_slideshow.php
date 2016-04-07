<?php
// No direct access
defined('_JEXEC') or die;
// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

$helper = new ModSWG_SlideshowHelper();
$startImage = $helper->getStartImage();
$imageSet = $helper->getImages();

$document = JFactory::getDocument();
$document->addScript("modules/mod_swg_slideshow/script/slideshow.js","text/javascript");
$slideshowConfig = array(
	"imageSet" => $imageSet,
	"startImage" => $startImage['image'],
	"startIndex" => $startImage['index'],
);
$document->addScriptDeclaration("var mod_swg_slideshow_config = '".json_encode($slideshowConfig)."';"); // TODO: unique variable name so we can have multiple slideshows
require JModuleHelper::getLayoutPath("mod_swg_slideshow");