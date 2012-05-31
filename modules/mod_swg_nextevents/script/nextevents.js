var infoPopup = null;
var enterDelay = null;
var exitDelay = null;

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
		"class":"popup "+eventType
	});
	document.body.adopt(infoPopup);
	// Move the popup to the right position based on the current cursor position
	// Making sure it is inside the visible screen area
	
	
	// Request data for this event (will be displayed when available)
	// TODO: Variable URL
	var a = new Request.JSON({
		url:"/index.php/homepage/walks",
		format:"json",
		data:{"eventtype":eventType,"id":eventID},
		method:"get",
		onSuccess: function(event) {
			
			// Build up the basic components that apply to all event types
			var infoHeader = new Element("div", {"class":"eventheader"});
			var eventName = new Element("h3", {"text":event.name,"style":"clear:both;"});
			var eventDate = new Element("span",{"class":"date","text":timestampToDate(event.startDate)});
			infoHeader.adopt(eventDate, eventName);
			infoPopup.adopt(infoHeader);
			
			var description = new Element("div", {"class":"description","html":"<p>"+event.description+"</p>"});
			infoPopup.adopt(description);
			
			var eventInfo = new Element("div", {"class":"eventinfo"});
			infoPopup.adopt(eventInfo);
			
			// Now do specific components
			switch(eventType) {
				case "walk":
					// Add the day to the class
					var startDate = new Date(event.startDate*1000);
					var day = "weekday";
					if (startDate.getDay() == 0)
						day = "sunday";
					else if (startDate.getDay() == 6)
						day = "saturday";
					infoPopup.addClass("walk"+day);
					
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
								"<a title='Streetmap view of approximate location' href='http://www.streetmap.com/loc/"+event.startGridRef+"'>"+event.startGridRef+", "+event.startPlaceName+"</a>"
						});
						eventInfo.adopt(end);
					}
					// TODO: Transport, leader, backmarker
					
					break;
				case "weekend":
					var moreInfo = new Element("p",{
						"class":"moreinfo",
						"html":"<span>More info:</span> "+event.url
					});
					var places = new Element("p",{
						"class":"places",
						"html":"<span>Places:</span> "+event.places+" at "+event.cost+" (remember the booking and refunds policy)"
					});
					var bookingsOpen = new Element("p",{
						"class":"bookingopen",
						"html":"<span>Bookings open:</span> "+event.bookingsOpen
					});
					eventInfo.adopt(moreInfo,places);
					// Fall through - contact is common
				case "social":
					var contact = new Element("p",{
						"class":eventType+"booking",
						"html":"<span>Contact:</span> "+event.contact
					});
					eventInfo.adopt(contact);
					
					if (eventType == "weekend")
						eventInfo.adopt(bookingsOpen);
					
					break;
			}
			
		}
	});
	a.get();
	
	
	
	// Add a loading indicator to the popup
	
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
	infoPopup.fade(0.95);
	
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

// TODO: Dynamically generate the popup - only create it for JS peeps
window.addEvent('domready', registerPopupLinks);
var popup = document.id("walk-popup");
//popup.set("opacity",0);