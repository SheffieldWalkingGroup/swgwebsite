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

// Import library dependencies
jimport('joomla.plugin.plugin');

class plgContentSimplepictureslideshow extends JPlugin {

	public $bespscounter = 0;//substitute article id

	public function onContentPrepare($context, &$article, &$params, $limitstart) {

		// checking
		$document=JFactory::getDocument();   
		if($document->getType() != 'html') {
			return;
		}
		if (!isset($article->text)||!preg_match("#{besps}(.*?){/besps}#s", $article->text) ) {
			return;
		}

		// paths
		$path_absolute = 	JPATH_SITE;
		$path_site = 			JURI :: base(true);
		if(substr($path_site, -1)=="/") $path_site = substr($path_site, 0, -1);
		$path_imgroot = '/images/'; 																			// default image root folder
		$path_ctrls 	= '/images/besps_buttons/'; 												// button folder
		$path_plugin 	= '/plugins/content/simplepictureslideshow/files/'; // path to plugin folder
		$file_css 		= 'besps.css'; 																			// default stylesheet
		$file_js 			= 'besps.js'; 																			// default JavaScript

		// import helper
    JLoader::import( 'simplepictureslideshowhelper', dirname( __FILE__ ).'/files' );
		
//captions
		if (preg_match_all("#{besps_c}(.*?){/besps_c}#s", $article->text, $matches, PREG_PATTERN_ORDER) > 0) {
			foreach ($matches[0] as $match) {
				$_raw_cap_ = preg_replace("/{.+?}/", "", $match);
				$_raw_cap_exp_ = explode("|",$_raw_cap_);
				$cap1=($_raw_cap_exp_[1]&&trim($_raw_cap_exp_[1])!="")?(trim(plgContentSimplepictureslideshowHelper::beStrtolower($_raw_cap_exp_[1]))):("CAPDEFAULT");
				$cap2=($_raw_cap_exp_[2]&&trim($_raw_cap_exp_[2])!="")?(trim($_raw_cap_exp_[2])):("");
				$cap3=($_raw_cap_exp_[3]&&trim($_raw_cap_exp_[3])!="")?(trim($_raw_cap_exp_[3])):("");
				$caparray="cap_ar".$_raw_cap_exp_[0];
				if(!isset($$caparray)){$$caparray=array();};
				//escape captions for use in JavaScript
				$cap4 = plgContentSimplepictureslideshowHelper::beKickQuotes($cap2);
				$cap5 = plgContentSimplepictureslideshowHelper::beKickQuotes($cap3);
				${$caparray}[$cap1]=array($cap2,$cap3,$cap4,$cap5);
				//remove the call
				$article->text = plgContentSimplepictureslideshowHelper::beReplaceCall("{besps_c}".$_raw_cap_."{/besps_c}",'', $article->text);
			}
		}
//captions

//links
		if (preg_match_all("#{besps_l}(.*?){/besps_l}#s", $article->text, $matches, PREG_PATTERN_ORDER) > 0) {
			foreach ($matches[0] as $match) {
				$_raw_link_ = preg_replace("/{.+?}/", "", $match);
				$_raw_link_exp_ = explode("|",$_raw_link_);
				$_link1=($_raw_link_exp_[1]&&trim($_raw_link_exp_[1])!="")?(plgContentSimplepictureslideshowHelper::beStrtolower(trim($_raw_link_exp_[1]))):("LINKDEFAULT");
				$_link2=($_raw_link_exp_[2]&&trim($_raw_link_exp_[2])!="")?(trim($_raw_link_exp_[2])):("");
				$_link3=($_raw_link_exp_[3]&&trim($_raw_link_exp_[3])!="")?(trim($_raw_link_exp_[3])):($_link2);
				$_link4=($_raw_link_exp_[4]&&trim($_raw_link_exp_[4])!="")?(trim($_raw_link_exp_[4])):("_self");
				$_linkarray="_linkar".$_raw_link_exp_[0];
				if(!isset($$_linkarray)){$$_linkarray=array();};
				${$_linkarray}[$_link1]=array($_link2,$_link3,$_link4);
				//remove the call
				$article->text = plgContentSimplepictureslideshowHelper::beReplaceCall("{besps_l}".$_raw_link_."{/besps_l}",'', $article->text);
			}
		}
//links

//images
		if (preg_match_all("#{besps}(.*?){/besps}#s", $article->text, $matches, PREG_PATTERN_ORDER) > 0) {
			$bs_count = -1;
			//substitute article id - start
			$headerstuff = $document->getHeadData();
			foreach($headerstuff['custom'] as $key => $custom){
				if(stristr($custom, 'besps_count') !== false){
					$bespscount=explode(" ", trim($custom));
					$this->bespscounter=$bespscount[2];
					unset($headerstuff['custom'][$key]);
				}
			}
		  $document->setHeadData($headerstuff);
			$this->bespscounter = $this->bespscounter+1;
			$document->addCustomTag('<!-- besps_count '.$this->bespscounter.' -->' );
			//substitute article id - end

			foreach ($matches[0] as $match) {
				$bs_count++;
				//split string and check for overrides
				$besps_code = preg_replace("/{.+?}/", "", $match);
				$besps_raw = explode ("|", $besps_code);
				$_images_dir_ = $besps_raw[0];
				unset ($besps_overrides);
				$besps_overrides=array();
				if(count($besps_raw)>=2){ //there are parameteroverrides
					for($i=1;$i<count($besps_raw);$i++){
						$overr_temp=explode("=",$besps_raw[$i]);
						if(count($overr_temp)>=2){
							$besps_overrides[plgContentSimplepictureslideshowHelper::beStrtolower(trim($overr_temp[0]))]=trim($overr_temp[1]);
						}
					}
				}
				unset($images);
				$noimage = 0;
				//read and process the param for the image root
				$path_imgroot	= trim($this->params->get('imagepath', $path_imgroot));
				if(substr($path_imgroot, -1)!="/"){$path_imgroot=$path_imgroot."/";} //add trailing slash
				if(substr($path_imgroot,0,1)!="/"){$path_imgroot="/".$path_imgroot;} //add leading slash

				// read directory and check for images
				if ($dh = @opendir($path_absolute.$path_imgroot.$_images_dir_)) {
					while (($f = readdir($dh)) !== false) {
						if((substr(plgContentSimplepictureslideshowHelper::beStrtolower($f),-3) == 'jpg') || (substr(plgContentSimplepictureslideshowHelper::beStrtolower($f),-3) == 'gif') || (substr(plgContentSimplepictureslideshowHelper::beStrtolower($f),-3) == 'png')) {
							$noimage++;
							$images[] = array('filename' => $f, 'flastmod' => filemtime($path_absolute.$path_imgroot.$_images_dir_."/".$f)); 
						}
					}
					closedir($dh);
					//damn, found the folder but it is empty
					$html="<br />Simple Picture Slideshow:<br />No images found in folder ".$path_absolute.$path_imgroot.$_images_dir_."<br />";
				}
				else {
					//you promised me a folder - where is it?
					$html="<br />Simple Picture Slideshow:<br />Could not find folder ".$path_absolute.$path_imgroot.$_images_dir_."<br />";
				}

				if($noimage) {
					// read in parameters and overrides
					$bs_width_				= (array_key_exists("width",$besps_overrides)&&$besps_overrides['width']!="")?($besps_overrides['width']):($this->params->get('im_width', 400));
					$bs_height_				= (array_key_exists("height",$besps_overrides)&&$besps_overrides['height']!="")?($besps_overrides['height']):($this->params->get('im_height', 300));
					$bs_im_align_ 		= (array_key_exists("align",$besps_overrides)&&$besps_overrides['align']!="")?($besps_overrides['align']):($this->params->get('im_align', 1));
					$bs_bgcol_				= (array_key_exists("bgcol",$besps_overrides)&&$besps_overrides['bgcol']!="")?($besps_overrides['bgcol']):($this->params->get('sl_bgcol', 'FFFFFF'));
					$bs_sdur_					= (array_key_exists("sdur",$besps_overrides)&&$besps_overrides['sdur']!="")?($besps_overrides['sdur']):($this->params->get('sl_sdur', 3));
					$bs_fdur_					= (array_key_exists("fdur",$besps_overrides)&&$besps_overrides['fdur']!="")?($besps_overrides['fdur']):($this->params->get('sl_fdur', 1));
					$bs_steps_				= (array_key_exists("steps",$besps_overrides)&&$besps_overrides['steps']!="")?($besps_overrides['steps']):($this->params->get('sl_steps', 20));
					$bs_autostart_		= (array_key_exists("auto",$besps_overrides)&&$besps_overrides['auto']!="")?($besps_overrides['auto']):($this->params->get('autostart', 1));
					$bs_sort_					= (array_key_exists("sort",$besps_overrides)&&$besps_overrides['sort']!="")?($besps_overrides['sort']):($this->params->get('bs_sort', 0));
					$bs_setid_ 				= (array_key_exists("setid",$besps_overrides)&&$besps_overrides['setid']!="")?($besps_overrides['setid']):($this->params->get('setid', 0));
					//controls
					$path_ctrls 			= trim($this->params->get('buttonpath', $path_ctrls));
					$bs_ctrl_show_ 		= (array_key_exists("ctrls",$besps_overrides)&&$besps_overrides['ctrls']!="")?($besps_overrides['ctrls']):($this->params->get('ctrl_show', 0));
					$bs_ctrl_sort_ 		= (array_key_exists("csort",$besps_overrides)&&$besps_overrides['csort']!="")?(trim($besps_overrides['csort'])):(trim($this->params->get('ctrl_sort', '1-2345')));
					$bs_ctrl_start_ 	= (array_key_exists("cstart",$besps_overrides))?($besps_overrides['cstart']):(trim($this->params->get('ctrl_start', '')));
					$bs_ctrl_stop_ 		= (array_key_exists("cstop",$besps_overrides))?($besps_overrides['cstop']):(trim($this->params->get('ctrl_stop', '')));
					$bs_ctrl_fwd_ 		= (array_key_exists("cfwd",$besps_overrides))?($besps_overrides['cfwd']):(trim($this->params->get('ctrl_fwd', '')));
					$bs_ctrl_back_ 		= (array_key_exists("cbwd",$besps_overrides))?($besps_overrides['cbwd']):(trim($this->params->get('ctrl_back', '')));
					$bs_ctrl_height_ 	= 22;
					//###preload
					$prld_decision 		= (array_key_exists("prld",$besps_overrides)&&$besps_overrides['prld']!="")?($besps_overrides['prld']):(intval($this->params->get('preld', '')));
					$prld_decision=($prld_decision&&$prld_decision>=3)?($prld_decision):("");
					$prld_replace=$path_site.$path_plugin."besps.png";
			    //###captions and links
					$bs_cap_show_ 		= (array_key_exists("caps",$besps_overrides)&&$besps_overrides['caps']!="")?($besps_overrides['caps']):($this->params->get('cap_show', 1));
					$bs_cap_pos_ 			= (array_key_exists("inout",$besps_overrides)&&$besps_overrides['inout']!="")?($besps_overrides['inout']):($this->params->get('cap_pos', 1));
					$bs_link_use_ 		= (array_key_exists("links",$besps_overrides)&&$besps_overrides['links']!="")?($besps_overrides['links']):($this->params->get('link_use', 1));
			    //###files
			    if($this->params->get('stylesheet')!=='-1'&&$this->params->get('stylesheet')!=='0'){$file_css=$this->params->get('stylesheet');}
			    if($this->params->get('javascript')!=='-1'&&$this->params->get('javascript')!=='0'){$file_js=$this->params->get('javascript');}
					
					//allow default buttons
					$bs_ctrl_start_type = $bs_ctrl_stop_type = $bs_ctrl_fwd_type = $bs_ctrl_back_type = "noimg";
					if(is_file($path_absolute.$path_ctrls.$bs_ctrl_start_)&&((substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_start_),-3)=='jpg')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_start_),-3)=='gif')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_start_),-3)=='png'))){$bs_ctrl_start_=$path_ctrls.$bs_ctrl_start_;$bs_ctrl_start_type="img";}
					elseif($bs_ctrl_start_==""){$bs_ctrl_start_=$path_plugin.'start.png';$bs_ctrl_start_type="img";}
					if(is_file($path_absolute.$path_ctrls.$bs_ctrl_stop_)&&((substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_stop_),-3)=='jpg')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_stop_),-3)=='gif')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_stop_),-3)=='png'))){$bs_ctrl_stop_=$path_ctrls.$bs_ctrl_stop_;$bs_ctrl_stop_type="img";}
					elseif($bs_ctrl_stop_==""){$bs_ctrl_stop_=$path_plugin.'stop.png';$bs_ctrl_stop_type="img";}
					if(is_file($path_absolute.$path_ctrls.$bs_ctrl_fwd_)&&((substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_fwd_),-3)=='jpg')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_fwd_),-3)=='gif')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_fwd_),-3)=='png'))){$bs_ctrl_fwd_=$path_ctrls.$bs_ctrl_fwd_;$bs_ctrl_fwd_type="img";}
					elseif($bs_ctrl_fwd_==""){$bs_ctrl_fwd_=$path_plugin.'fwd.png';$bs_ctrl_fwd_type="img";}
					if(is_file($path_absolute.$path_ctrls.$bs_ctrl_back_)&&((substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_back_),-3)=='jpg')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_back_),-3)=='gif')||(substr(plgContentSimplepictureslideshowHelper::beStrtolower($bs_ctrl_back_),-3)=='png'))){$bs_ctrl_back_=$path_ctrls.$bs_ctrl_back_;$bs_ctrl_back_type="img";}
					elseif($bs_ctrl_back_==""){$bs_ctrl_back_=$path_plugin.'bwd.png';$bs_ctrl_back_type="img";}
			
					//calculate time to slide
					$besps_ftim_	= $bs_fdur_*1000/$bs_steps_;
					$besps_stim_	= $bs_sdur_*1000;
					
					//sort images
					$images = plgContentSimplepictureslideshowHelper::beSortImages($images,$bs_sort_);

					//duplicate array if there are only 2 images
					unset($counterlast);
					if(count($images)==2){
						$images=array_merge($images,$images);
						$noimage+=2;
						$counterlast=2;
					}

					//create a unique identifier for every gallery
					$identifier=$this->bespscounter."_".$bs_count;

					//call the script once
					if($bs_count<=0){
						$document->addScript ($path_site.$path_plugin.$file_js);
					}

					//current array for preload-imagenames
					$preload_array=array();
					//current array for links
					$bslinks="_linkar".$bs_count;
					//current array for captions
					$captions="cap_ar".$bs_count;
					//clear current js-array for captions
					if(isset($cap_jsarray)){unset($cap_jsarray);}
					//clear current array for startimage-captions
					if(isset($currentarray)){unset($currentarray);}
					//start collecting data for javascript array of images to load afterwards
					if($bs_cap_show_&&isset($$captions)){
						$cap_jsarray='var bs_caps_'.$identifier.'= new Array();';
					}

					//set the height for the slideshow
					$temp_folderheight=$bs_height_;
					if($bs_ctrl_show_) {//stretch the height if we use controls
						$temp_folderheight+=$bs_ctrl_height_;
					}
					
					//generate the css for the head
					$bs_style=".besps_holder_".$identifier." {width:".$bs_width_."px;height:".$temp_folderheight."px;}\n";
					$bs_style.=".besps_ctrls_".$identifier." {display:block;width:".$bs_width_."px;padding-top:".($bs_height_+3)."px;text-align:right;}\n";
					if		($bs_im_align_==0){$bs_style.=".besps_holder_".$identifier." {margin:0 10px 0 auto;padding:0;display:block;}\n";}
					elseif($bs_im_align_==1){$bs_style.=".besps_holder_".$identifier." {margin:auto;padding:0;display:block;}\n";}
					elseif($bs_im_align_==3){$bs_style.=".besps_holder_".$identifier." {margin:10px;float:left;}\n";}
					elseif($bs_im_align_==4){$bs_style.=".besps_holder_".$identifier." {margin:10px;float:right;}\n";}
					else										{$bs_style.=".besps_holder_".$identifier." {}\n";}

					$bs_style.=".besps_slides_".$identifier." {position:absolute;width:".$bs_width_."px;height:".$bs_height_."px;}\n";
					$bs_style.=".besps_slides_".$identifier." div {visibility:hidden;z-index:1;position:absolute;left:0;top:0;width:".$bs_width_."px;height:".$bs_height_."px;background-color:#".$bs_bgcol_.";}\n";
					$bs_style.=".besps_slides_".$identifier." div img {position:absolute;}\n";
					$bs_style.="#img_".$identifier."_1 {visibility:visible;z-index:2;}\n";
					if($bs_cap_show_&&isset($$captions)){
					$bs_style.=".besps_caps_".$identifier." {position:absolute;width:".$bs_width_."px;height:".$bs_height_."px;}\n";
					$bs_style.=".besps_caps_".$identifier." div.bs_inside {position:absolute;bottom:0;left:0;width:".$bs_width_."px;}\n";
					$bs_style.=".besps_caps_".$identifier." div.bs_outside {position:absolute;top:".$temp_folderheight."px;left:0;width:".$bs_width_."px;}\n";
					}

					//create holder
					if($bs_setid_){
						$_tempstring=explode("/",$_images_dir_);
						$_tempstring=$_tempstring[count($_tempstring)-1];
						$html="\n<div class='besps_holder besps_holder_".$identifier."' id='".$_tempstring."'>";
					}else{
						$html="\n<div class='besps_holder besps_holder_".$identifier."'>";
					}							
					$html.="\n<div class='besps_slides besps_slides_".$identifier."'>";
					
					//walk down the found images
					for($a = 0;$a<$noimage;$a++) {
						if($images[$a]['filename'] != '') {
							//imagedata of current image
							$besp_aktimg_data_ = getimagesize($path_absolute.$path_imgroot.$_images_dir_.'/'.$images[$a]['filename']);

							//prepare captions############################
							//write caps to array if set
							if(isset($cap_jsarray)){
								$cap_jsarray.='bs_caps_'.$identifier.'['.$a.']=new Array(';
								if(array_key_exists(plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename']),$$captions)){
									$cap_jsarray.='"'.${$captions}[plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename'])][2].'","'.${$captions}[plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename'])][3].'"';
									if($a==0){$currentarray=${$captions}[plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename'])];}
									}
								elseif(array_key_exists("CAPDEFAULT",$$captions)){
									$cap_jsarray.='"'.${$captions}["CAPDEFAULT"][2].'","'.${$captions}["CAPDEFAULT"][3].'"';
									if($a==0){$currentarray=${$captions}["CAPDEFAULT"];}
									}
								else{
									$cap_jsarray.='"",""';
									if($a==0){$currentarray=Array('','','','');}
									}
								$cap_jsarray.=');';
							}

							//prepare links###############################
							if(isset($currentlink)){unset($currentlink);};
							if($bs_link_use_&&isset($$bslinks)){
								if(array_key_exists(plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename']),$$bslinks)){$currentlink=${$bslinks}[plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename'])];}
								elseif(array_key_exists("LINKDEFAULT",$$bslinks)){$currentlink=${$bslinks}["LINKDEFAULT"];}
								else{$currentlink=array("","","_self");}
							}

							//prepare alt-title###########################
							$curimtitle=utf8_encode(substr($images[$a]["filename"], 0, -4));
							//from link
							if(isset($currentlink)&&$currentlink[1]!=""){
								$curimtitle=$currentlink[1];
								}
							//from caption
							elseif(isset($$captions)){
								if(array_key_exists(plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename']),$$captions)){$curimtitle=${$captions}[plgContentSimplepictureslideshowHelper::beStrtolower($images[$a]['filename'])][2];}
								elseif(array_key_exists("CAPDEFAULT",$$captions)){$curimtitle=${$captions}["CAPDEFAULT"][2];}
								}

							//calculate imagesize and deviation
							$besp_showwidth=$besp_aktimg_data_[0];
							$besp_showheight=$besp_aktimg_data_[1];
							if($besp_aktimg_data_[0]>=$bs_width_){$besp_showwidth=$bs_width_;$besp_showheight=intval($besp_aktimg_data_[1]*$bs_width_/$besp_aktimg_data_[0]);}
							if($besp_showheight>=$bs_height_){$besp_showwidth=intval($besp_showwidth*$bs_height_/$besp_showheight);$besp_showheight=$bs_height_;}
							$besp_xdelta=intval(($bs_width_-$besp_showwidth)/2);
							$besp_ydelta=intval(($bs_height_-$besp_showheight)/2);

							//encode file name
							$iname=rawurlencode(utf8_encode($images[$a]["filename"]));
							//if image is to load afterwards, switch source to preload and push image to preloadarray
							if($prld_decision&&$a>=$prld_decision){
								$thesource=$prld_replace;
								$preload_array[]=$path_imgroot.$_images_dir_."/".$iname;
								}
							else {
								$thesource=$path_site.$path_imgroot.$_images_dir_."/".$iname;
								}

							//write image-div and image
							$html.="\n<div id='img_".$identifier."_".($a+1)."'>"; //write image-div
							if($bs_link_use_&&isset($$bslinks)){ //write linktag if set
								$html .= "<a href='".$currentlink[0]."' title='".$currentlink[1]."' target='".$currentlink[2]."'>";
								}
							//write image
							$html.="<img src='".$thesource."' style='left:".$besp_xdelta."px;top:".$besp_ydelta."px;width:".$besp_showwidth."px;height:".$besp_showheight."px;' alt='".$curimtitle."' title='".$curimtitle."'/>";
							if(isset($currentlink)){ $html .= "</a>"; } //close linktag if set
							$html.="</div>"; //close image-div
						}
					}

					//push images to load afterwards into array for javascript
					if(count($preload_array)>=1){
						$preload_jsarray="var besps_prearr_".$identifier."=new Array(";
						for($i=0;$i<=count($preload_array)-1;$i++){
							$preload_jsarray.='"'.$preload_array[$i].'",';
							}
						$preload_jsarray=substr($preload_jsarray, 0, -1).");\n"; //remove last comma and close array
						$preload_attribute=",besps_prearr_".$identifier;
						}
					else{
						$preload_attribute="";
						$preload_jsarray="";
						}
					
					if(isset($cap_jsarray)){ 
						$document->addScriptDeclaration($cap_jsarray); //add array with captions to page head
						$capsjsparam="bs_caps_".$identifier; //set parameter for captions
						}
					else{
						$capsjsparam='"NOCAPS"'; //set parameter for captions
						}

					//add call for the script to page head
					$document->addScriptDeclaration($preload_jsarray.'var besps_'.$identifier.'= new besps_slideshow("'.$identifier.'",'.$besps_ftim_.','.$besps_stim_.','.$bs_steps_.','.$bs_autostart_.','.$noimage.','.$capsjsparam.$preload_attribute.');' );

					$html.="</div>\n";
					$html.="\n<div class='besps_caps besps_caps_".$identifier."' id='bs_caps_".$identifier."'>";
					//caption
					if(isset($cap_jsarray)){
						$html .= "<div class='".(($bs_cap_pos_)?'bs_inside':'bs_outside')."'>";
						if($currentarray[0]!=""||$currentarray[1]!=""){
						$html .= "<span>".$currentarray[0]."</span><span>".$currentarray[1]."</span>";
						}
						$html .= "</div>".(($bs_cap_pos_)?"":"<br style='clear:both;' />");
						}
					$html.="</div>\n";

					//controls########################################
					if($bs_ctrl_show_) {
						$html.="<div class='besps_ctrls besps_ctrls_".$identifier."'>";

						//sort left-right and create control-elements
						$besps_temppos = strpos($bs_ctrl_sort_, '-');
						$html.=($besps_temppos===false)?("<div class='besps_ctrl_right'>"):("<div class='besps_ctrl_left'>"); //start left or right

						$k=0; //pointer to insert spaces
						for($i=0;$i<strlen($bs_ctrl_sort_);$i++){
							$j=substr($bs_ctrl_sort_, $i, 1);
						  if($k>=1){$html.="&nbsp;";}
							switch ($j) {
						    case '-': //split between left and right
									$html.="</div><div class='besps_ctrl_right'>";
									$k=0;
   						    break;
						    case '1': //counter
						    	$k++;
						    	$counterstart='1';
						    	$counterstop=(isset($counterlast))?($counterlast):(count($images));
						    	if(count($images)>=10){$counterstart='01';}if(count($images)>=100){$counterstart='001';}
									$html.="<span id='besps_counter_".$identifier."' class='besps_counter'>".$counterstart."/".$counterstop."</span>";
						      break;
						    case '2': //start
						    	$k++;
									$html.="<a href='javascript:besps_".$identifier.".b_start();'>";
									if($bs_ctrl_start_type=="img"){$html.="<img src='".$path_site.$bs_ctrl_start_."' alt='".substr($bs_ctrl_start_, (strrpos($bs_ctrl_start_, "/")+1), -4)."'/>";}
									else{$html.=$bs_ctrl_start_;}
									$html.="</a>";
						      break;
						    case '3': //stop
						    	$k++;
									$html.="<a href='javascript:besps_".$identifier.".b_stop();'>";
									if($bs_ctrl_stop_type=="img"){$html.="<img src='".$path_site.$bs_ctrl_stop_."' alt='".substr($bs_ctrl_stop_, (strrpos($bs_ctrl_stop_, "/")+1), -4)."'/>";}
									else{$html.=$bs_ctrl_stop_;}
									$html.="</a>";
					        break;
						    case '4': //back
						    	$k++;
									$html.="<a href='javascript:besps_".$identifier.".b_back();'>";
									if($bs_ctrl_back_type=="img"){$html.="<img src='".$path_site.$bs_ctrl_back_."' alt='".substr($bs_ctrl_back_, (strrpos($bs_ctrl_back_, "/")+1), -4)."'/>";}
									else{$html.=$bs_ctrl_back_;}
									$html.="</a>";
					        break;
						    case '5': //next
						    	$k++;
									$html.="<a href='javascript:besps_".$identifier.".b_forw();'>";
									if($bs_ctrl_fwd_type=="img"){$html.="<img src='".$path_site.$bs_ctrl_fwd_."' alt='".substr($bs_ctrl_fwd_, (strrpos($bs_ctrl_fwd_, "/")+1), -4)."'/>";}
									else{$html.=$bs_ctrl_fwd_;}
									$html.="</a>";
									break;
								default: //no idea what you mean!
									$html.="&nbsp;?&nbsp;";
						      break;
								}
							}
							$html.="</div></div>\n";
						}

					$html.="</div>\n";
					//add styles to the page head
					$document->addStyleDeclaration($bs_style);				
				}
				//replace the call with the slideshow
				$article->text = plgContentSimplepictureslideshowHelper::beReplaceCall("{besps}".$besps_code."{/besps}",$html, $article->text);
			}
			//call the stylesheet at the end to allow overrides
			//prevent duplicate links to stylesheet - start
			$detectstylesheet = 1;
			$headerstuff = $document->getHeadData();
			foreach($headerstuff['custom'] as $key => $custom){
				if(stristr($custom, $file_css) !== false){
					$detectstylesheet = 0;
				}
			}
			//prevent duplicate links to stylesheet - end
			if($detectstylesheet){
				$document->addCustomTag('<link href="'.$path_site.$path_plugin.$file_css.'" rel="stylesheet" type="text/css" />' );
			}
		}
//images
	}
	public function onContentAfterDisplay($context, &$article, &$params, $limitstart=0) {
		if(isset($article->id)){$this->myarticleid=$article->id;} //substitute article id on frontpage
	}
}
?>