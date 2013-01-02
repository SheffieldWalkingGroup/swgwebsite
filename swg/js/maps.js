/**
 * A wrapper around the map widget.
 * Handles translating SWG-ish concepts like walks and routes
 * into mappy concepts like markers and linestrings.
 * TODO: Is event support useful?
 */
var SWGMap = new Class({
	/**
	 * Creates a new map in the specified container
	 * TODO: Allow different maps?
	 * @param Element container Element containing the map
	 */
	initialize: function(container)
	{
		// Keep a reference to the container
		this.container = container;
		
		// Add a loading indicator
		var loadIndicator = new Element("div",{
			"class":"loadindicator"
		});
		document.getElementById(container).adopt(loadIndicator);
		
		// Create a new map
		this.map = new OpenLayers.Map(container);
		var attribution = "Map data &copy; <a href='http://www.openstreetmap.org' target='_blank'>OpenStreetMap</a> contributors. Style &copy; <a href='http://www.opencyclemap.org' target='_blank'>OpenCycleMap</a>."
		
		// Set up OpenCycleMap layer
		this.cycleMap = new OpenLayers.Layer.OSM(
				"OpenCycleMap",
				["http://a.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png",
			       "http://b.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png",
			       "http://c.tile.opencyclemap.org/cycle/$\{z}/$\{x}/$\{y}.png"],
				{sphericalMercator:true}
		);
		this.cycleMap.attribution = attribution;
		this.cycleMap.events.register('loadend',this,function(){
			loadIndicator.dispose();
		});
		this.map.addLayer(this.cycleMap);
		
		// Set up Landscape layer
		this.landscapeMap = new OpenLayers.Layer.OSM(
				"Landscape",
				["http://a.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png",
			       "http://b.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png",
			       "http://c.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png"],
				{sphericalMercator:true}
		);
		this.landscapeMap.attribution = attribution;
		this.landscapeMap.events.register('loadend',this,function(){
			loadIndicator.dispose();
		});
		this.map.addLayer(this.landscapeMap);
		
		// Set up street map layer
		this.streetMap = new OpenLayers.Layer.OSM("Street map");
		this.streetMap.events.register('loadend',this,function(){
			loadIndicator.dispose();
		});
		this.map.addLayer(this.streetMap);

		this.map.addControl(new OpenLayers.Control.LayerSwitcher());
		this.markers = new OpenLayers.Layer.Markers("Locations");
		this.map.addLayer(this.markers); // TODO: Keep marker layer at the top
		
		// Initialise other data
		this.walks = new Array();
		this.routes = new Array();
		this.socials = new Array();
		this.weekends = new Array();
		

	},
	
	setDefaultMap: function(style)
	{
		switch(style.toLowerCase())
		{
			case "cycle":
				this.map.setBaseLayer(this.cycleMap);
				break;
			case "landscape":
				this.map.setBaseLayer(this.landscapeMap);
				break;
			case "street":
				this.map.setBaseLayer(this.streetMap);
				break;
		}
	},
	
	/**
	 * Adds a click handler to the map
	 * @param func Function to call when the user clicks on the map. Must take two parameters: the event, and a LonLat object.
	 */
	addClickHandler: function(func)
	{
		// TODO: Handle multiple handlers
		OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {                
			defaultHandlerOptions: {
				'single': true,
				'double': false,
				'pixelTolerance': 0,
				'stopSingle': false,
				'stopDouble': false
			},

			initialize: function(options) {
				this.handlerOptions = OpenLayers.Util.extend(
					{}, this.defaultHandlerOptions
				);
				OpenLayers.Control.prototype.initialize.apply(
					this, arguments
				); 
				this.handler = new OpenLayers.Handler.Click(
					this, {
						'click': this.trigger
					}, this.handlerOptions
				);
			}, 

			trigger: function(e) {
				var lonlat = this.map.getLonLatFromViewPortPx(e.xy);
				lonlat = lonlat.transform(this.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326"));
				
				// Pass the converted coordinates to the specified function
				func(e, lonlat);
			}
		});
		
		var click = new OpenLayers.Control.Click();
		this.map.addControl(click);
		click.activate();
	},
	
	/**
	 * Adds a new walk to the map.
	 * All that gets shown is the start and end markers.
	 * Routes are added separately
	 */
	addWalk: function(walkID)
	{
		// Load the walk
		// TODO: Handle failed loads
		var walk = new Walk();
		walk.load(walkID,this);
	},
	
	addWalkInstance: function(wiID)
	{
		var wi = new WalkInstance();
		wi.load(wiID,this);
	},
	
	addSocial: function (socialID)
	{
		var social = new Social();
		social.load(socialID, this);
	},
	
	/**
	 * Called when a walk has loaded
	 * @access private
	 */
	loadedWalk: function(walk)
	{
		this.walks[walk.id] = {
				'id':walk.id,
				'walk':walk,
				'start':null,
				'end':null,
				'meet':null
		};
		
		// Try to load a route
		walk.loadRoute(this);
		
		// Create markers for start and (possibly) end points
		// Note: need to transform from WGS1984 to (usually) Spherical Mercator projection
		var start = new OpenLayers.LonLat(walk.startLatLng.lng,walk.startLatLng.lat).transform(
		    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		);
		this.walks[walk.id].start = start;
		var startIcon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
		var startMarker = new OpenLayers.Marker(start, startIcon);
		
		var startPopup = new OpenLayers.Popup.FramedCloud("StartPopup",
		    start, null,
		    "Start: "+walk.startGridRef+"<br>"+walk.startPlaceName+"<br><a href='http://www.streetmap.co.uk/loc/"+walk.startGridRef+"' target='_blank'>View on Ordnance Survey map</a>", startIcon, true
		);
		// Need to centre the map before adding popups
		// TODO: Temporary - should zoom to bounding box around start & end (or route if available)
		// If a circular walk, don't zoom in too close either
		// Note: zoom 13 shows lots of details, incl. paths. Should probably stick to that:
		// route line shows where the finish is
		this.map.setCenter(start,13);
		
		this.map.addPopup(startPopup);
		  
		startMarker.events.register('mousedown',startMarker,function(e) { 
		    startPopup.toggle(); OpenLayers.Event.stop(e);
		});
		this.markers.addMarker(startMarker);
		
		if (walk.isLinear == 1)
		{
			var end = new OpenLayers.LonLat(walk.endLatLng.lng,walk.endLatLng.lat).transform(
			    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
			);
			this.walks[walk.id].end = end;
			var endIcon = new OpenLayers.Icon("/images/icons/red.png",{w:8,h:8},{x:-4,y:-4});
		    var endMarker = new OpenLayers.Marker(end, endIcon);
		    var endPopup = new OpenLayers.Popup.FramedCloud("EndPopup",
		      end, null,
		      "End: "+walk.endGridRef+"<br>"+walk.endPlaceName+"<br><a href='http://www.streetmap.co.uk/loc/"+walk.endGridRef+"' target='_blank'>View on Ordnance Survey map</a>", endIcon, true
		    );
		    this.map.addPopup(endPopup);
		    endMarker.events.register('click',endMarker,function(e) { endPopup.toggle(); OpenLayers.Event.stop(e);});
		    this.markers.addMarker(endMarker);
		}
		
		// Get the meeting point for WalkInstances
		if (walk.meetPoint != undefined && walk.meetPoint.location != undefined)
		{
			var meet = new OpenLayers.LonLat(walk.meetPoint.location.lng,walk.meetPoint.location.lat).transform(
				    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		    );
			this.walks[walk.id].meet = meet;
			var meetIcon = new OpenLayers.Icon("/images/icons/yellow.png",{w:8,h:8},{x:-4,y:-4});
			var meetMarker = new OpenLayers.Marker(meet, meetIcon);
			var meetText = "Meet: "+walk.meetPoint.longDesc;
			if (walk.meetPoint.extra != "")
				meetText += "<br>"+walk.meetPoint.extra;
			var meetPopup = new OpenLayers.Popup.FramedCloud("MeetPopup",
					meet, null,
					meetText, meetIcon, true
			);
			this.map.addPopup(meetPopup);
			meetMarker.events.register('click',meetMarker,function(e) {meetPopup.toggle(); OpenLayers.Event.stop(e);});
			this.markers.addMarker(meetMarker);
		}
		this.map.setCenter(start,14);
		
		// Do we have a queued move?
		if (this.queuedMove != undefined && this.queuedMove != null)
		{
			this.showPoint(this.queuedMove.walkID, this.queuedMove.pointType, this.queuedMove.zoom);
		}
	},
	
	loadedSocial: function(social)
	{
		this.socials[social.id] = {
				'id':social.id,
				'walk':social,
				'location':null
		};
		
		// Create a marker for the location
		// We should have latLng by now
		// Note: need to transform from WGS1984 to (usually) Spherical Mercator projection
		var location = new OpenLayers.LonLat(social.latLng.lng,social.latLng.lat).transform(
		    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		);
		this.socials[social.id].location = location;
		var icon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
		var marker = new OpenLayers.Marker(location, icon);
		
		var popup = new OpenLayers.Popup.FramedCloud("Popup",
		    location, null,
		    social.location.replace(/\n/g,"<br>"), icon, true
		);
		
		// Need to centre the map before adding popups
		// TODO: Temporary - should zoom to bounding box around start & end (or route if available)
		// If a circular walk, don't zoom in too close either
		// Note: zoom 13 shows lots of details, incl. paths. Should probably stick to that:
		// route line shows where the finish is
		this.map.setCenter(location,13);
		
		this.map.addPopup(popup);
		  
		marker.events.register('mousedown',marker,function(e) { 
			popup.toggle(); OpenLayers.Event.stop(e);
		});
		this.markers.addMarker(marker);
		
		this.map.setCenter(location,14);
		
		// Do we have a queued move?
		//if (this.queuedMove != undefined && this.queuedMove != null)
		//{
//			this.showPoint(this.queuedMove.walkID, this.queuedMove.pointType, this.queuedMove.zoom);
		//}
	},
	
	/**
	 * Called when a route has loaded
	 */
	loadedRoute: function(route, walk)
	{
		var points = new Array();
		
		var srcProjection = new OpenLayers.Projection("EPSG:4326");
		
		var rt, rtFeature, rtLayer;
		
		if (route.waypoints.length != 0)
		{
			
			// If we have a real route, draw it as a LineString
			for (pt in route.waypoints)
			{
				if (route.waypoints[pt].hasOwnProperty('lng'))
				{
					var point = new OpenLayers.Geometry.Point(route.waypoints[pt].lng, route.waypoints[pt].lat).transform(srcProjection, this.map.getProjectionObject());
					points.push(point);
				}
			}
			rt = new OpenLayers.Geometry.LineString(points);
			
			rtFeature = new OpenLayers.Feature.Vector(
					rt, null, {
						strokeColor:"#FF9555",
						strokeOpacity:1,
						strokeWidth:3,
						pointRadius:3,
						pointerEvents:"visiblePainted"
					}
			);
		}
		else if (walk.isLinear == 1)
		{
			// Dotted line for linear walks
			var startPt = new OpenLayers.Geometry.Point(walk.startLatLng.lng, walk.startLatLng.lat).transform(srcProjection, this.map.getProjectionObject());
			var endPt   = new OpenLayers.Geometry.Point(walk.endLatLng.lng,   walk.endLatLng.lat  ).transform(srcProjection, this.map.getProjectionObject());
			
			rt = new OpenLayers.Geometry.LineString([startPt, endPt]);
			
			rtFeature = new OpenLayers.Feature.Vector(
					rt, null, {
						strokeColor:"#FF9555",
						strokeOpacity:1,
						strokeWidth:3,
						pointRadius:3,
						pointerEvents:"visiblePainted",
						strokeDashstyle:"dash"
					}
			);
		}
		
		// If we have some sort of route, add it
		if (rt != undefined && rt != null)
		{
			rtLayer = new OpenLayers.Layer.Vector("Route");
			this.map.addLayer(rtLayer);
			rtLayer.addFeatures([rtFeature]);
			
			//Move the markers to the top layer
			this.map.setLayerZIndex(this.markers, this.map.getNumLayers());
		}
	},
	
	/**
	 * Scroll to a particular point on a walk
	 * @param int walkID Walk ID to show
	 * @param pointType Type of point: 'start','end','meet'
	 */
	showPoint: function(walkID, pointType, zoom)
	{
		if (zoom == undefined)
			zoom = 14;
		
		// If we haven't loaded the walk yet, queue the move.
		// If the walk never loads, this will just be ignored/overwritten
		if (this.walks[walkID] == undefined)
		{
			this.queuedMove = {'walkID':walkID, 'pointType':pointType, 'zoom':zoom};
			return;
		}
		pointType = pointType.toLowerCase();
		var walk = this.walks[walkID];
		
		if (pointType == "start" && walk.start != null)
		{
			this.map.setCenter(this.walks[walkID].start,zoom);
		}
		else if (pointType == "end" && walk.end != null)
		{
			this.map.setCenter(walk.end,zoom);
		}
		else if (pointType == "meet" && walk.meet != null)
		{
			this.map.setCenter(walk.meet,zoom)
		}
	},
	/**
	 * Destroy the map
	 */
	destroy: function()
	{
		this.map.destroy();
	}
});

