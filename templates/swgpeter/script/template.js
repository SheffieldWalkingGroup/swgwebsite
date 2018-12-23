var ratingTips
var setupRatingTips = function() {
	ratingTips = new Tips(".rating",{
    	showDelay:250,
    	hideDelay:250,
    	className:"ratingTip",
    	fixed:true,
    	id:"ratingTooltip",
    	waiAria:true,
    	title:function(el) {
    		return "Walk grades";
    	},
    	text:function(el) {
    		return "<h4>Distance</h4>" +
    				"<ol style='list-style-type:upper-alpha'>" +
    				"<li>Short - up to 8 miles</li><li>Medium - 8 to 12 miles</li><li>Long - more than 12 miles</li>" +
    				"</ol>"+
    				"<h4>Difficulty</h4>" +
    				"<ol style='list-style-type:numeric;'>" +
    				"<li>Easy terrain with a couple of mild climbs</li><li>Moderate terrain with some tricky parts and steady climbs</li><li>Hard terrain, possibly with exposure and scrambling, with steep and long ascents</li>" +
    				"</ol>";
    	}
    });
}

var hasLocalStorage = function() {
	try {
		return 'localStorage' in window && window['localStorage'] !== null;
	} catch(e){
		return false;
	}
}
// TODO: Js for mobile template - matchMedia - http://www.sitepoint.com/javascript-media-queries/ - https://developer.mozilla.org/en-US/docs/Web/API/Window.matchMedia#Browser_compatibility

window.addEvent('domready', setupRatingTips);

/**
 * Display a popup message on top of the page
 */
function Popup(title, body, type)
{
	var popup = new Element("div", {
		"class":"popup"
	});
	
	var popupContents = new Element("div", {
		"class":"content"
	});
	
	var heading = new Element("h3");
	heading.set("text", title);
	var header = new Element("div", {'class' : 'header'});
	header.adopt(heading);
	
	var contents;
	
	if (body instanceof Element)
		contents = body;
	else
	{
		contents = new Element("div",{
			"class":"popupmessage"
		});
		contents.set("html", body);
	}
	
	popup.adopt(popupContents);
	popupContents.adopt(header);
	popupContents.adopt(contents);
	
	var mask = new Mask(document.body, {
		hideOnClick : true
	});
	
	mask.addEvent("hide", function()
	{
		popup.destroy();
	});
	
	popup.inject(document.body);
	popup.position({position:"center"});
	
	// TODO: Only run if on mobile - and preferably on Android only
	window.addEvent('backbutton', function(evt)
	{
		alert("Back");
	});
	
	mask.show();
}

/**
 * makeScrollbar function by Bas Wenneker, http://www.solutoire.com/experiments/scrollbar/index.html
 * @param content
 * @param scrollbar
 * @param handle
 * @param horizontal
 * @param ignoreMouse
 */
function makeScrollbar(content,scrollbar,handle,horizontal,ignoreMouse){
	var steps = (horizontal?(content.getScrollSize().x - content.getSize().x):(content.getScrollSize().y - content.getSize().y))
	var slider = new Slider(scrollbar, handle, {	
		steps: steps,
		mode: (horizontal?'horizontal':'vertical'),
		initialSetp:0,
		onChange: function(step){
			// Scrolls the content element in x or y direction.
			var x = (horizontal?step:0);
			var y = (horizontal?0:step);
			content.scrollTo(x,y);
		}
	}).set(0);
	if( !(ignoreMouse) ){
		// Scroll the content element when the mousewheel is used within the 
		// content or the scrollbar element.
		$$(content, scrollbar).addEvent('mousewheel', function(e){	
			e.stop();
			var step = slider.step - e.wheel * 30;	
			slider.set(step);					
		});
	}
	// Stops the handle dragging process when the mouse leaves the document body.
	$(document.body).addEvent('mouseleave',function(){slider.drag.stop()});
}

