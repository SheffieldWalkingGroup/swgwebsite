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
		this.landscapeMap = new OpenLayers.Layer.OSM(
				"Landscape",
				["http://a.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png",
			       "http://b.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png",
			       "http://c.tile3.opencyclemap.org/landscape/$\{z}/$\{x}/$\{y}.png"],
				{sphericalMercator:true}
		);
		this.landscapeMap.attribution = attribution;
		this.map.addLayer(this.landscapeMap);
		this.map.addControl(new OpenLayers.Control.LayerSwitcher());
		this.markers = new OpenLayers.Layer.Markers("Locations");
		this.map.addLayer(this.markers); // TODO: Keep marker layer at the top
		
		// Initialise other data
		this.walks = new Array();
		this.routes = new Array();
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
	
	/**
	 * Called when a walk has loaded
	 * @access private
	 */
	loadedWalk: function(walk)
	{
		this.walks[walk.id] = walk;
		
		// Try to load a route
		walk.loadRoute(this);
		
		// Create markers for start and (possibly) end points
		// Note: need to transform from WGS1984 to (usually) Spherical Mercator projection
		var start = new OpenLayers.LonLat(walk.startLatLng.lng,walk.startLatLng.lat).transform(
		    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		);
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
		if (walk.meetPoint != undefined && walk.meetPoint.meetPoint != undefined)
		{
			var meet = new OpenLayers.LonLat(walk.meetPoint.meetPoint.lng,walk.meetPoint.meetPoint.lat).transform(
				    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		    );
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
	 * Destroy the map
	 */
	destroy: function()
	{
		this.map.destroy();
	}
});

var Walkable = new Class({
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
				for (i in data)
				{
					if (data.hasOwnProperty(i))
					{
						self[i] = data[i];
					}
				}
				requestor.loadedWalk(self);
			}
			// TODO: onFailure
		});
		loader.get();
	},
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
				for (i in data)
				{
					if (data.hasOwnProperty(i))
					{
						self[i] = data[i];
					}
				}
				requestor.loadedWalk(self);
			}
			// TODO: onFailure
		});
		loader.get();
	},
});

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