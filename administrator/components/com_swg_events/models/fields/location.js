var Location = new Class({
	/**
	 * Index of this location - first location is 0
	 */
	index: 0,
	/**
	 * OpenLayers.LonLat Lat/Long as WGS-84/EPSG:4326 coordinates
	 */
	lonLat: null,
	/**
	 * OpenLayers item used to mark this point
	 */
	marker: null,
	/**
	 * OpenLayers geometry representing the marker
	 */
	markerGeometry: null,
	/**
	 * Form field storing this field's grid reference (if any)
	 */
	gridRefField: null,
	/**
	 * Form field storing this field's location name (if any). Used for output only.
	 */
	locationNameField: null,
	/**
	 * Line coming into this point (will be none for a start point)
	 */
	lineIn: null,
	/**
	 * Line going out of this point (will be none for an end point)
	 */
	lineOut: null,
});

var JFormFieldLocation = new Class({
	map: null,
	locationNameField: null,
	markerLayer: null,
	numLocations: 0,
	/**
	 * Location[]
	 */
	locations: new Array(),
								   
	searchPanel: null,
	searchField: null,
	
	loc: null,
	
	outputField: null,
	
	bounds:null,
	
	initialize: function(id, startPos, startZoom, locations, gridRefFieldIds, locationNameFieldIds, routes, placeMarkerButtons)
	{
		this.map = new SWGMap(id+"_map");
		this.map.setDefaultMap("street");
		var start = new OpenLayers.LonLat(startPos.lng, startPos.lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		this.map.map.setCenter(start,startZoom);
		this.map.setDefaultMap("landscape");
		
		var markerIcon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
		
		this.markerLayer = this.map.map.getLayersByName("Locations")[0];
		
		this.outputField = document.getElementById(id);
		
		this.bounds = new OpenLayers.Bounds();
		
		var self = this;
		
		// Set up the search field
		this.searchPanel = document.id(id+"_search")
		var searchFields = this.searchPanel.getElements("input");
		this.searchField = searchFields[0];
		
		searchFields.addEvents({
			"click": function(e) {
				e.stop();
				self.handleSearch();
			},
			"change": function(e) {
				e.stop();
				self.handleSearch();
			}
		});
		
		// Register an on submit handler on the containing form.
		// This allows us to intercept submits caused by the user pressing enter in the search form
		document.id(id).getParent("form").addEvent("submit", function (e) {
			if (document.activeElement == self.searchField)
			{
				e.stop();
				self.handleSearch();
			}
		});
		
		// Place markers for all locations
		for (var i=0; i<locations.length; i++)
		{
			this.addLocation(new OpenLayers.LonLat(locations[i].lng, locations[i].lat));
		}
		
		// Connect to a grid reference fields if set. Grid reference fields are added AFTER other locations to avoid conflicts.
		if (gridRefFieldIds != undefined)
		{
			// Create a new location for each grid reference. Read the current reference and hook up an event listener to the field
			for (i=0; i<gridRefFieldIds.length; i++)
			{
				var field = document.id("jform_"+gridRefFieldIds[i]);
				if (field)
				{
					var loc = this.getLocationFromGridRefField(field);
					var lonLat;
					
					var location = this.addLocation();
					location.gridRefField = field;
					
					if (loc != null && !isNaN(loc.lat()) && !isNaN(loc.lon()))
					{
						lonLat = new OpenLayers.LonLat(loc.lon(), loc.lat());
						this.setLocation(location.index, lonLat);
					}
						
					// Register an onchange event to pick up updates
					field.addEvent("change", function() {
						// Find out which field this is
						for (var j=0; j<self.locations.length; j++)
						{
							if (self.locations[j].gridRefField != null && this == self.locations[j].gridRefField)
							{
								var grLoc = self.getLocationFromGridRefField(this);
								var loc = new OpenLayers.LonLat(grLoc.lon(), grLoc.lat());
								self.setLocation(j, loc);
								
								if (self.locations[j].locationNameField != null)
									self.writeLocationToNameField(loc, self.locations[j].locationNameField);
								
								// Scroll to show this marker
								var mapPoint = loc.transform(new OpenLayers.Projection("EPSG:4326"),self.map.map.getProjectionObject())
								if (!self.map.map.getExtent().containsLonLat(mapPoint))
								{
									self.map.map.panTo(mapPoint);
								}
								break;
							}
						}
					});
				}
			}
		}
		
		/* Connect to location name fields.
		   Unlike grid references, location name fields match up with existing fields.
		   They are only used for output, never for reading. 
		   Location names are only output if there isn't already a location name in the field, 
		   or if that location name came from reverse geocoding.
		 */
		if (locationNameFieldIds != undefined)
		{
			for (i=0; i<this.locations.length; i++)
			{
				if (locationNameFieldIds[i] != undefined)
				{
					field = document.id("jform_"+locationNameFieldIds[i]);
					if (field)
					{
						this.locations[i].locationNameField = field;
						
						// If a value is entered manually, prevent it from being overwritten
						field.addEvent("change", function() {
							field.store("nameFromGeocoding", false);
						});
					}
				}
			}
		}
		
		// Allow markers to be dragged
		var dragFeature = new OpenLayers.Control.DragFeature(
			this.markerLayer,
			{
				geometryTypes: ["OpenLayers.Geometry.Point"],
				onDrag: function(marker, picelLocation) {
					self.markerLayer.redraw();
				},
				onComplete: function(marker, pixelLocation) {
					// Find out which marker we just moved

					// i gives the index of the marker and location
					var i = marker.index;
					var point = new OpenLayers.LonLat(marker.geometry.x, marker.geometry.y);
				
					var location = point.transform(
						self.map.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326")
					);
					self.locations[i].lonLat = location;
					
					// Do we have a grid reference field
					if (self.locations[i].gridRefField != null)
						self.writeLocationToGridRefField(location, self.locations[i].gridRefField);
					
					if (self.locations[i].locationNameField != null)
						self.writeLocationToNameField(location, self.locations[i].locationNameField);
					
					self.outputLocations();
				}
				
			}
		);
		
		this.map.map.addControl(dragFeature);
		dragFeature.activate();
		
		// If we have a route, add it to the map
		for (i=0; i<routes.length; i++)
		{
			var route = new Route();
			route.read(routes[i]);
			
			if (route.waypoints.length != 0)
			{
				var srcProjection = new OpenLayers.Projection("EPSG:4326");
				var destProjection = this.map.map.getProjectionObject();
				
				var points = new Array();
				for (var j=0; j<route.waypoints.length; j++)
				{
					var pt = new OpenLayers.Geometry.Point(route.waypoints[j].lng, route.waypoints[j].lat).transform(srcProjection, destProjection)
					points.push(pt);
					this.bounds.extend(pt);
				}
				
				var rt = new OpenLayers.Geometry.LineString(points);
				var rtFeature = new OpenLayers.Feature.Vector(
					rt, null, {
						strokeColor:"#FF9555",
						strokeOpacity:1,
						strokeWidth:3,
						pointRadius:3,
						pointerEvents:"visiblePainted"
					}
				);
				this.markerLayer.addFeatures([rtFeature]);
			}
			
		}
		
		// Scale the map if we have points
		if (this.bounds.getCenterLonLat().lat != 0) // More likely to walk at longitude 0 than on the equator
		{
			this.map.map.zoomToExtent(this.bounds, false);
			if (this.map.map.zoom > 16)
				this.map.map.zoomTo(16); // Don't zoom to closely on single points
		}
		
		// Wire up the place marker buttons
		if (placeMarkerButtons != undefined)
		{
			for (i=0; i<this.locations.length;i++)
			{
				if (placeMarkerButtons[i] != undefined)
				{
					field = document.id(placeMarkerButtons[i]);
					if (field)
					{
						this.locations[i].placeMarkerButton = field;
						
						field.addEvent("click", function() {
							// Find out which field this is
							for (var j=0; j<self.locations.length; j++)
							{
								if (self.locations[j].placeMarkerButton != null && this == self.locations[j].placeMarkerButton)
								{
									var mapCentre = self.map.map.center
									var loc = new OpenLayers.LonLat(mapCentre.lon, mapCentre.lat);
									loc.transform(self.map.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326"));
									// Unset any existing location to force a redraw
									if (self.locations[j].lonLat != null)
									{
										var oldLoc = self.locations[j];
										self.markerLayer.removeFeatures([oldLoc.marker]);
										oldLoc.markerGeometry.destroy();
										oldLoc.marker.destroy();
										oldLoc.lonLat = null;
										
									}
									self.setLocation(j, loc);
									
									// Do we have a grid reference field
									if (self.locations[j].gridRefField != null)
										self.writeLocationToGridRefField(loc, self.locations[j].gridRefField);
									
									// Don't set the location name here - unlikely to be correct until the user drags the marker into place
									break;
								}
							}
							
						});
					}
				}
			}
		}
	},
	
	/**
	 * Adds a new location at the end of the list
	 * @param OpenLayers.LonLat lonLat Location to add (EPSG:4326). If not set, create a holding location to be used later
	 */
	addLocation:function(lonLat)
	{
		var location = new Location();
		
		location.index = this.locations.length;
		
		this.locations.push(location);
		
		if (lonLat != undefined && lonLat != null && !isNaN(lonLat.lon) && !isNaN(lonLat.lat))
		{
			this.setLocation(location.index, lonLat);
		}
		
		return location;
	},
	
	/**
	 * Sets the position of a marker, internally and on the map.
	 * @param int index Marker to move. If there is no marker there, nothing is done.
	 * @param OpenLayers.LonLat location Location to move marker to (EPSG:4326)
	 */
	setLocation:function(index, lonLat, forceRedraw)
	{
		if (forceRedraw == undefined)
			forceRedraw = false;
								   
		if (index >= this.locations.length)
			return false;
		
		var location = this.locations[index];
		var mapPoint = new OpenLayers.LonLat(lonLat.lon, lonLat.lat);
		mapPoint.transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		
		if (location.lonLat == undefined)
			newLocation = true;
		else
			newLocation = false;
		
		location.lonLat = lonLat;
		
		// If this location has never been setup, do so now
		if (newLocation || forceRedraw)
		{
			location.markerGeometry = new OpenLayers.Geometry.Point(mapPoint.lon, mapPoint.lat);
			location.marker = new OpenLayers.Feature.Vector(location.markerGeometry);
			location.marker.index = index; // Used in callback/event methods
		
			var featuresToAdd = [location.marker];
			
			// Apply styles. The first marker is green, the last one is red.
			// When adding a further last marker, return the second-last one to default colours
			if (index == 0)
				location.marker.attributes.type = "start";
			else
			{
				// For now, locations can only be added at the end. In future, this may change.
				location.marker.attributes.type = "end";
				
				// If we already had an end location, it becomes a default location when a new end is added
				if (this.locations[index-1].marker.attributes.type == "end")
				{
					this.locations[index-1].marker.attributes.type = "default";
				}
			}
			
			// If this location isn't the first one, add a line from the previous point to this one
			if (index >= 1)
			{
				var prevLocation = this.locations[index-1];
				var line = new OpenLayers.Geometry.LineString([prevLocation.markerGeometry, location.markerGeometry]);
				var lineFeature = new OpenLayers.Feature.Vector(
					line, null, {
						strokeColor:"#FF9555",
						strokeOpacity:1,
						strokeWidth:3,
						pointRadius:3,
						pointerEvents:"visiblePainted",
						strokeDashstyle:"dash"
					}
				);
				featuresToAdd.push(lineFeature);
				prevLocation.lineOut = lineFeature;
				location.lineIn = lineFeature;
			}
			
			this.markerLayer.addFeatures(featuresToAdd);
		}
		else
		{
			
			location.markerGeometry.x = mapPoint.lon;
			location.markerGeometry.y = mapPoint.lat;
		}
		
		this.bounds.extend(mapPoint);
		
		this.outputLocations();
		this.markerLayer.redraw();
	},
	
	placeMarker: function(location)
	{
		var loc = new OpenLayers.LonLat(location.lon,location.lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		
		this.marker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(loc.lon, loc.lat));
		
		// Add event to the marker, allowing it to be removed
		// TODO: Could implement dragging too
		var self = this;
		this.marker.events.register('click', this.marker, function(e) {
			// TODO
		});
		this.markerLayer.clearMarkers();
		this.markerLayer.addMarker(this.marker);
		
		this.map.map.setCenter(loc);
	},
	
	/**
	 * Outputs all current locations to the input field as a JSON string
	 */
	outputLocations:function()
	{
		var jsonOut = new Array();
		for (var i=0; i<this.locations.length; i++)
		{
			if (this.locations[i].lonLat != null && !isNaN(this.locations[i].lonLat.lon) && !isNaN(this.locations[i].lonLat.lat))
			{
				jsonOut.push(this.locations[i].lonLat);
			}
		}
		this.outputField.value = JSON.encode(jsonOut);
	},
	
	getLocationFromGridRefField: function(field)
	{
		// Remove any spaces before parsing - these are added by our 'convert to grid reference' script
		var gridRef = field.value.replace(/\s/g,'');
		var OSRef = OsGridRef.parse(gridRef);
			
		// Make sure this is valid
		// TODO: Maybe put the validator on the gridref field?
		if (!isNaN(OSRef.easting) && !isNaN(OSRef.northing))
			return OsGridRef.osGridToLatLong(OSRef);
		else
			return null;
	},
	
	writeLocationToGridRefField: function(location, field)
	{
		// Convert location to OS Grid reference
		var locLatLon = new LatLon(location.lat, location.lon);
		var OSRef = OsGridRef.latLongToOsGrid(locLatLon);
		field.value = OSRef.toString(6);
	},
	
	writeLocationToNameField: function(location, field)
	{
		if (field.value == "" || field.retrieve("nameFromGeocoding", false))
		{
			var self = this;
			var lookup = new Request.JSON({
				url: "/api/nominatim?lat="+location.lat+"&lon="+location.lon+"&format=json",
				onSuccess: function(placeName)
				{
					if (placeName)
					{
						field.value = placeName;
						field.store("nameFromGeocoding", true);
					}
				}
				// TODO: onFailure
			});
			lookup.get();
		}
	},
	
	handleSearch: function()
	{
		// Remove any existing search stuff
		$$(".searchpanel .placeNameResults").destroy();
		$$(".searchpanel .invisibleBacking").destroy();
		
		var value = this.searchField.value;
		var self = this;
		if (value != "")
		{
			var lookup = new Request.JSON({
				url:"/api/nominatim?format=json&search="+encodeURIComponent(value)+"&scope=2",
				onSuccess: function(results)
				{
					// Generate a list
					var container = new Element("div", {
						'class':"placeNameResults"
					});
					if (results.length == 0)
					{
						var result = new Element("p", {
							html: "No matching places were found."
						});
						container.adopt(result);
						container.style.zIndex = 4000; // Put it behind the invisible backing
					}
					else
					{
						for (var i=0; i<results.length; i++)
						{
							// This self-calling function is needed to prevent each result variable from being overwritten as the loop runs.
							// Otherwise, when the user clicks on a result, the event will always fire off the details of the last result in the list.
							(function(place) {	
								var html = place.display_name;
								if (place.icon != undefined)
								{
									html = "<img src='"+place.icon+"' />"+place.display_name
								}
								var result = new Element("p", {
									html: html,
									id: "placeNameResults_"+i, // TODO: Make globally unique
								});
								result.store("data", place);
								result.addEvent("click", function(e)
								{
									var data = result.retrieve("data");
									container.destroy();
									backing.destroy();
									
									// Move the map to the target
									var target = new OpenLayers.LonLat(data.lon,data.lat).transform(new OpenLayers.Projection("EPSG:4326"), self.map.map.getProjectionObject());
									self.map.map.panTo(target);
									self.map.map.zoomTo(16);
								});
								container.adopt(result);
							})(results[i]);
						}
					}
					
					// Display the list
					container.inject(self.searchField, "after");
					
					// And a transparent backing sheet to intercept clicks outside the list
					var backing = new Element("div", {
						'class':"invisibleBacking"
					});
					backing.addEvent("click", function(e)
					{
						container.destroy();
						backing.destroy();
					});
					backing.inject(container, "after");
				}
			});
			lookup.get();
		}
	}
});
