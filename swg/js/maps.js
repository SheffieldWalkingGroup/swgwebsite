/**
 * A wrapper around the map widget.
 * Handles translating SWG-ish concepts like walks and routes
 * into mappy concepts like markers and linestrings.
 * TODO: Is event support useful?
 */
var SWGMap = new Class({
	markerStyle: new OpenLayers.StyleMap({
		"default": {
			fillOpacity: 1,
			pointRadius: 6,
			strokeColor: "#FF9555",
			strokeWidth: 1,
			fillColor: "#000000"
		}
	}),
	markerVariants: {
		"default":{fillColor:"#FF9555"},
		"start": {fillColor: "#00dd00"},
		"end"  : {fillColor: "#ff0000"},
		"meet" : {fillColor: "#ffff00"}
	},
	styleContext: function(feature) { return feature;},
	
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
		document.id(container).adopt(loadIndicator);
		
		// Create a new map
		this.map = new OpenLayers.Map(container);
		var attribution = "Map data &copy; <a href='http://www.openstreetmap.org' target='_blank'>OpenStreetMap</a> contributors. Style &copy; <a href='http://www.opencyclemap.org' target='_blank'>OpenCycleMap</a>."
		
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
		
		// Set up street map layer
		this.streetMap = new OpenLayers.Layer.OSM("Street map");
		this.streetMap.events.register('loadend',this,function(){
			loadIndicator.dispose();
		});
		this.map.addLayer(this.streetMap);
		
		// Set up the styles
		this.markerStyle.addUniqueValueRules("default", "type", this.markerVariants);
		
		// Set up the locations marker layer
		// We also set up a click handler on this layer
		this.map.addControl(new OpenLayers.Control.LayerSwitcher());
		this.locations = new OpenLayers.Layer.Vector("Locations", {
			styleMap: this.markerStyle
		});
		this.map.addLayer(this.locations);
		
		var selectControl = new OpenLayers.Control.SelectFeature(
			this.locations, {
				clickFeature: this.clickLocation,
				onSelect: this.selectLocation,
				onUnselect: this.unselectLocation,
				autoActivate: true
			}
		);
		this.map.addControl(selectControl);
				
		// Initialise other data
		this.walks = new Array();
		this.routes = new Array();
		this.socials = new Array();
		this.weekends = new Array();
	},
	
	clickLocation: function(location)
	{
		if (location.click != undefined)
		{
			location.click();
		}
	},
	
	selectLocation: function(location)
	{
		if (location.select != undefined)
		{
			location.select();
		}
	},
	
	unselectLocation: function(location)
	{
		if (location.unselect != undefined)
		{
			location.unselect();
		}
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
	
	setZoom: function(zoom)
	{
		this.map.zoomTo(8);
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
	
	addWeekend: function (weekendID)
	{
		var weekend = new Weekend();
		weekend.load(weekendID, this);
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
		
		var startMarker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(start.lon, start.lat));
		startMarker.attributes.type = "start";
		
		var startPopup = new OpenLayers.Popup.FramedCloud("StartPopup",
		    start, null,
		    "Start: "+walk.startGridRef+"<br>"+walk.startPlaceName+"<br><a href='http://www.streetmap.co.uk/loc/"+walk.startGridRef+"' target='_blank'>View on Ordnance Survey map</a>", null, true
		);
		// Need to centre the map before adding popups
		// TODO: Temporary - should zoom to bounding box around start & end (or route if available)
		// If a circular walk, don't zoom in too close either
		// Note: zoom 13 shows lots of details, incl. paths. Should probably stick to that:
		// route line shows where the finish is
		this.map.setCenter(start,13);
		
		this.map.addPopup(startPopup);
		  
		startMarker.click = function(e) { 
		    startPopup.toggle();
		};
		
		this.locations.addFeatures([startMarker]);
		
		if (walk.isLinear == 1)
		{
			var end = new OpenLayers.LonLat(walk.endLatLng.lng,walk.endLatLng.lat).transform(
			    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
			);
			this.walks[walk.id].end = end;
			
		    var endMarker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(end.lon, end.lat));
			endMarker.attributes.type = "end";
			
		    var endPopup = new OpenLayers.Popup.FramedCloud("EndPopup",
		      end, null,
		      "End: "+walk.endGridRef+"<br>"+walk.endPlaceName+"<br><a href='http://www.streetmap.co.uk/loc/"+walk.endGridRef+"' target='_blank'>View on Ordnance Survey map</a>", null, true
		    );
		    this.map.addPopup(endPopup);
			
			endMarker.click = function() {
				endPopup.toggle();
			};
		    this.locations.addFeatures([endMarker]);
		}
		
		// Get the meeting point for WalkInstances
		if (walk.meetPoint != undefined && walk.meetPoint.location != undefined)
		{
			var meet = new OpenLayers.LonLat(walk.meetPoint.location.lng,walk.meetPoint.location.lat).transform(
				    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		    );
			this.walks[walk.id].meet = meet;
			
			var meetMarker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(meet.lon, meet.lat));
			meetMarker.attributes.type = "meet";
			
			var meetText = "Meet: "+walk.meetPoint.longDesc;
			if (walk.meetPoint.extra != "")
				meetText += "<br>"+walk.meetPoint.extra;
			var meetPopup = new OpenLayers.Popup.FramedCloud("MeetPopup",
					meet, null,
					meetText, null, true
			);
			this.map.addPopup(meetPopup);
			meetMarker.click = function() {
				meetPopup.toggle();
				
			};
			this.locations.addFeatures([meetMarker]);
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
		var marker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(location.lon, location.lat));
		marker.attributes.type = "default";
		
		var popup = new OpenLayers.Popup.FramedCloud("Popup",
		    location, null,
		    social.location.replace(/\n/g,"<br>"), null, true
		);
		
		// Need to centre the map before adding popups
		this.map.setCenter(location,15);
		
		this.map.addPopup(popup);
		  
		marker.click = function() { 
			popup.toggle();
		};
		this.locations.addFeatures([marker]);
		
		this.map.setCenter(location,15);
		
		// Do we have a queued move?
		//if (this.queuedMove != undefined && this.queuedMove != null)
		//{
//			this.showPoint(this.queuedMove.walkID, this.queuedMove.pointType, this.queuedMove.zoom);
		//}
	},
	
	loadedWeekend: function(weekend)
	{
		this.weekends[weekend.id] = {
				'id':weekend.id,
				'walk':weekend,
				'location':null
		};
		
		// Create a marker for the location
		// Note: need to transform from WGS1984 to (usually) Spherical Mercator projection
		var location = new OpenLayers.LonLat(weekend.latLng.lng,weekend.latLng.lat).transform(
		    new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject()
		);
		this.weekends[weekend.id].location = location;
		var marker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(location.lon, location.lat));
		marker.attributes.type = "default";
		
		var popup = new OpenLayers.Popup.FramedCloud("Popup",
		    location, null,
		    weekend.placeName.replace(/\n/g,"<br>"), null, true
		);
		
		// Need to centre the map before adding popups
		this.map.setCenter(location,8);
		
		this.map.addPopup(popup);
		  
		marker.click = function(e) { 
			popup.toggle();
		};
		this.locations.addFeatures([marker]);
		
		this.map.setCenter(location,8);
		
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
			this.map.setLayerZIndex(this.locations, this.map.getNumLayers());
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
		route.load(this['class'].toLowerCase(),this.id,this);
	}
	
})

