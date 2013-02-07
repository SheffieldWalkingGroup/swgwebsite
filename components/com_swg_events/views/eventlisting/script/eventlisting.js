/*
 * TODO: Support non-maps
 */

var mapContainer, mapElement, map, lastOpenedEventWrapper, highlightedEvents, totalEvents, loadingEvents=false, apiParams, loadedEvents=100, canLoadMore;

var EventWrapper = new Class({
	wrapper : null,
	content : null,
	eventType : null,
	eventID : null,
	mapOpen : false,
	map : null,
	mapLink : null,
	
	/**
	 * Initialises an event wrapper
	 * @param wrapper the element wrapping this event 
	 */
	initialize : function(wrapper)
	{
		this.wrapper = wrapper;
		this.content = wrapper.getElements('div.content')[0];
		var self = this;
		
	    this.eventType = wrapper.id.substring(0,wrapper.id.indexOf("_"));
	    this.eventID   = wrapper.id.substring(wrapper.id.indexOf("_")+1);
	    
	    // Standard map link
		// Get the map link
		this.mapLink = wrapper.getElements('a[rel="toggle-map"]')[0];
		if (this.mapLink != undefined)
		{
			this.mapLink.addEvent('click',function(event)
			{
				self.openMap();
				event.stop();
			});
		}
	    
	    if (this.eventType == "walk")
    	{
			
			
			// Add events to other links
			var startLink = wrapper.getElements('a[rel="map-start"]')[0];
			startLink.addEvent('click',function(event)
			{
				event.stop();
				self.openMap();
				self.map.showPoint(self.eventID, 'start');
			});
			var endLink = wrapper.getElements('a[rel="map-end"]')[0];
			if (endLink != undefined)
			{
				endLink.addEvent('click',function(event)
				{
					event.stop();
					self.openMap();
					self.map.showPoint(self.eventID, 'end');
				});
			}
			var meetLink = wrapper.getElements('a[rel="map-transport"]')[0];
			if (meetLink != undefined)
			{
				meetLink.addEvent('click',function(event)
				{
					event.stop();
					self.openMap();
					self.map.showPoint(self.eventID, 'meet');
				});
			}
    	}
	    else
    	{
			
			// Socials & weekends only have one location
			var generalLink = wrapper.getElements('a[rel="map"]')[0];
			if (generalLink != undefined)
			{
				generalLink.addEvent('click',function(event)
				{
					event.stop();
					self.openMap();
				});
			}
    	}
	    
	    // Set up the map container
	    this.mapContainer = new Element("div",{
			"class":"map-container",
			"style":"height:0px"
		});
		
		this.mapElement = new Element("div",{
			"id":"map_"+this.eventType+"_"+this.eventID,
			"class":"map"
		});
		this.mapContainer.adopt(this.mapElement);
		
		// Set up the highlighting controls
		if (localStorage)
		{
			var highlightWrapper = new Element("p");
			this.highlightLink = new Element("a",{
				"href":"#",
				"html":"Highlight",
				"title":"Highlight this "+this.eventType.toLowerCase()+" on your computer"
			});
			highlightWrapper.adopt(this.highlightLink);
			wrapper.getElements('[class="controls"]')[0].adopt(highlightWrapper);
			this.highlightLink.addEvent('click',function(event)
			{
				event.stop();
				self.highlightEvent();
			});
			
			// Should this event already be highlighted?
			if (
					highlightedEvents[this.eventType] != undefined &&
					highlightedEvents[this.eventType][this.eventID] != undefined &&
					highlightedEvents[this.eventType][this.eventID]
				)
				this.highlightEvent();
		}
	},
		
	openMap : function()
	{
		if (this.mapOpen)
			return;
		
		// Close all (other) map elements
		for (var i=0; i<events.length; i++)
		{
			events[i].closeMap();
		}
		
		// Put the map elements in place
		this.content.adopt(this.mapContainer);
		
		// Create the map
		this.map = new SWGMap("map_"+this.eventType+"_"+this.eventID);
		if (this.eventType == "walk")
		{
			this.map.addWalkInstance(this.eventID);
			this.map.setDefaultMap("landscape");
		}
		else if (this.eventType == "social")
		{
			this.map.addSocial(this.eventID);
			this.map.setDefaultMap("street");
		}
		else if (this.eventType == "weekend")
		{
			this.map.addWeekend(this.eventID);
			this.map.setDefaultMap("street"); // Landscape map doesn't work well at small scales
		}
		
		// Open the map
		var self = this;
		var openFx = new Fx.Tween(this.mapContainer, {
			transition:Fx.Transitions.Quad.easeOut,
			onComplete: function() {
				self.mapOpen = true;
				
				self.mapLink.set('html',"Hide map");
				self.mapLink.removeEvents();
				self.mapLink.addEvent('click',function(e){
					self.closeMap();
					e.stop();
				});
			}
		});
		openFx.start("height",400);
	},
	
	closeMap : function()
	{
		if (!this.mapOpen)
			return;
		
		var self = this;
		var closeFx = new Fx.Tween(this.mapContainer, {
			transition:Fx.Transitions.Quad.easeIn,
			onComplete: function() {
				self.mapOpen = false;
				self.map.destroy();
				self.mapContainer.dispose();
				
				self.mapLink.set('html',"Show map");
				self.mapLink.removeEvents();
				self.mapLink.addEvent('click',function(e){
					self.openMap();
					e.stop();
				});
			}
		});
		closeFx.start("height",0);
	},
	
	highlightEvent : function()
	{
		this.wrapper.addClass("highlighted");
		
		// Store the highlighting
		if (highlightedEvents[this.eventType] == undefined)
		{
			highlightedEvents[this.eventType] = new Object();
		}
		highlightedEvents[this.eventType][this.eventID] = true;
		localStorage.highlightedEvents = JSON.stringify(highlightedEvents);
		
		// Swap the event type
		this.highlightLink.removeEvents();
		var self = this;
		this.highlightLink.set('html','Remove highlight');
		this.highlightLink.set('title','Remove the highlight from this '+this.eventType.toLowerCase());
		this.highlightLink.addEvent('click',function(e){
			e.stop();
			self.unhighlightEvent();
			
		});
	},
	
	unhighlightEvent : function()
	{
		this.wrapper.removeClass("highlighted");
		
		// Clear the highlighting from storage
		delete highlightedEvents[this.eventType][this.eventID];
		localStorage.highlightedEvents = JSON.stringify(highlightedEvents);
		
		// Swap the event type
		this.highlightLink.removeEvents();
		var self = this;
		this.highlightLink.set('html','Highlight');
		this.highlightLink.set('title',"Highlight this "+this.eventType.toLowerCase()+" on your computer");
		this.highlightLink.addEvent('click',function(e){
			e.stop();
			self.highlightEvent();
			
		});
	}
});

