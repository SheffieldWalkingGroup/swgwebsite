var JFormFieldLocation = new Class({
	map: null,
	gridRefField: null,
	locationNameField: null,
	markerLayer: null,
	marker: null,
	loc: null,
	
	fieldLatitude: null,
	fieldLongitude: null,
	
	initialize: function(id, startPos, markerPos, gridRefFieldId, locationNameFieldId)
	{
		this.map = new SWGMap(id+"_map");
		this.map.setDefaultMap("street");
		var start = new OpenLayers.LonLat(startPos.lng, startPos.lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		this.map.map.setCenter(start,startPos.zoom);
		
		var markerIcon = new OpenLayers.Icon("/images/icons/green.png",{w:8,h:8},{x:-4,y:-4});
		
		this.markerLayer = this.map.map.getLayersByName("Locations")[0];
		
		this.fieldLatitude = document.getElementById(id+"_lat");
		this.fieldLongitude = document.getElementById(id+"_lng");
		
		var self = this;
		
		// Connect to a grid reference field if set
		if (document.id(gridRefFieldId))
		{
			this.gridRefField = document.id(gridRefFieldId);
			
			// Set up a listener on that field
			this.gridRefField.addEvent("change", function(e) {
				self.setLocationFromGridRefField();
			});
		}
		
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
		
		// Maybe place an initial marker - from the specified location or the grid reference field
		if (markerPos != null)
		{
			this.fieldLatitude.value = markerPos.lat;
			this.fieldLongitude.value = markerPos.lon;
			this.placeMarker(markerPos);
		}
		else if (this.gridRefField != null)
		{
			this.setLocationFromGridRefField();
		}
	},
	
	setLocation: function(location)
	{
		// Have we moved a significant distance?
		// We consider this to be 0.015° along either axis (approx 1km).
		var significantMovement = (
			this.fieldLatitude.value == "" || this.fieldLongitude.value == "" ||
			Math.abs(this.fieldLatitude.value - location.lat) >= 0.015 || Math.abs(this.fieldLongitude.value - location.lon) >= 0.015
		);
		
		// Set the location text fields
		this.fieldLatitude.value = location.lat;
		this.fieldLongitude.value = location.lon;
		
		this.writeLocationToGridRefField(location)
		
		if (significantMovement)
			this.writeLocationToNameField(location)
		
		this.placeMarker(location);
	},
	
	placeMarker: function(location)
	{
		var loc = new OpenLayers.LonLat(location.lon,location.lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.map.getProjectionObject());
		
		this.marker = new OpenLayers.Marker(loc);
		
		// Add event to the marker, allowing it to be removed
		// TODO: Could implement dragging too
		var self = this;
		this.marker.events.register('click', this.marker, function(e) {
			self.fieldLatitude.value = "";
			self.fieldLongitude.value = "";
			self.markerLayer.clearMarkers();
			OpenLayers.Event.stop(e);
		});
		this.markerLayer.clearMarkers();
		this.markerLayer.addMarker(this.marker);
		
		this.map.map.setCenter(loc);
	},
	
	setLocationFromGridRefField: function()
	{
		var gridRef = this.gridRefField.value.replace(/\s/g,'');
		var OSRef = OsGridRef.parse(gridRef);
			
		// Make sure this is valid
		// TODO: Maybe put the validator on the gridref field?
		if (OSRef.easting != NaN && OSRef.northing != NaN)
		{
			var loc = OsGridRef.osGridToLatLong(OSRef);
			// Unpack the lat/lon functions
			this.setLocation({'lat':loc.lat(), 'lon':loc.lon()});
		}
	},
	
	writeLocationToGridRefField: function(location)
	{
		if (this.gridRefField)
		{
			// Convert location to OS Grid reference
			var locLatLon = new LatLon(location.lat, location.lon);
			var OSRef = OsGridRef.latLongToOsGrid(locLatLon);
			this.gridRefField.value = OSRef.toString(6);
		}
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
	}
});
