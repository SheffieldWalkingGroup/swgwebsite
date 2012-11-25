/*
 * TODO: Allow manual closing
 * TODO: Auto-close map when another opens
 * TODO: Support non-maps
 * TODO: Map controls (pan to start, end, meeting place)
 * 
 * TODO: Go full OOP, with each wrapper represented by its own JS object, containing a separate mapContainer, map, etc.
 */

var mapContainer, mapElement, map, lastOpenedEventWrapper;

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
		
		// Get the map link
		this.mapLink = wrapper.getElements('a[rel="toggle-map"]')[0];
		if (this.mapLink == undefined)
			return;
		
		var self = this;
		this.mapLink.addEvent('click',function(event)
				{
					self.openMap();
					event.stop();
				}
		);
		
	    this.eventType = wrapper.id.substring(0,wrapper.id.indexOf("_"));
	    this.eventID   = wrapper.id.substring(wrapper.id.indexOf("_")+1);
	    
	    // Set up the map container
	    this.mapContainer = new Element("div",{
			class:"map-container",
			style:"height:0px"
		});
		
		this.mapElement = new Element("div",{
			id:"map_"+this.eventType+"_"+this.eventID,
			class:"map"
		});
		this.mapContainer.adopt(this.mapElement);
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
			this.map.addWalkInstance(this.eventID);
		
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
	}
});

var events = new Array();

function registerMapLinks()
{
	events = new Array();
	
	var eventElements = $(document.body).getElements("div.event");
	for (var i=0; i<eventElements.length; i++)
	{
		var event = new EventWrapper(eventElements[i]);
		events.push(event);
	}
}

var getWrapperFromLink = function(link)
{
	var wrapper = link;
	while (!wrapper.getParent().hasClass("event"))
    {
      wrapper = wrapper.getParent();
    }
	return wrapper;
}

var clickOpenLink = function(event)
{
	// Find the event wrapper
    var wrapper = getWrapperFromLink(event.target);
    
    // Close any existing maps (unless it's the one we're opening)
    if (
    		map != undefined && map != null && 
    		lastOpenedEventWrapper != wrapper && 
    		lastOpenedEventWrapper.contains(mapContainer)
	)
	{
    	closeMap(lastOpenedEventWrapper, true);
	}
    
    
    // Change this link to a close map link
    var link = event.target;
    link.set('html', "Hide map");
    link.removeEvent('click',clickOpenLink);
    link.addEvent('click',clickCloseLink);
    
    lastOpenedEventWrapper = wrapper;

    // TODO: Potential race condition
    openMap(wrapper);
    event.stop();
}

var clickCloseLink = function(event)
{
	closeMap(getWrapperFromLink(event.target),false);
	var link = event.target;
    link.set('html', "Show map");
    link.removeEvent('click',clickCloseLink);
    link.addEvent('click',clickOpenLink);
	event.stop();
}

var openMap = function(wrapper)
{
	// Remove any existing container
	mapContainer.dispose();
	
	// Get the event type & ID
    var wrapperID = wrapper.getParent().id;
    var eventType = wrapperID.substring(0,wrapperID.indexOf("_"));
    var eventID   = wrapperID.substring(wrapperID.indexOf("_")+1);
    
    wrapper.adopt(mapContainer);
    
    // Create the map
    if (map != undefined && map != null)
	{
    	// map.destroy();
	}
    map = new SWGMap('map');

    // TODO: Handle non-walks
    map.addWalkInstance(eventID);
    
    // TODO: Add the map & controls
    
    // Animate the map opening
    var openFx = new Fx.Tween(mapContainer, {
    	transition:Fx.Transitions.Quad.easeOut
    });
    openFx.start("height",400);
    
}

var closeMap = function(wrapper, opening)
{
	var closeFxOptions = {
			transition:Fx.Transitions.Quad.easeIn,
			onComplete: function() {
				if (!opening)
					mapContainer.dispose();
				
				// Restore the show map link
				var link = wrapper.getElements('a[rel="toggle-map"]')[0];
				link.set('html', "Show map");
			    link.removeEvent('click',clickCloseLink);
			    link.addEvent('click',clickOpenLink);
			}
	};
	var closeFx;
	if (opening)
	{
		// Create a dummy element to hold the map open as it slides close
		var dummyElement = new Element("div",{
			class:"map-container",
			style:"height:"+mapContainer.getSize().y+"px"
		});
		mapContainer.getParent().adopt(dummyElement);
		mapContainer.dispose();
		map.destroy();
		
		// Close the old map and open the new one
		closeFx = new Fx.Tween(dummyElement, closeFxOptions);
	}
	else
	{
		closeFx = new Fx.Tween(mapContainer,closeFxOptions);
	}
	closeFx.start("height", 0);
	
}