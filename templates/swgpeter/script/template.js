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
			banner.setStyle("height", banner.offsetWidth * 0.3368);
		}
		// Resize the slideshow images
		var slideshowDivs = document.body.getElements(".home .slideshow div");
		if (slideshowDivs.length != 0)
		{
			var height = slideshowDivs[0].offsetWidth * (2/3);
			for (var i=0;i<slideshowDivs.length;i++)
			{
				if (!slideshowDivs[i].hasClass("bs_inside"))
					slideshowDivs[i].setStyle("height", height);
			}
			
			var slideshowImgs = document.body.getElements(".slideshow .besps_slides img");
			for (var i=0; i<slideshowImgs.length; i++)
			{
				slideshowImgs[i].setStyle("width", slideshowDivs[0].width);
				slideshowImgs[i].setStyle("height", height);
			}
		}
		
		// Make any suitable text boxes expandable
		var textBoxHeadings = document.body.getElements(".moduletable h3:first-of-type");
		for (var i=0; i<textBoxHeadings.length; i++)
		{
			var heading = textBoxHeadings[i];
			var box = heading.getParent(".moduletable");
			if (!box.hasClass("keep-open"))
				this.setupFolding(box, heading);
		}
		
		// Modify events
		// TODO: Handle events already set up - check for a flag
		var self = this;
		window.addEvent("eventReady", function(event,container)
		{
			self.eventHooks.call(self, event, container);
		});
		
	},
	
	setupFolding: function(box, heading) 
	{
		box.addClass("closable");
			
		box.store("openheight", box.offsetHeight);
		box.store("closedheight", heading.offsetHeight);
		box.store("open", false);
		box.set("tween", {duration:"short"});
		
		box.style.height = heading.offsetHeight+"px";
		
		heading.store("target", box);
		heading.addEvent("click", function(ev)
		{
			var box = heading.retrieve("target");
			if (box.hasClass("open"))
			{
				box.removeClass("open");
				box.tween("height", box.retrieve("closedheight"));
			}
			else
			{
				box.addClass("open");
				box.tween("height", box.retrieve("openheight"));
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
		
		// Set up folding
		this.setupFolding(container, container.getElement(".eventheader"));
		
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