/**
 * A walk.
 * TODO: May be useful elsewhere, and need moving to another file
 */
var Walk = new Class({
	Extends: Walkable,
	"class": "Walk",
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
	"class":"WalkInstance",
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
});

var Weekend = new Class({
	Extends: Event,
	load: function(instanceID,requestor)
	{
		var self=this;
		var loader = new Request.JSON({
			url: "/api/eventlisting?eventtype=weekend&id="+instanceID+"&format=json",
			onSuccess: function(data)
			{
				self.parseData(data);
				
				// We need location data to exist. Use the social title if blank.
				if (self.location == undefined || self.location == null || self.location == "")
					self.location = self.name;
				
				requestor.loadedWeekend(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
});

var Route = new Class({
	
	initialize: function(walkable)
	{
		if (walkable != undefined)
			this.walkable = walkable;
		
		this.waypoints = [];
	},
	
	/**
	 * Sets up a route from a JSON string.
	 * All that really matters is the waypoint array, which must contain one object per waypoint.
	 * Each waypoint object must have the fields 'lat' and 'lon'.
	 */
	read: function(data)
	{
		for (i in data)
		{
			if (data.hasOwnProperty(i))
			{
				this[i] = data[i];
			}
		}
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
				self.read(data);
				requestor.loadedRoute(self);
			}
			// TODO: onFailure
		});
		loader.get();
	}
});

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Latitude/longitude spherical geodesy formulae & scripts (c) Chris Veness 2002-2012            */
/*   - www.movable-type.co.uk/scripts/latlong.html                                                */
/*                                                                                                */
/*  Sample usage:                                                                                 */
/*    var p1 = new LatLon(51.5136, -0.0983);                                                      */
/*    var p2 = new LatLon(51.4778, -0.0015);                                                      */
/*    var dist = p1.distanceTo(p2);          // in km                                             */
/*    var brng = p1.bearingTo(p2);           // in degrees clockwise from north                   */
/*    ... etc                                                                                     */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Note that minimal error checking is performed in this example code!                           */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */


/**
 * @requires Geo
 */
 
 
/**
 * Creates a point on the earth's surface at the supplied latitude / longitude
 *
 * @constructor
 * @param {Number} lat: latitude in numeric degrees
 * @param {Number} lon: longitude in numeric degrees
 * @param {Number} [rad=6371]: radius of earth if different value is required from standard 6,371km
 */
function LatLon(lat, lon, rad) {
  if (typeof(rad) == 'undefined') rad = 6371;  // earth's mean radius in km
  // only accept numbers or valid numeric strings
  this._lat = typeof(lat)=='number' ? lat : typeof(lat)=='string' && lat.trim()!='' ? +lat : NaN;
  this._lon = typeof(lon)=='number' ? lon : typeof(lon)=='string' && lon.trim()!='' ? +lon : NaN;
  this._radius = typeof(rad)=='number' ? rad : typeof(rad)=='string' && trim(lon)!='' ? +rad : NaN;
}


/**
 * Returns the distance from this point to the supplied point, in km 
 * (using Haversine formula)
 *
 * from: Haversine formula - R. W. Sinnott, "Virtues of the Haversine",
 *       Sky and Telescope, vol 68, no 2, 1984
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @param   {Number} [precision=4]: no of significant digits to use for returned value
 * @returns {Number} Distance in km between this point and destination point
 */
LatLon.prototype.distanceTo = function(point, precision) {
  // default 4 sig figs reflects typical 0.3% accuracy of spherical model
  if (typeof precision == 'undefined') precision = 4;
  
  var R = this._radius;
  var lat1 = this._lat.toRad(), lon1 = this._lon.toRad();
  var lat2 = point._lat.toRad(), lon2 = point._lon.toRad();
  var dLat = lat2 - lat1;
  var dLon = lon2 - lon1;

  var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
          Math.cos(lat1) * Math.cos(lat2) * 
          Math.sin(dLon/2) * Math.sin(dLon/2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  var d = R * c;
  return d.toPrecisionFixed(precision);
}


/**
 * Returns the (initial) bearing from this point to the supplied point, in degrees
 *   see http://williams.best.vwh.net/avform.htm#Crs
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {Number} Initial bearing in degrees from North
 */
LatLon.prototype.bearingTo = function(point) {
  var lat1 = this._lat.toRad(), lat2 = point._lat.toRad();
  var dLon = (point._lon-this._lon).toRad();

  var y = Math.sin(dLon) * Math.cos(lat2);
  var x = Math.cos(lat1)*Math.sin(lat2) -
          Math.sin(lat1)*Math.cos(lat2)*Math.cos(dLon);
  var brng = Math.atan2(y, x);
  
  return (brng.toDeg()+360) % 360;
}


/**
 * Returns final bearing arriving at supplied destination point from this point; the final bearing 
 * will differ from the initial bearing by varying degrees according to distance and latitude
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {Number} Final bearing in degrees from North
 */
LatLon.prototype.finalBearingTo = function(point) {
  // get initial bearing from supplied point back to this point...
  var lat1 = point._lat.toRad(), lat2 = this._lat.toRad();
  var dLon = (this._lon-point._lon).toRad();

  var y = Math.sin(dLon) * Math.cos(lat2);
  var x = Math.cos(lat1)*Math.sin(lat2) -
          Math.sin(lat1)*Math.cos(lat2)*Math.cos(dLon);
  var brng = Math.atan2(y, x);
          
  // ... & reverse it by adding 180°
  return (brng.toDeg()+180) % 360;
}


/**
 * Returns the midpoint between this point and the supplied point.
 *   see http://mathforum.org/library/drmath/view/51822.html for derivation
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {LatLon} Midpoint between this point and the supplied point
 */
LatLon.prototype.midpointTo = function(point) {
  lat1 = this._lat.toRad(), lon1 = this._lon.toRad();
  lat2 = point._lat.toRad();
  var dLon = (point._lon-this._lon).toRad();

  var Bx = Math.cos(lat2) * Math.cos(dLon);
  var By = Math.cos(lat2) * Math.sin(dLon);

  lat3 = Math.atan2(Math.sin(lat1)+Math.sin(lat2),
                    Math.sqrt( (Math.cos(lat1)+Bx)*(Math.cos(lat1)+Bx) + By*By) );
  lon3 = lon1 + Math.atan2(By, Math.cos(lat1) + Bx);
  lon3 = (lon3+3*Math.PI) % (2*Math.PI) - Math.PI;  // normalise to -180..+180º
  
  return new LatLon(lat3.toDeg(), lon3.toDeg());
}


/**
 * Returns the destination point from this point having travelled the given distance (in km) on the 
 * given initial bearing (bearing may vary before destination is reached)
 *
 *   see http://williams.best.vwh.net/avform.htm#LL
 *
 * @param   {Number} brng: Initial bearing in degrees
 * @param   {Number} dist: Distance in km
 * @returns {LatLon} Destination point
 */
LatLon.prototype.destinationPoint = function(brng, dist) {
  dist = typeof(dist)=='number' ? dist : typeof(dist)=='string' && dist.trim()!='' ? +dist : NaN;
  dist = dist/this._radius;  // convert dist to angular distance in radians
  brng = brng.toRad();  // 
  var lat1 = this._lat.toRad(), lon1 = this._lon.toRad();

  var lat2 = Math.asin( Math.sin(lat1)*Math.cos(dist) + 
                        Math.cos(lat1)*Math.sin(dist)*Math.cos(brng) );
  var lon2 = lon1 + Math.atan2(Math.sin(brng)*Math.sin(dist)*Math.cos(lat1), 
                               Math.cos(dist)-Math.sin(lat1)*Math.sin(lat2));
  lon2 = (lon2+3*Math.PI) % (2*Math.PI) - Math.PI;  // normalise to -180..+180º

  return new LatLon(lat2.toDeg(), lon2.toDeg());
}


/**
 * Returns the point of intersection of two paths defined by point and bearing
 *
 *   see http://williams.best.vwh.net/avform.htm#Intersection
 *
 * @param   {LatLon} p1: First point
 * @param   {Number} brng1: Initial bearing from first point
 * @param   {LatLon} p2: Second point
 * @param   {Number} brng2: Initial bearing from second point
 * @returns {LatLon} Destination point (null if no unique intersection defined)
 */
LatLon.intersection = function(p1, brng1, p2, brng2) {
  brng1 = typeof brng1 == 'number' ? brng1 : typeof brng1 == 'string' && trim(brng1)!='' ? +brng1 : NaN;
  brng2 = typeof brng2 == 'number' ? brng2 : typeof brng2 == 'string' && trim(brng2)!='' ? +brng2 : NaN;
  lat1 = p1._lat.toRad(), lon1 = p1._lon.toRad();
  lat2 = p2._lat.toRad(), lon2 = p2._lon.toRad();
  brng13 = brng1.toRad(), brng23 = brng2.toRad();
  dLat = lat2-lat1, dLon = lon2-lon1;
  
  dist12 = 2*Math.asin( Math.sqrt( Math.sin(dLat/2)*Math.sin(dLat/2) + 
    Math.cos(lat1)*Math.cos(lat2)*Math.sin(dLon/2)*Math.sin(dLon/2) ) );
  if (dist12 == 0) return null;
  
  // initial/final bearings between points
  brngA = Math.acos( ( Math.sin(lat2) - Math.sin(lat1)*Math.cos(dist12) ) / 
    ( Math.sin(dist12)*Math.cos(lat1) ) );
  if (isNaN(brngA)) brngA = 0;  // protect against rounding
  brngB = Math.acos( ( Math.sin(lat1) - Math.sin(lat2)*Math.cos(dist12) ) / 
    ( Math.sin(dist12)*Math.cos(lat2) ) );
  
  if (Math.sin(lon2-lon1) > 0) {
    brng12 = brngA;
    brng21 = 2*Math.PI - brngB;
  } else {
    brng12 = 2*Math.PI - brngA;
    brng21 = brngB;
  }
  
  alpha1 = (brng13 - brng12 + Math.PI) % (2*Math.PI) - Math.PI;  // angle 2-1-3
  alpha2 = (brng21 - brng23 + Math.PI) % (2*Math.PI) - Math.PI;  // angle 1-2-3
  
  if (Math.sin(alpha1)==0 && Math.sin(alpha2)==0) return null;  // infinite intersections
  if (Math.sin(alpha1)*Math.sin(alpha2) < 0) return null;       // ambiguous intersection
  
  //alpha1 = Math.abs(alpha1);
  //alpha2 = Math.abs(alpha2);
  // ... Ed Williams takes abs of alpha1/alpha2, but seems to break calculation?
  
  alpha3 = Math.acos( -Math.cos(alpha1)*Math.cos(alpha2) + 
                       Math.sin(alpha1)*Math.sin(alpha2)*Math.cos(dist12) );
  dist13 = Math.atan2( Math.sin(dist12)*Math.sin(alpha1)*Math.sin(alpha2), 
                       Math.cos(alpha2)+Math.cos(alpha1)*Math.cos(alpha3) )
  lat3 = Math.asin( Math.sin(lat1)*Math.cos(dist13) + 
                    Math.cos(lat1)*Math.sin(dist13)*Math.cos(brng13) );
  dLon13 = Math.atan2( Math.sin(brng13)*Math.sin(dist13)*Math.cos(lat1), 
                       Math.cos(dist13)-Math.sin(lat1)*Math.sin(lat3) );
  lon3 = lon1+dLon13;
  lon3 = (lon3+3*Math.PI) % (2*Math.PI) - Math.PI;  // normalise to -180..+180º
  
  return new LatLon(lat3.toDeg(), lon3.toDeg());
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

/**
 * Returns the distance from this point to the supplied point, in km, travelling along a rhumb line
 *
 *   see http://williams.best.vwh.net/avform.htm#Rhumb
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {Number} Distance in km between this point and destination point
 */
LatLon.prototype.rhumbDistanceTo = function(point) {
  var R = this._radius;
  var lat1 = this._lat.toRad(), lat2 = point._lat.toRad();
  var dLat = (point._lat-this._lat).toRad();
  var dLon = Math.abs(point._lon-this._lon).toRad();
  
  var dPhi = Math.log(Math.tan(lat2/2+Math.PI/4)/Math.tan(lat1/2+Math.PI/4));
  var q = (isFinite(dLat/dPhi)) ? dLat/dPhi : Math.cos(lat1);  // E-W line gives dPhi=0
  
  // if dLon over 180° take shorter rhumb across anti-meridian:
  if (Math.abs(dLon) > Math.PI) {
    dLon = dLon>0 ? -(2*Math.PI-dLon) : (2*Math.PI+dLon);
  }
  
  var dist = Math.sqrt(dLat*dLat + q*q*dLon*dLon) * R; 
  
  return dist.toPrecisionFixed(4);  // 4 sig figs reflects typical 0.3% accuracy of spherical model
}

/**
 * Returns the bearing from this point to the supplied point along a rhumb line, in degrees
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {Number} Bearing in degrees from North
 */
LatLon.prototype.rhumbBearingTo = function(point) {
  var lat1 = this._lat.toRad(), lat2 = point._lat.toRad();
  var dLon = (point._lon-this._lon).toRad();
  
  var dPhi = Math.log(Math.tan(lat2/2+Math.PI/4)/Math.tan(lat1/2+Math.PI/4));
  if (Math.abs(dLon) > Math.PI) dLon = dLon>0 ? -(2*Math.PI-dLon) : (2*Math.PI+dLon);
  var brng = Math.atan2(dLon, dPhi);
  
  return (brng.toDeg()+360) % 360;
}

/**
 * Returns the destination point from this point having travelled the given distance (in km) on the 
 * given bearing along a rhumb line
 *
 * @param   {Number} brng: Bearing in degrees from North
 * @param   {Number} dist: Distance in km
 * @returns {LatLon} Destination point
 */
LatLon.prototype.rhumbDestinationPoint = function(brng, dist) {
  var R = this._radius;
  var d = parseFloat(dist)/R;  // d = angular distance covered on earth’s surface
  var lat1 = this._lat.toRad(), lon1 = this._lon.toRad();
  brng = brng.toRad();

  var dLat = d*Math.cos(brng);
  // nasty kludge to overcome ill-conditioned results around parallels of latitude:
  if (Math.abs(dLat) < 1e-10) dLat = 0; // dLat < 1 mm
  
  var lat2 = lat1 + dLat;
  var dPhi = Math.log(Math.tan(lat2/2+Math.PI/4)/Math.tan(lat1/2+Math.PI/4));
  var q = (isFinite(dLat/dPhi)) ? dLat/dPhi : Math.cos(lat1);  // E-W line gives dPhi=0
  var dLon = d*Math.sin(brng)/q;
  
  // check for some daft bugger going past the pole, normalise latitude if so
  if (Math.abs(lat2) > Math.PI/2) lat2 = lat2>0 ? Math.PI-lat2 : -Math.PI-lat2;
  
  lon2 = (lon1+dLon+3*Math.PI)%(2*Math.PI) - Math.PI;
 
  return new LatLon(lat2.toDeg(), lon2.toDeg());
}

/**
 * Returns the loxodromic midpoint (along a rhumb line) between this point and the supplied point.
 *   see http://mathforum.org/kb/message.jspa?messageID=148837
 *
 * @param   {LatLon} point: Latitude/longitude of destination point
 * @returns {LatLon} Midpoint between this point and the supplied point
 */
LatLon.prototype.rhumbMidpointTo = function(point) {
  lat1 = this._lat.toRad(), lon1 = this._lon.toRad();
  lat2 = point._lat.toRad(), lon2 = point._lon.toRad();
  
  if (Math.abs(lon2-lon1) > Math.PI) lon1 += 2*Math.PI; // crossing anti-meridian
  
  var lat3 = (lat1+lat2)/2;
  var f1 = Math.tan(Math.PI/4 + lat1/2);
  var f2 = Math.tan(Math.PI/4 + lat2/2);
  var f3 = Math.tan(Math.PI/4 + lat3/2);
  var lon3 = ( (lon2-lon1)*Math.log(f3) + lon1*Math.log(f2) - lon2*Math.log(f1) ) / Math.log(f2/f1);
  
  if (!isFinite(lon3)) lon3 = (lon1+lon2)/2; // parallel of latitude
  
  lon3 = (lon3+3*Math.PI) % (2*Math.PI) - Math.PI;  // normalise to -180..+180º
  
  return new LatLon(lat3.toDeg(), lon3.toDeg());
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */


/**
 * Returns the latitude of this point; signed numeric degrees if no format, otherwise format & dp 
 * as per Geo.toLat()
 *
 * @param   {String} [format]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to display
 * @returns {Number|String} Numeric degrees if no format specified, otherwise deg/min/sec
 */
LatLon.prototype.lat = function(format, dp) {
  if (typeof format == 'undefined') return this._lat;
  
  return Geo.toLat(this._lat, format, dp);
}

/**
 * Returns the longitude of this point; signed numeric degrees if no format, otherwise format & dp 
 * as per Geo.toLon()
 *
 * @param   {String} [format]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to display
 * @returns {Number|String} Numeric degrees if no format specified, otherwise deg/min/sec
 */
LatLon.prototype.lon = function(format, dp) {
  if (typeof format == 'undefined') return this._lon;
  
  return Geo.toLon(this._lon, format, dp);
}

/**
 * Returns a string representation of this point; format and dp as per lat()/lon()
 *
 * @param   {String} [format]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to display
 * @returns {String} Comma-separated latitude/longitude
 */
LatLon.prototype.toString = function(format, dp) {
  if (typeof format == 'undefined') format = 'dms';
  
  return Geo.toLat(this._lat, format, dp) + ', ' + Geo.toLon(this._lon, format, dp);
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

// ---- extend Number object with methods for converting degrees/radians

/** Converts numeric degrees to radians */
if (typeof Number.prototype.toRad == 'undefined') {
  Number.prototype.toRad = function() {
    return this * Math.PI / 180;
  }
}

/** Converts radians to numeric (signed) degrees */
if (typeof Number.prototype.toDeg == 'undefined') {
  Number.prototype.toDeg = function() {
    return this * 180 / Math.PI;
  }
}

/** 
 * Formats the significant digits of a number, using only fixed-point notation (no exponential)
 * 
 * @param   {Number} precision: Number of significant digits to appear in the returned string
 * @returns {String} A string representation of number which contains precision significant digits
 */
if (typeof Number.prototype.toPrecisionFixed == 'undefined') {
  Number.prototype.toPrecisionFixed = function(precision) {
    
    // use standard toPrecision method
    var n = this.toPrecision(precision);
    
    // ... but replace +ve exponential format with trailing zeros
    n = n.replace(/(.+)e\+(.+)/, function(n, sig, exp) {
      sig = sig.replace(/\./, '');       // remove decimal from significand
      l = sig.length - 1;
      while (exp-- > l) sig = sig + '0'; // append zeros from exponent
      return sig;
    });
    
    // ... and replace -ve exponential format with leading zeros
    n = n.replace(/(.+)e-(.+)/, function(n, sig, exp) {
      sig = sig.replace(/\./, '');       // remove decimal from significand
      while (exp-- > 1) sig = '0' + sig; // prepend zeros from exponent
      return '0.' + sig;
    });
    
    return n;
  }
}

/** Trims whitespace from string (q.v. blog.stevenlevithan.com/archives/faster-trim-javascript) */
if (typeof String.prototype.trim == 'undefined') {
  String.prototype.trim = function() {
    return String(this).replace(/^\s\s*/, '').replace(/\s\s*$/, '');
  }
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
if (!window.console) window.console = { log: function() {} };

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Geodesy representation conversion functions (c) Chris Veness 2002-2012                        */
/*   - www.movable-type.co.uk/scripts/latlong.html                                                */
/*                                                                                                */
/*  Sample usage:                                                                                 */
/*    var lat = Geo.parseDMS('51° 28′ 40.12″ N');                                                 */
/*    var lon = Geo.parseDMS('000° 00′ 05.31″ W');                                                */
/*    var p1 = new LatLon(lat, lon);                                                              */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */


var Geo = {};  // Geo namespace, representing static class


/**
 * Parses string representing degrees/minutes/seconds into numeric degrees
 *
 * This is very flexible on formats, allowing signed decimal degrees, or deg-min-sec optionally
 * suffixed by compass direction (NSEW). A variety of separators are accepted (eg 3º 37' 09"W) 
 * or fixed-width format without separators (eg 0033709W). Seconds and minutes may be omitted. 
 * (Note minimal validation is done).
 *
 * @param   {String|Number} dmsStr: Degrees or deg/min/sec in variety of formats
 * @returns {Number} Degrees as decimal number
 * @throws  {TypeError} dmsStr is an object, perhaps DOM object without .value?
 */
Geo.parseDMS = function(dmsStr) {
  if (typeof deg == 'object') throw new TypeError('Geo.parseDMS - dmsStr is [DOM?] object');
  
  // check for signed decimal degrees without NSEW, if so return it directly
  if (typeof dmsStr === 'number' && isFinite(dmsStr)) return Number(dmsStr);
  
  // strip off any sign or compass dir'n & split out separate d/m/s
  var dms = String(dmsStr).trim().replace(/^-/,'').replace(/[NSEW]$/i,'').split(/[^0-9.,]+/);
  if (dms[dms.length-1]=='') dms.splice(dms.length-1);  // from trailing symbol
  
  if (dms == '') return NaN;
  
  // and convert to decimal degrees...
  switch (dms.length) {
    case 3:  // interpret 3-part result as d/m/s
      var deg = dms[0]/1 + dms[1]/60 + dms[2]/3600; 
      break;
    case 2:  // interpret 2-part result as d/m
      var deg = dms[0]/1 + dms[1]/60; 
      break;
    case 1:  // just d (possibly decimal) or non-separated dddmmss
      var deg = dms[0];
      // check for fixed-width unseparated format eg 0033709W
      //if (/[NS]/i.test(dmsStr)) deg = '0' + deg;  // - normalise N/S to 3-digit degrees
      //if (/[0-9]{7}/.test(deg)) deg = deg.slice(0,3)/1 + deg.slice(3,5)/60 + deg.slice(5)/3600; 
      break;
    default:
      return NaN;
  }
  if (/^-|[WS]$/i.test(dmsStr.trim())) deg = -deg; // take '-', west and south as -ve
  return Number(deg);
}


/**
 * Convert decimal degrees to deg/min/sec format
 *  - degree, prime, double-prime symbols are added, but sign is discarded, though no compass
 *    direction is added
 *
 * @private
 * @param   {Number} deg: Degrees
 * @param   {String} [format=dms]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to use - default 0 for dms, 2 for dm, 4 for d
 * @returns {String} deg formatted as deg/min/secs according to specified format
 * @throws  {TypeError} deg is an object, perhaps DOM object without .value?
 */
Geo.toDMS = function(deg, format, dp) {
  if (typeof deg == 'object') throw new TypeError('Geo.toDMS - deg is [DOM?] object');
  if (isNaN(deg)) return null;  // give up here if we can't make a number from deg
  
    // default values
  if (typeof format == 'undefined') format = 'dms';
  if (typeof dp == 'undefined') {
    switch (format) {
      case 'd': dp = 4; break;
      case 'dm': dp = 2; break;
      case 'dms': dp = 0; break;
      default: format = 'dms'; dp = 0;  // be forgiving on invalid format
    }
  }
  
  deg = Math.abs(deg);  // (unsigned result ready for appending compass dir'n)
  
  switch (format) {
    case 'd':
      d = deg.toFixed(dp);     // round degrees
      if (d<100) d = '0' + d;  // pad with leading zeros
      if (d<10) d = '0' + d;
      dms = d + '\u00B0';      // add º symbol
      break;
    case 'dm':
      var min = (deg*60).toFixed(dp);  // convert degrees to minutes & round
      var d = Math.floor(min / 60);    // get component deg/min
      var m = (min % 60).toFixed(dp);  // pad with trailing zeros
      if (d<100) d = '0' + d;          // pad with leading zeros
      if (d<10) d = '0' + d;
      if (m<10) m = '0' + m;
      dms = d + '\u00B0' + m + '\u2032';  // add º, ' symbols
      break;
    case 'dms':
      var sec = (deg*3600).toFixed(dp);  // convert degrees to seconds & round
      var d = Math.floor(sec / 3600);    // get component deg/min/sec
      var m = Math.floor(sec/60) % 60;
      var s = (sec % 60).toFixed(dp);    // pad with trailing zeros
      if (d<100) d = '0' + d;            // pad with leading zeros
      if (d<10) d = '0' + d;
      if (m<10) m = '0' + m;
      if (s<10) s = '0' + s;
      dms = d + '\u00B0' + m + '\u2032' + s + '\u2033';  // add º, ', " symbols
      break;
  }
  
  return dms;
}


/**
 * Convert numeric degrees to deg/min/sec latitude (suffixed with N/S)
 *
 * @param   {Number} deg: Degrees
 * @param   {String} [format=dms]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to use - default 0 for dms, 2 for dm, 4 for d
 * @returns {String} Deg/min/seconds
 */
Geo.toLat = function(deg, format, dp) {
  var lat = Geo.toDMS(deg, format, dp);
  return lat==null ? '–' : lat.slice(1) + (deg<0 ? 'S' : 'N');  // knock off initial '0' for lat!
}


/**
 * Convert numeric degrees to deg/min/sec longitude (suffixed with E/W)
 *
 * @param   {Number} deg: Degrees
 * @param   {String} [format=dms]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to use - default 0 for dms, 2 for dm, 4 for d
 * @returns {String} Deg/min/seconds
 */
Geo.toLon = function(deg, format, dp) {
  var lon = Geo.toDMS(deg, format, dp);
  return lon==null ? '–' : lon + (deg<0 ? 'W' : 'E');
}


/**
 * Convert numeric degrees to deg/min/sec as a bearing (0º..360º)
 *
 * @param   {Number} deg: Degrees
 * @param   {String} [format=dms]: Return value as 'd', 'dm', 'dms'
 * @param   {Number} [dp=0|2|4]: No of decimal places to use - default 0 for dms, 2 for dm, 4 for d
 * @returns {String} Deg/min/seconds
 */
Geo.toBrng = function(deg, format, dp) {
  deg = (Number(deg)+360) % 360;  // normalise -ve values to 180º..360º
  var brng =  Geo.toDMS(deg, format, dp);
  return brng==null ? '–' : brng.replace('360', '0');  // just in case rounding took us up to 360º!
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
if (!window.console) window.console = { log: function() {} };


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
/*  Ordnance Survey Grid Reference functions  (c) Chris Veness 2005-2012                          */
/*   - www.movable-type.co.uk/scripts/gridref.js                                                  */
/*   - www.movable-type.co.uk/scripts/latlon-gridref.html                                         */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

/**
 * @requires LatLon
 */
 
 
/**
 * Creates a OsGridRef object
 *
 * @constructor
 * @param {Number} easting:  Easting in metres from OS false origin
 * @param {Number} northing: Northing in metres from OS false origin
 */
function OsGridRef(easting, northing) {
  this.easting = parseInt(easting, 10);
  this.northing = parseInt(northing, 10);
}


/**
 * Convert (OSGB36) latitude/longitude to Ordnance Survey grid reference easting/northing coordinate
 *
 * @param {LatLon} point: OSGB36 latitude/longitude
 * @return {OsGridRef} OS Grid Reference easting/northing
 */
OsGridRef.latLongToOsGrid = function(point) {
  var lat = point.lat().toRad(); 
  var lon = point.lon().toRad(); 
  
  var a = 6377563.396, b = 6356256.910;          // Airy 1830 major & minor semi-axes
  var F0 = 0.9996012717;                         // NatGrid scale factor on central meridian
  var lat0 = (49).toRad(), lon0 = (-2).toRad();  // NatGrid true origin is 49ºN,2ºW
  var N0 = -100000, E0 = 400000;                 // northing & easting of true origin, metres
  var e2 = 1 - (b*b)/(a*a);                      // eccentricity squared
  var n = (a-b)/(a+b), n2 = n*n, n3 = n*n*n;

  var cosLat = Math.cos(lat), sinLat = Math.sin(lat);
  var nu = a*F0/Math.sqrt(1-e2*sinLat*sinLat);              // transverse radius of curvature
  var rho = a*F0*(1-e2)/Math.pow(1-e2*sinLat*sinLat, 1.5);  // meridional radius of curvature
  var eta2 = nu/rho-1;

  var Ma = (1 + n + (5/4)*n2 + (5/4)*n3) * (lat-lat0);
  var Mb = (3*n + 3*n*n + (21/8)*n3) * Math.sin(lat-lat0) * Math.cos(lat+lat0);
  var Mc = ((15/8)*n2 + (15/8)*n3) * Math.sin(2*(lat-lat0)) * Math.cos(2*(lat+lat0));
  var Md = (35/24)*n3 * Math.sin(3*(lat-lat0)) * Math.cos(3*(lat+lat0));
  var M = b * F0 * (Ma - Mb + Mc - Md);              // meridional arc

  var cos3lat = cosLat*cosLat*cosLat;
  var cos5lat = cos3lat*cosLat*cosLat;
  var tan2lat = Math.tan(lat)*Math.tan(lat);
  var tan4lat = tan2lat*tan2lat;

  var I = M + N0;
  var II = (nu/2)*sinLat*cosLat;
  var III = (nu/24)*sinLat*cos3lat*(5-tan2lat+9*eta2);
  var IIIA = (nu/720)*sinLat*cos5lat*(61-58*tan2lat+tan4lat);
  var IV = nu*cosLat;
  var V = (nu/6)*cos3lat*(nu/rho-tan2lat);
  var VI = (nu/120) * cos5lat * (5 - 18*tan2lat + tan4lat + 14*eta2 - 58*tan2lat*eta2);

  var dLon = lon-lon0;
  var dLon2 = dLon*dLon, dLon3 = dLon2*dLon, dLon4 = dLon3*dLon, dLon5 = dLon4*dLon, dLon6 = dLon5*dLon;

  var N = I + II*dLon2 + III*dLon4 + IIIA*dLon6;
  var E = E0 + IV*dLon + V*dLon3 + VI*dLon5;

  return new OsGridRef(E, N);
}


/**
 * Convert Ordnance Survey grid reference easting/northing coordinate to (OSGB36) latitude/longitude
 *
 * @param {OsGridRef} easting/northing to be converted to latitude/longitude
 * @return {LatLon} latitude/longitude (in OSGB36) of supplied grid reference
 */
OsGridRef.osGridToLatLong = function(gridref) {
  var E = gridref.easting;
  var N = gridref.northing;

  var a = 6377563.396, b = 6356256.910;              // Airy 1830 major & minor semi-axes
  var F0 = 0.9996012717;                             // NatGrid scale factor on central meridian
  var lat0 = 49*Math.PI/180, lon0 = -2*Math.PI/180;  // NatGrid true origin
  var N0 = -100000, E0 = 400000;                     // northing & easting of true origin, metres
  var e2 = 1 - (b*b)/(a*a);                          // eccentricity squared
  var n = (a-b)/(a+b), n2 = n*n, n3 = n*n*n;

  var lat=lat0, M=0;
  do {
    lat = (N-N0-M)/(a*F0) + lat;

    var Ma = (1 + n + (5/4)*n2 + (5/4)*n3) * (lat-lat0);
    var Mb = (3*n + 3*n*n + (21/8)*n3) * Math.sin(lat-lat0) * Math.cos(lat+lat0);
    var Mc = ((15/8)*n2 + (15/8)*n3) * Math.sin(2*(lat-lat0)) * Math.cos(2*(lat+lat0));
    var Md = (35/24)*n3 * Math.sin(3*(lat-lat0)) * Math.cos(3*(lat+lat0));
    M = b * F0 * (Ma - Mb + Mc - Md);                // meridional arc

  } while (N-N0-M >= 0.00001);  // ie until < 0.01mm

  var cosLat = Math.cos(lat), sinLat = Math.sin(lat);
  var nu = a*F0/Math.sqrt(1-e2*sinLat*sinLat);              // transverse radius of curvature
  var rho = a*F0*(1-e2)/Math.pow(1-e2*sinLat*sinLat, 1.5);  // meridional radius of curvature
  var eta2 = nu/rho-1;

  var tanLat = Math.tan(lat);
  var tan2lat = tanLat*tanLat, tan4lat = tan2lat*tan2lat, tan6lat = tan4lat*tan2lat;
  var secLat = 1/cosLat;
  var nu3 = nu*nu*nu, nu5 = nu3*nu*nu, nu7 = nu5*nu*nu;
  var VII = tanLat/(2*rho*nu);
  var VIII = tanLat/(24*rho*nu3)*(5+3*tan2lat+eta2-9*tan2lat*eta2);
  var IX = tanLat/(720*rho*nu5)*(61+90*tan2lat+45*tan4lat);
  var X = secLat/nu;
  var XI = secLat/(6*nu3)*(nu/rho+2*tan2lat);
  var XII = secLat/(120*nu5)*(5+28*tan2lat+24*tan4lat);
  var XIIA = secLat/(5040*nu7)*(61+662*tan2lat+1320*tan4lat+720*tan6lat);

  var dE = (E-E0), dE2 = dE*dE, dE3 = dE2*dE, dE4 = dE2*dE2, dE5 = dE3*dE2, dE6 = dE4*dE2, dE7 = dE5*dE2;
  lat = lat - VII*dE2 + VIII*dE4 - IX*dE6;
  var lon = lon0 + X*dE - XI*dE3 + XII*dE5 - XIIA*dE7;
  
  return new LatLon(lat.toDeg(), lon.toDeg());
}


/**
 * Converts standard grid reference ('SU387148') to fully numeric ref ([438700,114800]);
 *   returned co-ordinates are in metres, centred on supplied grid square;
 *
 * @param {String} gridref: Standard format OS grid reference
 * @returns {OsGridRef}     Numeric version of grid reference in metres from false origin
 */
OsGridRef.parse = function(gridref) {
  gridref = gridref.trim();
  // get numeric values of letter references, mapping A->0, B->1, C->2, etc:
  var l1 = gridref.toUpperCase().charCodeAt(0) - 'A'.charCodeAt(0);
  var l2 = gridref.toUpperCase().charCodeAt(1) - 'A'.charCodeAt(0);
  // shuffle down letters after 'I' since 'I' is not used in grid:
  if (l1 > 7) l1--;
  if (l2 > 7) l2--;

  // convert grid letters into 100km-square indexes from false origin (grid square SV):
  var e = ((l1-2)%5)*5 + (l2%5);
  var n = (19-Math.floor(l1/5)*5) - Math.floor(l2/5);
  if (e<0 || e>6 || n<0 || n>12) return new OsGridRef(NaN, NaN);

  // skip grid letters to get numeric part of ref, stripping any spaces:
  gridref = gridref.slice(2).replace(/ /g,'');

  // append numeric part of references to grid index:
  e += gridref.slice(0, gridref.length/2);
  n += gridref.slice(gridref.length/2);

  // normalise to 1m grid, rounding up to centre of grid square:
  switch (gridref.length) {
    case 0: e += '50000'; n += '50000'; break;
    case 2: e += '5000'; n += '5000'; break;
    case 4: e += '500'; n += '500'; break;
    case 6: e += '50'; n += '50'; break;
    case 8: e += '5'; n += '5'; break;
    case 10: break; // 10-digit refs are already 1m
    default: return new OsGridRef(NaN, NaN);
  }

  return new OsGridRef(e, n);
}


/**
 * Converts this numeric grid reference to standard OS grid reference
 *
 * @param {Number} [digits=6] Precision of returned grid reference (6 digits = metres)
 * @return {String)           This grid reference in standard format
 */
OsGridRef.prototype.toString = function(digits) {
  digits = (typeof digits == 'undefined') ? 10 : digits;
  e = this.easting, n = this.northing;
  if (e==NaN || n==NaN) return '??';
  
  // get the 100km-grid indices
  var e100k = Math.floor(e/100000), n100k = Math.floor(n/100000);
  
  if (e100k<0 || e100k>6 || n100k<0 || n100k>12) return '';

  // translate those into numeric equivalents of the grid letters
  var l1 = (19-n100k) - (19-n100k)%5 + Math.floor((e100k+10)/5);
  var l2 = (19-n100k)*5%25 + e100k%5;

  // compensate for skipped 'I' and build grid letter-pairs
  if (l1 > 7) l1++;
  if (l2 > 7) l2++;
  var letPair = String.fromCharCode(l1+'A'.charCodeAt(0), l2+'A'.charCodeAt(0));

  // strip 100km-grid indices from easting & northing, and reduce precision
  e = Math.floor((e%100000)/Math.pow(10,5-digits/2));
  n = Math.floor((n%100000)/Math.pow(10,5-digits/2));

  var gridRef = letPair + ' ' + e.padLz(digits/2) + ' ' + n.padLz(digits/2);

  return gridRef;
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */

/** Trims whitespace from string (q.v. blog.stevenlevithan.com/archives/faster-trim-javascript) */
if (typeof String.prototype.trim == 'undefined') {
  String.prototype.trim = function() {
    return this.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
  }
}

/** Pads a number with sufficient leading zeros to make it w chars wide */
if (typeof String.prototype.padLz == 'undefined') {
  Number.prototype.padLz = function(w) {
    var n = this.toString();
    var l = n.length;
    for (var i=0; i<w-l; i++) n = '0' + n;
    return n;
  }
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  */