var Mobile = new Class({
	mobile: false,
	
	menuButton: null,
	nav: null,
	menu: null,
	
	menuFx: null,
	
	start: function()
	{
		// Prevent slideshow images from loading
		// TODO: Can't do this before the slides have already loaded. Will find another way - maybe write a custom slideshow.
		/*var imgs = document.body.getElements('.slideshow .besps_slides img');
		for (var i=0; i<imgs.length; i++)
		{
			imgs[i].erase('src');
		}*/
		
		this.mobile = true;
		
		// Stop the slideshow. TODO: Can we get a guaranteed identifier?
		//besps_1_0.b_stop();
		this.nav = document.body.getElement("nav");
		this.menuButton = this.nav.getElement(".menu-button");
		
		// Resize the menu tab join to fit the menu button (size may differ due to fonts)
		var menuButtonWidth = this.menuButton.offsetWidth;
		this.nav.getElement(".menu-tab-join").style.width = menuButtonWidth + 5 + "px";
		
		this.menu = this.nav.getElement("ul.nav");
		// Temporarily open the menu to get its size
		this.nav.setStyle("visibility", "hidden");
		this.nav.addClass("open");
		this.menu.store("height", this.menu.offsetHeight);
		this.nav.removeClass("open");
		this.nav.setStyle("visibility", "visible");
		this.menu.setStyle("height", 0);
		var self = this;
		
		document.body.addClass("mobile");
		
		menuFx = new Fx.Tween(this.menu,{
			duration: 100,
			unit: "px",
			property: 'height',
			onComplete: function()
			{
				if (self.menu.offsetHeight <= 10)
				{
					self.nav.removeClass("open");
				}
			}
		});
		
		this.menuButton.addEvent("click", function()
		{
			if (self.nav.hasClass("open"))
			{
				menuFx.start(0);
			}
			else
			{
				var height = self.menu.retrieve("height");
				self.nav.addClass("open");
				menuFx.start(height);
			}
		});
		
		// Resize the banner image
		var banner = document.body.getElement(".random-image img");
		if (banner != null)
		{
			var subHead = banner.parentNode.getElement("h2");
			var resizeBanner = function(banner)
			{
				var bannerWidth = banner.offsetWidth;
				banner.setStyle("height", 0.3368 * bannerWidth);
			};
			if (banner.complete)
			{
				// Make sure the existing scripts have finished
				resizeBanner.delay(0,this,banner);
			}
			else
			{
				banner.addEvent("load", function()
				{
					resizeBanner(banner);
				});
			}
		}
		// Resize the slideshow images
		var slideshowDivs = document.body.getElements(".home .slideshow div");
		if (slideshowDivs.length > 0)
		{
			var firstSlide = slideshowDivs[0].getElement("img");
			// Have to wait until the first image had loaded to set the page layout, but the div will have the correct width
			var resizeSlides = function(slideshowDivs)
			{
				if (slideshowDivs.length != 0)
				{
					// TODO: Implement same fix as for banner images
					var slideshowWidth = slideshowDivs[0].offsetWidth, slideshowHeight = slideshowDivs[0].offsetWidth * (2/3);
					for (var i=0;i<slideshowDivs.length;i++)
					{
						if (!slideshowDivs[i].hasClass("bs_inside"))
							slideshowDivs[i].setStyle("height", slideshowHeight);
					}
					
					var slideshowImgs = document.body.getElements(".slideshow .besps_slides img");
					for (var i=0; i<slideshowImgs.length; i++)
					{
						slideshowImgs[i].setStyle("width", slideshowWidth);
						slideshowImgs[i].setStyle("height", slideshowHeight);
					}
				}
			}
			
			if (firstSlide.complete)
			{
				resizeSlides.delay(0,this,[slideshowDivs]); // Array parameter must be wrapped in an array - MooTools limitation
			}
			else
			{
				firstSlide.addEvent("load", function()
				{
					resizeSlides(slideshowDivs);
				});
			}
		}
		
		// Make any suitable text boxes expandable
		var textBoxHeadings = document.body.getElements(".moduletable h3:first-of-type");
		for (var i=0; i<textBoxHeadings.length; i++)
		{
			var heading = textBoxHeadings[i];
			var box = heading.getParent(".moduletable");
			if (!box.hasClass("keep-open"))
			{
				// Wrap the contents
				var content = new Element("div");
				var toAdopt = [];
				for (var j=0; j<box.childNodes.length; j++)
				{
					if (box.childNodes[i] != heading)
					{
						toAdopt.push(box.childNodes[i]);
					}
				}
				box.adopt(content);
				content.adopt(toAdopt);
				this.setupFolding(box, heading, content);
			}
		}
		
		// Modify events
		// TODO: Handle events already set up - check for a flag
		var self = this;
		window.addEvent("eventReady", function(event,container)
		{
			self.eventHooks.call(self, event, container);
		});
		
	},
	
	/**
	 * Set up folding for a content box.
	 * The box will show only the heading by default, and users can tap it to display the content
	 * 
	 * @param HTMLElement box An element wrapping the entire box
	 * @param HTMLElement heading The heading element, stays visible and can be tapped to open
	 * @param HTMLElement content An element wrapping the content
	 */
	setupFolding: function(box, heading, content)
	{
		box.addClass("closable");
			
		content.style.height = 0;
		content.style.paddingBottom = 0;
		
		heading.store("target", content);
		heading.addEvent("click", function(ev)
		{
			var content = heading.retrieve("target");
			if (content.hasClass("open"))
			{
				var closing = new Fx.Morph(content, {
					duration: "short"
				});
				content.removeClass("open");
				
				closing.start({
					"height" : 0,
					"padding-bottom" : 0,
				});
			}
			else
			{
				// Get the correct height of the content: sometimes getting the height at page load doesn't give the correct value
				content.style.visibility = "hidden";
				content.style.height = "auto";
				var targetHeight = content.offsetHeight;
				content.style.height = "0";
				content.style.visibility = "visible";
				
				var opening = new Fx.Morph(content, {
					duration: "short"
				});
				opening.start({
					"height": targetHeight,
					"padding-bottom": "10px"
				});
				
				content.addClass("open");
			}
		});
	},
	
	stop: function()
	{
		if (this.mobile)
		{
			this.menuButton.removeEvent("click");
			
			this.mobile = false;
			
			document.body.removeClass("mobile");
		}
	},
	
	eventHooks: function(event, container)
	{
		// Replace time formats
		var times = container.getElements("time");
		for (var i=0; i<times.length; i++)
		{
			var time = times[i];
			if (time.innerHTML == "")
				continue;
			// iPhone Safari is shit and can't parse dates
			var datestring = time.get("datetime");
			var datearr = datestring.split(/[- :T+]/);
			if (datearr[3] == undefined)
				datearr[3] = 0;
			if (datearr[4] == undefined)
				datearr[4] = 0;
			var date = new Date(datearr[0], datearr[1]-1, datearr[2], datearr[3], datearr[4]);
			time.innerHTML = displayDate(date);
			
		}
		
		// Set up telephone links
		var leaderTelEl = container.getElement(".leadertel");
		if (leaderTelEl)
		{
			var leaderTel = leaderTelEl.textContent;
			
			var leaderLink = new Element("a", {
				'html' : 'Contact leader',
				'events' : {
					click: function()
					{
						event.phoneCallLeaderPopup()
					}
				}
			});
			leaderTelEl.parentNode.adopt(leaderLink);
			
			/*
			// TODO: No call during office hours option
			var call = document.createElement("a");
			call.href = "tel:"+leaderTel;
			call.text = "Call leader to ask questions";
			
			var text = document.createElement("a");
			text.href = "sms:"+leaderTel;
			text.text = "Text leader to book on this walk";
			
			var leaderPara = leaderTelEl.parentNode;
			leaderPara.appendChild(document.createElement("br"));
			leaderPara.appendChild(call);
			leaderPara.appendChild(document.createElement("br"));
			leaderPara.appendChild(text);*/
		}
		
		
		if (event.alterations.cancelled)
		{
			// Lock closed
			container.style.height = container.getElement(".eventheader").offsetHeight+"px";
			container.addClass("closable");
		}
		else
		{
			// Set up folding
			this.setupFolding(container, container.getElement(".eventheader"), container.getElement(".eventbody"));
		}
		
		// Remove the link in the header
		var header = container.getElement("h3");
		header.set("text", header.get("text"));
	}
});


