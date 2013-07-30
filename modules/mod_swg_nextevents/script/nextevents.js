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
			onSuccess: function(event) {
				cachedEvents[eventType][eventID] = event;
				
				// Is this the event we want now?
				if (event.id == currentlyLoading)
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
