var Location = new Class({
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
	
	initialize: function(id, startPos, startZoom, locations, gridRefFieldIds, locationNameFieldId)
	{
		this.map = new SWGMap(id+"_map");
		this.map.setDefaultMap("street");
		var start = new OpenLayers.LonLat(startPos.lng, startPos.lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		this.map.map.setCenter(start,startZoom);
		
		var markerIcon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
		
		this.markerLayer = this.map.map.getLayersByName("Locations")[0];
		
		this.outputField = document.getElementById(id);
		
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
		
		
		if (document.id(locationNameFieldId))
		{
			this.locationNameField = document.id(locationNameFieldId);
			this.locationNameField.addEvent("change", function(result)
			{
				// Scroll to this location
				// We DON'T place a marker, because it's very unlikely to be the exact place.
				// Just scroll the map & zoom so the user can click the exact place to mark.
				var target = new OpenLayers.LonLat(result.lon,result.lat).transform(new OpenLayers.Projection("EPSG:4326"), self.map.map.getProjectionObject());
				self.map.map.panTo(target);
				self.map.map.zoomTo(16);
			});
		}
		
		// Place markers for all locations
		for (var i=0; i<locations.length; i++)
		{
			this.addLocation(new OpenLayers.LonLat(locations[i].lng, locations[i].lat));
		}
		
		// Connect to a grid reference fields if set
		if (gridRefFieldIds != undefined)
		{
			// Create a new location for each grid reference. Read the current reference and hook up an event listener to the field
			for (i=0; i<gridRefFieldIds.length; i++)
			{
				var field = document.id(gridRefFieldIds[i])
				if (field)
				{
					var loc = this.getLocationFromGridRefField(field);
					var location = this.addLocation(new OpenLayers.LonLat(loc.lon(), loc.lat()));
					location.gridRefField = field;
					
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
								
								break;
							}
						}
					});
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
					// TODO: Can we store the index on the marker somehow?

					// i gives the index of the marker and location
					var i = marker.index;
					var point = new OpenLayers.LonLat(marker.geometry.x, marker.geometry.y);
					var location = point.transform(
						self.map.map.getProjectionObject(), new OpenLayers.Projection("EPSG:4326")
					);
					self.locations[i].lonLat = location;
					
					// Do we have a grid reference field
					if (self.locations[i].gridRefField != null)
					{
						self.writeLocationToGridRefField(location, self.locations[i].gridRefField);
					}
					self.outputLocations();
				}
				
			}
		);
		
		this.map.map.addControl(dragFeature);
		dragFeature.activate();
	},
	
	/**
	 * Adds a new location at the end of the list
	 * @param OpenLayers.LonLat lonLat Location to add (EPSG:4326)
	 */
	addLocation:function(lonLat)
	{
		var location = new Location();
		location.lonLat = lonLat;
		var mapPoint = Object.clone(lonLat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		location.markerGeometry = new OpenLayers.Geometry.Point(mapPoint.lon, mapPoint.lat);
		location.marker = new OpenLayers.Feature.Vector(location.markerGeometry);
		location.marker.index = this.locations.length;
		// TODO: styles
		this.locations.push(location);
		
		var featuresToAdd = [location.marker]
		
		// If this location isn't the first one, add a line from the previous point to this one
		if (this.locations.length >= 2)
		{
			var prevLocation = this.locations[this.locations.length-2];
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
		this.outputLocations();
		return location;
	},
	
	/**
	 * Sets the position of a marker, internally and on the map.
	 * @param int index Marker to move. If there is no marker there, nothing is done.
	 * @param OpenLayers.LonLat location Location to move marker to (EPSG:4326)
	 */
	setLocation:function(index, location)
	{
		if (index >= this.locations.length)
			return false;
		
		var loc = this.locations[index];
		var mapPoint = Object.clone(location).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		
		loc.lonLat = location;
		loc.markerGeometry.x = mapPoint.lon;
		loc.markerGeometry.y = mapPoint.lat;
		
		this.markerLayer.redraw();
		
		this.outputLocations();
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
			jsonOut.push(this.locations[i].lonLat);
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
		if (OSRef.easting != NaN && OSRef.northing != NaN)
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
	
	writeLocationToNameField: function(location)
	{
		if (this.locationNameField)
		{
			// Reverse geocode this location name, unless we already have a location name and we haven't moved some significant distance (see above)
			if (this.locationNameField.value == "")
			{
				var self = this;
				var lookup = new Request.JSON({
					url: "/api/nominatim?lat="+location.lat+"&lon="+location.lon+"&format=json",
					onSuccess: function(placeName)
					{
						if (placeName)
							self.locationNameField.value = placeName;
						else
							self.locationNameField.value = "";
					}
					// TODO: onFailure
				});
				lookup.get();
			}
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