var mql = matchMedia("(max-device-width:600px)");
window.addEvent("domready", function() {widthChange(mql);});
mql.addListener(widthChange);
var mobile = new Mobile();
function widthChange(mql)
{
	if (mql.matches)
	{
		mobile.start();
	}
	else
	{
		mobile.stop();
	}
}

function displayDate(date)
{
	var dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
	var monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
	var dayName = dayNames[date.getDay()];
	var dayDate = date.getDate();
	var month = monthNames[date.getMonth()];
	var year = date.getFullYear();
	
	// Dirty, but saves unnecessary string conversion & length calculations
	var suffix = "th";
	if (dayDate == 1 || dayDate == 11 || dayDate == 21 || dayDate == 31)
		suffix = "st";
	else if (dayDate == 2 || dayDate == 22)
		suffix = "nd";
	else if (dayDate == 3 || dayDate == 23)
		suffix = "rd";
	return dayName+" "+dayDate+suffix+" "+month+(year != new Date().getFullYear() ? " "+year:"");
}

function shortDate(date)
{
	return date.getDate()+"/"+(date.getMonth()+1)+"/"+date.getFullYear();
}

/* Leader availability calendar */
function triState(cb) {
    if (cb.readOnly) cb.checked=cb.readOnly=false;
    else if (!cb.checked) cb.readOnly=cb.indeterminate=true;
  
    var real = document.getElementById(cb.id + "_real");
    if (cb.checked) {
        real.value = 2;
    } else if (cb.indeterminate) {
        real.value = 1;
    } else {
        real.value = 0;
    }
}
