(function() {

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
/**
 * The event currently being displayed - full data
 */
var currentEvent = null;
var cachedEvents = {
  "walk":new Array(),
  "social":new Array(),
  "weekend":new Array()
};

var highlightedEvents;

var footer;

var map = null;
var mapContainer = null;

var link = null;

var popupPosition = function()
{
	return
	({
		relativeTo:link,
		position:'upperLeft',
		edge:'bottomLeft',
		minimum:{x:0,y:5},
		maximum:{x:(window.getSize().x-infoPopup.getSize().x-50)},
		offset:{x:20,y:-20}
	});
};

function registerPopupLinks() {
	// Get all the event info popup links (for all event types)
	var popuplinks = $$("div.events a.eventinfopopup");
	popuplinks.each(
		function(link) {
			// Get the event type and ID
			var eventType = link.rel.substr(0,link.rel.indexOf("_"));
			var eventID = link.rel.substr(link.rel.indexOf("_")+1);
			var newMembers = link.hasClass("newmembers");
			link.addEvent("mouseover", function(event) {
				// Delay 300ms before showing
				clearTimers();
				enterDelay = showPopup.delay(300,window,[eventType,eventID,link,newMembers]);
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
var showPopup = function(eventType, eventID, link, newMembers) {
	// Create a new popup
	if (infoPopup != null)
		infoPopup.dispose();
	infoPopup = new Element("div",{
		"class":"popup "
	});
	popupContents = new Element("div",{
		"class":"content "+eventType
	});
	
	// Clean up the map references
	if (map != null)
	{
		map.destroy();
		map = null;
		mapContainer = null;
	}
	
	infoPopup.adopt(popupContents);
	infoPopup.inject(document.body);
	
	// Set the popup's position. It should be 20px above the link,
	// and at least 50px from the edge of the window.
	infoPopup.position(popupPosition());
	
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
		displayEvent(cachedEvents[eventType][eventID], popupContents, newMembers);
		postDisplay(popupContents, cachedEvents[eventType][eventID]);
		
		// Reset the position in case the size has changed (e.g. loaded map)
		infoPopup.position(popupPosition());
	}
	else
	{
		// Cache this as the currently loading event
		currentlyLoading = eventID;
		// Request data for this event (will be displayed when available)
		// TODO: Variable URL
		var a = new Request.JSON({
			url:"/api/eventlisting",
			format:"json",
			data:{"eventtype":eventType,"id":eventID},
			method:"get",
			onSuccess: function(data) {
				// Create the event and cache it
				var event = new Event();
				event.populateFromArray(data);
				cachedEvents[eventType][eventID] = event;
				
				// Is this the event we want now?
				if (data.id == currentlyLoading)
				{
					// Destroy the load indicator
					loadIndicator.dispose();
					displayEvent(event, popupContents, newMembers);
					postDisplay(popupContents, event);
					
				}
			}
		});
		a.get();
	
		// Add a loading indicator to the popup
		var loadIndicator = new Element("div",{
			"class":"loadindicator"
		});
		popupContents.adopt(loadIndicator);
	}
}

function postDisplay(container, event)
{
	// Add cancellation/altered classes
	if (event.alterations.any) 
	{
		infoPopup.addClass("popup-altered");
	
		if (event.alterations.cancelled) {
			var cancelledText = new Element("p", {
				"class":"cancelled-message",
				"html":"Cancelled"
			});
			infoPopup.adopt(cancelledText);
		}
	}
	
	// Add extra info not included in the basic event display
	switch(event.type.toLowerCase()) {
		case "walk":
			// Transport details
			var transportText = "";
			if (event.meetPoint.id != 7) // TODO: Remove magic number
			{
				// Parse the meeting time
				var meetTime = new Date(event.meetPoint.meetTime*1000);
				transportText += "Meet at "+meetTime.format("%H:%M")+" at ";
			}
									
			var transport = new Element("p", {
				"class":"transport",
				"html":"<span>Transport:</span> " + transportText
			});
			if (event.meetPoint.location != null)
			{	
				var transportLink = new Element("a", {
					"title":"Map of meeting point",
					"href":"http://www.streetmap.com/loc/N"+event.meetPoint.location.lat+",E"+event.meetPoint.location.lng,
					"target":"_blank",
					"rel":"map-transport",
					"html":event.meetPoint.longDesc
				});
				transportLink.addEvent("click",function(event){
					event.stop();
					showMap();
					map.showPoint(currentEvent.id, 'meet');
				});
				transport.adopt(transportLink);

				transport.adopt(document.createTextNode(". "));
			}
			else
				transport.adopt(document.createTextNode(event.meetPoint.longDesc+". "));
			
			if (event.meetPoint.extra != null)
				transport.adopt(document.createTextNode(event.meetPoint.extra));
			
			if (event.alterations.placeTime)
				transport.addClass("altered");
			
			// Inject this after the start
			var start = container.getElement("p.start");
			start.grab(transport, "after");
			
			// Leader phone number
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
			
			// This replaces the existing leader info, which just includes the name
			leader.replaces(container.getElement("p.leader"));
			
			break;
		case "weekend":
			/*var area = new Element("span", {
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
			if (event.contact)
			{
				var contact = new Element("p",{
					"class":event.type.toLowerCase()+"booking",
					"html":"<span>Contact:</span> "+event.contact
				});
			}
			var paymentDue = new Element("p",{
				"class":"paymentdue",
				"html":"<span>Payment due:</span> "+timestampToDate(event.paymentDue)
			});
			if (event.alterations.organiser)
				contact.addClass("altered");
			eventInfo.adopt(moreInfo,places,bookingsOpen,contact,paymentDue);*/
			break;
		case "social":
			/*if (event.bookingsInfo)
			{
				var contact = new Element("p",{
					"class":event.type.toLowerCase()+"booking",
					"html":"<span>Contact:</span> "+event.bookingsInfo
				});
			}
			if (event.alterations.organiser)
				contact.addClass("altered");
			if (contact != undefined)
				eventInfo.adopt(contact);
			
			// Get the start and end time
			// For new members, take the new member times if they exist and the normal times if not.
			// Otherwise, just use normal times
			var startTime, endTime;
			
			if (newMembers && event.newMemberStart != undefined)
				startTime = timestampToTime(event.newMemberStart);
			else if (event.start != undefined && timestampToTime(event.start) != "00:00")
				startTime = timestampToTime(event.start);
			
			if (newMembers && event.newMemberEnd != undefined)
				endTime = timestampToTime(event.newMemberEnd);
			else if (event.end != undefined)
				endTime = timestampToTime(event.end);
			
			// Now build the elements to display them (if they exist)
			// Only display the end time if the start time is set.
			if (startTime != undefined)
			{
				var start = new Element("p", {
					"class":"start",
					"html":"<span>Start:</span> "+startTime
				});
				eventInfo.adopt(start);
				if (endTime != undefined)
				{
					var end = new Element("p",{
						"class":"end",
						"html":"<span>End:</span> "+endTime
					});
					eventInfo.adopt(end);
				}
			}
			
			if (event.type.toLowerCase() == "weekend")
				eventInfo.adopt(bookingsOpen);
			*/
			break;
	}
	
	// Direct new members to general info page
	footer = new Element("p",{
		"class":"newMemberInfo",
		"html":"Coming on your first walk? Welcome! Please read this <a href='/walks/general-information'>information about walking with us</a>."
	});
	container.adopt(footer);
	
	if (event.hasMap)
		showMap(container);
}

function showMap(popup) {
	
	// TODO: Reference everthing from popup
	if (map == null)
	{
		// Show a map
		mapContainer = new Element("div",{
			"class":"map",
			"id":"map_"+currentEvent.id
		});
		mapContainer.inject(footer,'before');
		map = new SWGMap("map_"+currentEvent.id);
		switch (currentEvent.type)
		{
			case "Walk":
				var wi = map.addWalkInstance(currentEvent.id);
				map.addLoadedHandler(function()
				{
					// Load the route when we've got the walk
					var route = new Route("Planned route");
					route.load("walkinstance", wi.id, 10, wi);
					map.showPoint(currentEvent.id, "start", 13);
					map.zoomToFit();
				});
				
				break;
			case "Social":
				map.addSocial(currentEvent.id);
				map.setDefaultMap("street");
				break;
			case "Weekend":
				map.addWeekend(currentEvent.id);
				map.setDefaultMap("street"); // Landscape map doesn't work well at small scales
				break;
		}
		
		// Reset the position to fit the map
		infoPopup.position(popupPosition());
	}
}

/**
 * Fade the popup out and destroy it
 */
function hidePopup() {
	if (infoPopup != undefined && infoPopup != null)
	{
		infoPopup.fade("out");
		infoPopup.dispose.delay(500); // After the fade
	}
	
}

function setupHighlighting()
{
	if (localStorage.highlightedEvents)
		highlightedEvents = JSON.parse(localStorage.highlightedEvents);
	else
		highlightedEvents = new Object();
	
	var events = $$("div.events li");
	events.each(
		function (evt)
		{
			var eventType = evt.id.substr(0,evt.id.indexOf("_"));
			var eventID = evt.id.substr(evt.id.indexOf("_")+1);
			
			if (
					highlightedEvents[eventType] != undefined &&
					highlightedEvents[eventType][eventID] != undefined &&
					highlightedEvents[eventType][eventID]
				)
			evt.addClass("highlighted");
		}
	);
}

// TODO: Dynamically generate the popup - only create it for JS peeps
window.addEvent('domready', registerPopupLinks);
if (hasLocalStorage())
{
	window.addEvent('domready', setupHighlighting);
}
var popup = document.id("walk-popup");
//popup.set("opacity",0);

})();