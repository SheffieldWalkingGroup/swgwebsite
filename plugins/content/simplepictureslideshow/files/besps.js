/*
// "Simple Picture Slideshow" Plugin for Joomla 3.1 - Version 1.5.8
// License: GNU General Public License version 2 or later; see LICENSE.txt
// Author: Andreas Berger - andreas_berger@bretteleben.de
// Copyright (C) 2013 Andreas Berger - http://www.bretteleben.de. All rights reserved.
// Project page and Demo at http://www.bretteleben.de
// ***Last update: 2013-08-23***
*/

function besps_slideshow(besps_slideid,besps_ftim,besps_stim,besps_steps,besps_startwhen,besps_emax,besps_caps,besps_preload){

//declarations
	var self = this;
	var slideid=besps_slideid;
	var ftim=besps_ftim;
	var stim=besps_stim;
	var steps=besps_steps;
	var startwhen=besps_startwhen;
	var emax=besps_emax;
	var preload=besps_preload;
	var stopit=1;
	var startim=1;
	var u=0;
	var parr = new Array();
	var ptofade,pnext,factor,mytimeout;
  var caps=besps_caps;

//daisychain onload-events
this.be_daisychain=function(sl){
	if(window.addEventListener){
		window.addEventListener('load',sl,false);
		}
	else if(window.attachEvent){
		window.attachEvent('onload',sl);
		}
	else{
		if(window.onload){
			var ld=window.onload;
			window.onload=function(){ld();sl();};
			}
		else{
			window.onload=sl;
			}
		}
	};

//push images into array and get things going
	this.b_myfade = function(){
		var a,idakt,paktidakt,ie5exep;
		for(a=1;a<=emax;a++){
			idakt="img_"+slideid+"_"+a;paktidakt=document.getElementById(idakt);
    	ie5exep=new Array(paktidakt);parr=parr.concat(ie5exep);
    if(preload&&a==emax){
    	setTimeout(function(){self.b_preload();},10);
    }
    }
		if(startwhen){
			stopit=0;
 			mytimeout=setTimeout(function(){self.b_slide();},stim);
 		}
	}

//prepare current and next and trigger slide
	this.b_slide = function(){
		clearTimeout(mytimeout);
		u=0;
		ptofade=parr[startim-1];
		if(startim<emax){pnext=parr[startim];}
		else{pnext=parr[0];}
		pnext.style.zIndex=1;
		pnext.style.visibility="visible";
		pnext.style.filter="Alpha(Opacity=100)";
		try{pnext.style.removeAttribute("filter");} catch(err){}
		pnext.style.MozOpacity=1;
		pnext.style.opacity=1;
		ptofade.style.zIndex=2;
		ptofade.style.visibility="visible";
		ptofade.style.filter="Alpha(Opacity=100)";
		ptofade.style.MozOpacity=1;
		ptofade.style.opacity=1;
		factor=100/steps;
		if(stopit=="0"){
			this.b_slidenow();
		}
	}

//one step forward
	this.b_forw = function(){
		stopit=1;
		clearTimeout(mytimeout);
		ptofade=parr[startim-1];
		if(startim<emax){pnext=parr[startim];startim=startim+1;}
		else{pnext=parr[0];startim=1;}
		ptofade.style.visibility="hidden";
		ptofade.style.zIndex=1;
		pnext.style.visibility="visible";
		pnext.style.zIndex=2;
		this.b_switchcap();
		self.b_slide();
		//counter
		self.setCurrentNumber();
	}

//one step back
	this.b_back = function(){
		stopit=1;
		clearTimeout(mytimeout);
		if(u==0){ //between two slides
			ptofade=parr[startim-1];
			if(startim<emax){pnext=parr[startim];}
			else{pnext=parr[0];}
			pnext.style.visibility="hidden";
			ptofade.style.zIndex=1;
			ptofade.style.visibility="visible";
			if(startim>=2){startim=startim-1;}
			else{startim=emax;}
			this.b_switchcap();
			self.b_slide();
		}
		else{ //whilst sliding
			this.b_switchcap();
			self.b_slide();
		}
		//counter
		self.setCurrentNumber();
	}

//slide as said, then give back
	this.b_slidenow = function(){
		var check1,maxalpha,curralpha;
		check1=ptofade.style.MozOpacity;
		maxalpha=(100-factor*u)/100*105;
		if(check1<=maxalpha/100){u=u+1;}
		curralpha=100-factor*u;
		ptofade.style.filter="Alpha(Opacity="+curralpha+")";
		ptofade.style.MozOpacity=curralpha/100;
		ptofade.style.opacity=curralpha/100;
		if(u<steps){ //slide not finished
			if(stopit=="0"){mytimeout=setTimeout(function(){self.b_slidenow();},ftim);}
			else {this.b_slide();}
		}
		else{ //slide finished
			if(startim<emax){
				ptofade.style.visibility="hidden";
				ptofade.style.zIndex=1;
				pnext.style.zIndex=2;
				startim=startim+1;u=0;
				this.b_switchcap();
				mytimeout=setTimeout(function(){self.b_slide();},stim);
			}
			else{
				ptofade.style.visibility="hidden";
				ptofade.style.zIndex=1;
				pnext.style.zIndex=2;
				startim=1;u=0;
				this.b_switchcap();
				mytimeout=setTimeout(function(){self.b_slide();},stim);
			}
		}
		//counter
		self.setCurrentNumber();
	}

//manual start
	this.b_start= function(){
		if(stopit==1){
 			stopit=0;
			this.b_switchcap();
 			mytimeout=setTimeout(function(){self.b_slide();},stim);
 		}
	}

//manual stop
	this.b_stop= function(){
		clearTimeout(mytimeout);
		stopit=1;
			this.b_switchcap();
		this.b_slide();
	}

//preload
	this.b_preload= function(){
		var arrdelta,tempsrc,j;
		if(preload&&preload.length>=1){
			for (j=0;j<preload.length;j++){
				arrdelta=(emax-preload.length+j)*1;
				tempsrc=parr[arrdelta].getElementsByTagName("img")[0].src.toString();
				parr[arrdelta].getElementsByTagName("img")[0].src=tempsrc.replace(/\/plugins\/content\/simplepictureslideshow\/files\/besps.png$/g, preload[j]);
			}
		}
	}

//switch captions
	this.b_switchcap = function(){
		if(caps!="NOCAPS"&&caps.length>=1){
			document.getElementById("bs_caps_"+besps_slideid).getElementsByTagName("div")[0].innerHTML=(caps[startim-1][0]!=""||caps[startim-1][1]!="")?"<span>"+caps[startim-1][0]+"</span><span>"+caps[startim-1][1]+"</span>":"&nbsp;";
		}
}

//counter
	this.setCurrentNumber= function(){
		if(document.getElementById("besps_counter_"+besps_slideid)){
			var actim=startim;
			var lastim=emax;
			//capture doubled arrays
			if(parr.length==4&&parr[1].getElementsByTagName("img")[0].src==parr[3].getElementsByTagName("img")[0].src){
				lastim=2;
				actim-=(actim>=3)?2:0;
				}
			if(emax>=10){actim=(actim<=9)?('0'+actim):(actim);}
			if(emax>=100){actim=(actim<=99)?('0'+actim):(actim);}
			document.getElementById("besps_counter_"+besps_slideid).innerHTML=actim+"/"+lastim;
		}
}

//call autostart-function
	this.be_daisychain(this.b_myfade);

}