var Event = new Class({
	parseData: function(data)
	{
		for (var i in data)
		{
			if (data.hasOwnProperty(i))
			{
				this[i] = data[i];
			}
		}
	}
})

var Walkable = new Class({
	Extends: Event,
	initialize: function()
	{
		this.route = null;
	},
	
	/**
	 * Loads the route for this walk, and return it to the requestor.
	 * This is a wrapper, used for the purpose of adding the walk object to the notification to the requestor
	 * Returns false if no route available.
	 */
	loadRoute: function(requestor)
	{
		this.loadedRoute = function(route)
		{
			requestor.loadedRoute(route, this);
		};
			
		var route = new Route(this);
		route.load(this.type.toLowerCase(),this.id,this);
	}
	
})

/**
 * A walk.
 * TODO: May be useful elsewhere, and need moving to another file
 */
var Walk = new Class({
	Extends: Walkable,
	type: "Walk",
	load: function(walkID,requestor)
	{
		var self=this;
		var loader = new Request.JSON({
			url: "/api/walkdetails?id="+walkID+"&format=json",
			onSuccess: function(data)
			{
				self.parseData(data);
				requestor.loadedWalk(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
});

/**
 * A walk instance.
 * TODO: May be useful elsewhere, and need moving to another file
 */
var WalkInstance = new Class({
	Extends: Walkable,
	type:"WalkInstance",
	load: function(instanceID,requestor)
	{
		var self=this;
		var loader = new Request.JSON({
			url: "/api/eventlisting?eventtype=walk&id="+instanceID+"&format=json",
			onSuccess: function(data)
			{
				self.parseData(data);
				requestor.loadedWalk(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
});

var Social = new Class({
	Extends: Event,
	load: function(instanceID,requestor)
	{
		var self=this;
		var loader = new Request.JSON({
			url: "/api/eventlisting?eventtype=social&id="+instanceID+"&format=json",
			onSuccess: function(data)
			{
				self.parseData(data);
				
				// We need location data to exist. Use the social title if blank.
				if (self.location == undefined || self.location == null || self.location == "")
					self.location = self.name;
				
				requestor.loadedSocial(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
})

var Route = new Class({
	
	initialize: function(walkable)
	{
		if (walkable != undefined)
			this.walkable = walkable;
		
		this.waypoints = [];
	},
	
	/**
	 * Loads a route by route ID, walk ID, or any other supported search
	 * @param searchType String What to search by, e.g. "route" or "walk". See parser class for details
	 * @param id int ID of route/walk/other to search for
	 * @param requestor Object Object to notify when a route is loaded. loadedRoute(this) is called. 
	 */
	load: function(searchType, id, requestor)
	{
		var self=this;
		var loader = new Request.JSON({
			url: "/api/route?"+searchType+"id="+id+"&format=json",
			onSuccess: function(data)
			{
				for (i in data)
				{
					if (data.hasOwnProperty(i))
					{
						self[i] = data[i];
					}
				}
				requestor.loadedRoute(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
})
