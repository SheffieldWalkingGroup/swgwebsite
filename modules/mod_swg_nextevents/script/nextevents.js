/**
 * The outer wrapper around the popup
 */
var infoPopup = null;
/**
 * The contents of the popup
 */
var popupContents = null;
var enterDelay = null;
var exitDelay = null;
/**
 * The ID of the currently loading event: used to avoid race conditions
 * If the incoming event has the wrong ID, it is saved in the cache but not displayed
 */
var currentlyLoading = null;
var cachedEvents = {
  "walk":new Array(),
  "social":new Array(),
  "weekend":new Array()
};

function registerPopupLinks() {
	// Get all the event info popup links (for all event types)
	var popuplinks = $$("div.events a.eventinfopopup");
	popuplinks.each(
		function(link) {
			// Get the event type and ID
			var eventType = link.rel.substr(0,link.rel.indexOf("_"));
			var eventID = link.rel.substr(link.rel.indexOf("_")+1);
			link.addEvent("mouseover", function(event) {
				// Delay 300ms before showing
				clearTimers();
				enterDelay = showPopup.delay(300,window,[eventType,eventID,link]);
				return false;
			});
			link.addEvent("mouseout", function(event) {
				// Delay 500ms before hiding
				clearTimers();
				exitDelay = hidePopup.delay(500);
				return false;
			});
		}
	);
}

/**
 * Called when the user mouses over or out of a link.
 * It cancels any running timers, so they can be started from scratch.
 */
function clearTimers() {
	if (enterDelay != null) {
		clearTimeout(enterDelay);
		enterDelay = null;
	}
	if (exitDelay != null) {
		clearTimeout(exitDelay);
		exitDelay = null;
	}
}

/**
 * Called when the user mouses over a popup link. 
 * Starts a 500ms timer before the popup appears
 * TODO: Could request data immediately so it's preloaded,
 * as long as we can handle race conditions as the user moves around
 */
var showPopup = function(eventType, eventID, link) {
	// Create a new popup
	if (infoPopup != null)
		infoPopup.dispose();
	infoPopup = new Element("div",{
		"class":"popup "
	});
	popupContents = new Element("div",{
		"class":"content "+eventType
	});
	
	infoPopup.adopt(popupContents);
	document.body.adopt(infoPopup);
	
	// Set the popup's position. It should be 20px above the link,
	// and at least 50px from the edge of the window.
	infoPopup.position({
		relativeTo:link,
		position:'upperLeft',
		edge:'bottomLeft',
		maximum:{x:(window.getSize().x-infoPopup.getSize().x-50)},
		offset:{y:-20}
	});
	
	// Display the popup
	infoPopup.set("opacity",0);
	infoPopup.fade(1);
	
	// Allow the user to move the mouse into the popup without it vanishing
	infoPopup.addEvent("mouseover",function(event) {
		clearTimers();
	});
	
	// But in return, we need to add mouseout functionality
	infoPopup.addEvent("mouseout", function(event) {
		// Delay 500ms before hiding
		clearTimers();
		exitDelay = hidePopup.delay(500);
		return false;
	});
	
	// See if we've previously cached this event
	if (cachedEvents[eventType][eventID] != undefined)
	{
		displayPopup(cachedEvents[eventType][eventID], eventType);
	}
	else
	{
		// Cache this as the currently loading event
		currentlyLoading = eventID;
		// Request data for this event (will be displayed when available)
		// TODO: Variable URL
		var a = new Request.JSON({
			url:"/index.php/homepage/walks",
			format:"json",
			data:{"eventtype":eventType,"id":eventID},
			method:"get",
			onSuccess: function(event) {
				cachedEvents[eventType][eventID] = event;
				
				// Is this the event we want now?
				if (event.id == currentlyLoading)
				{
					// Destroy the load indicator
					loadIndicator.dispose();
					displayPopup(event, eventType);
				}
			}
		});
		a.get();
	
		// Add a loading indicator to the popup
		var loadIndicator = new Element("img",{
			"src":"/templates/swgpeter/images/ajax-loader.gif",
			"width":"32",
			"height":"32",
			"class":"loadindicator"
		});
		popupContents.adopt(loadIndicator);
	}
}