var events = new Array();

function registerMapLinks()
{
	// Load which events have been highlighted
	if (localStorage)
	{
		if (localStorage.highlightedEvents)
			highlightedEvents = JSON.parse(localStorage.highlightedEvents);
		else
			highlightedEvents = new Object();
	}
	
	events = new Array();
	
	var eventElements = $(document.body).getElements("div.event");
	for (var i=0; i<eventElements.length; i++)
	{
		var event = new EventWrapper(eventElements[i]);
		events.push(event);
	}
}

function scrolled(evt)
{
	if (!loadingEvents && loadedEvents < totalEvents && window.getScroll().y == (window.getScrollSize().y - window.getSize().y)) {
		hitBottom(evt);
	}
}

function hitBottom()
{
	loadingEvents = true;
	
	// TODO: More robust
	var container = $$(".main")[0];
	var before = $$(".postcontent")[0];
	
	// Show a loading bar
	var loadBar = new Element("div", {"class":"loadbar"});
	var loadIndicator = new Element("div",{
		"class":"loadindicator"
	});
	loadBar.adopt(loadIndicator);
	loadBar.inject(before, "before");
	
	var request = new Request.JSON({
		url:"/api/eventlisting.js?format=json&"+apiParams+"&offset="+loadedEvents,
		onSuccess: function(data)
		{
			for (var i=0; i<data.length; i++)
			{
				// TODO: Could link in with walk, social, weekend objects in maps.js in future
				var event = data[i];
				
				var evtWrap = new Element("div",{
					"id":event.type.toLowerCase()+"_"+event.id,
					"class":"event published"
				});
				var evtContent = new Element("div",{"class":"content "+event.type.toLowerCase()});
				
				evtWrap.adopt(evtContent);
				evtWrap.inject(before,"before");
				
				displayEvent(event, evtContent, false);
				
				// Move extra stuff
				evtContent.getElements(".description").adopt(evtContent.getElements(".icons"));
				evtContent.getElements(".eventinfo").adopt(new Element("div",{"class":"controls"}));
				
				// Run this through normal event setup
				var event = new EventWrapper(evtWrap);
				events.push(event);
			}
			loadedEvents += data.length;
			loadingEvents = false;
			loadBar.dispose();
		}
	});
	request.get();
	
	
}