function displayPopup(event, eventType) {
	// Add alterations to the whole popup
	if (event.alterations.any) {
		infoPopup.addClass("popup-altered");
	}
	
	if (event.alterations.cancelled) {
		popupContents.addClass("cancelled");
		var cancelledText = new Element("p", {
			"class":"cancelled-message",
			"html":"Cancelled"
		});
		infoPopup.adopt(cancelledText);
	}
		
	
	// Build up the basic components that apply to all event types
	var infoHeader = new Element("div", {"class":"eventheader"});
	var eventName = new Element("h3", {"text":event.name,"style":"clear:both;"});
	var eventDate = new Element("span",{"class":"date","text":timestampToDate(event.start)});
	if (event.alterations.date)
		eventDate.addClass("altered");
	
	infoHeader.adopt(eventDate, eventName);
	popupContents.adopt(infoHeader);
	
	var description = new Element("div", {"class":"description","html":"<p>"+event.description+"</p>"});
	popupContents.adopt(description);
	if (event.alterations.details)
		description.addClass("altered");
	
	var eventInfo = new Element("div", {"class":"eventinfo"});
	popupContents.adopt(eventInfo);
	
	// Now do specific components
	switch(eventType) {
		case "walk":
			// Add the day to the class
			var start = new Date(event.start*1000);
			var day = "weekday";
			if (start.getDay() == 0)
				day = "sunday";
			else if (start.getDay() == 6)
				day = "saturday";
			popupContents.addClass("walk"+day);
			
			var rating = new Element("span", {"class":"rating", "text":event.distanceGrade+event.difficultyGrade+" ("+event.miles+" miles)"});
			rating.inject(eventName,'before');
			
			// TODO: icons
			var start = new Element("p", {
				"class":"start", 
				"html":
					"<span>Start:</span> " +
					"<a title='Streetmap view of approximate location' href='http://www.streetmap.com/loc/"+event.startGridRef+"'>"+event.startGridRef+", "+event.startPlaceName+"</a>"
				});
			eventInfo.adopt(start);
			if (event.isLinear == true) {
				var end = new Element("p", {
					"class":"end", 
					"html":
						"<span>End:</span> " +
						event.endGridRef+", "+event.endPlaceName
				});
				eventInfo.adopt(end);
			}
			
			var transportText = "";
			if (event.meetPoint.id != 7) // TODO: Remove magic number
			{
				// Parse the meeting time
				var meetTime = new Date(event.meetPoint.meetTime*1000);
				transportText += "Meet at "+meetTime.format("%H:%M")+" at "+event.meetPoint.longDesc+". ";
			}
			if (event.meetPoint.extra != null) // TODO: Use function? Note: doesn't matter if it's empty
				transportText += event.meetPoint.extra;
									
			var transport = new Element("p", {
				"class":"transport",
				"html":"<span>Transport:</span> " + transportText
			});
			eventInfo.adopt(transport);
			if (event.alterations.placeTime)
				transport.addClass("altered");
			
			var leaderText = event.leader.displayName+
				" ("+event.leader.telephone+")";
			if (event.leader.noContactOfficeHours)
				leaderText += " &ndash; don't call during office hours";
			
			var leader = new Element("p", {
				"class":"leader",
				"html":"<span>Leader:</span> "+leaderText
			});
			if (event.alterations.organiser)
				leader.addClass("altered");
			eventInfo.adopt(leader);
			
			var backmarker = new Element("p", {
				"class":"backmarker",
				"html":"<span>Backmarker:</span> "+event.backmarker.displayName
			});
			eventInfo.adopt(backmarker);
			
			break;
		case "weekend":
			var area = new Element("span", {
				"class":"area",
				"text":event.area
			});
			area.inject(eventName,'before');
			if (event.url != "") {
				var moreInfo = new Element("p",{
					"class":"moreinfo",
					"html":"<span>More info:</span> <a href='"+event.url+"'>Here"
				});
			}
			var places = new Element("p",{
				"class":"places",
				"html":"<span>Places:</span> "+event.places+" at "+event.cost+" (remember the booking and refunds policy)"
			});
			var bookingsOpen = new Element("p",{
				"class":"bookingopen",
				"html":"<span>Bookings open:</span> "+event.bookingsOpen
			});
			var contact = new Element("p",{
				"class":eventType+"booking",
				"html":"<span>Contact:</span> "+event.contact
			});
			if (event.alterations.organiser)
				contact.addClass("altered");
			eventInfo.adopt(moreInfo,places,contact);
			break;
		case "social":
			var contact = new Element("p",{
				"class":eventType+"booking",
				"html":"<span>Contact:</span> "+event.bookingsInfo
			});
			if (event.alterations.organiser)
				contact.addClass("altered");
			eventInfo.adopt(contact);
			
			if (eventType == "weekend")
				eventInfo.adopt(bookingsOpen);
			
			break;
	}
	
	// Add a fancy scrollbar to the description
	if (description.getScrollSize().y > description.getSize().y) {
		description.style.width="290px"; // Make room for the scrollbar
		var scrollbar = new Element("div",{"class":"scrollbar-vert", "id":"scrollbar"});
		var scrollHandle = new Element("div",{"class":"handle-vert", "id":"scrollHandle"});
		description.grab(scrollbar,'before');
		scrollbar.adopt(scrollHandle);
		
		makeScrollbar(description,scrollbar,scrollHandle,false,false);
	}
}

function timestampToDate(timestamp) {
	var dayNames = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
	var monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
	var date = new Date(timestamp*1000); // Convert to milliseconds
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
	return dayName+" "+dayDate+suffix+" "+month;
}

/**
 * Fade the popup out and destroy it
 */
function hidePopup() {
	infoPopup.fade("out");
	infoPopup.dispose.delay(500); // After the fade
	
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
			e = new Event(e).stop();
			var step = slider.step - e.wheel * 30;	
			slider.set(step);					
		});
	}
	// Stops the handle dragging process when the mouse leaves the document body.
	$(document.body).addEvent('mouseleave',function(){slider.drag.stop()});
}

// TODO: Dynamically generate the popup - only create it for JS peeps
window.addEvent('domready', registerPopupLinks);
var popup = document.id("walk-popup");
//popup.set("opacity",0);
