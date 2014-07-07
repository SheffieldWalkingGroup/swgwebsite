var highlightedEvents;

function setupEventsShared()
{
	// Load which events have been highlighted
	if (hasLocalStorage())
	{
		if (localStorage.highlightedEvents)
			highlightedEvents = JSON.parse(localStorage.highlightedEvents);
		else
			highlightedEvents = new Object();
	}
}

/**
 * Data-based representation of an event, and its HTML representation on the page
 * Events that are displayed in the initial load have certain elements loaded as the page is initialised
 * TODO: Could merge with event in maps.js
 */
var Event = new Class({
	type : null,
	id : null,
	container : null,
	htmlBody : null,
	name : null,
	start : null,
	end : null,
	content : null,
	mapOpen : false,
	map : null,
	mapLink : null,
	attended : null,
	miles : null,
	distanceGrade : null,
	difficultyGrade : null,
	organiser: null,
	
	/**
	 * Set up the event from an HTML element in the page
	 * @param container Element containing the whole event
	 */
	populateFromHTML : function(container)
	{
		this.alterations = {any: false, cancelled: false, placeTime: false, organiser: false, details: false, date: false};
		this.container = container;
		this.htmlBody = container.getElement(".eventbody");
		
		this.type = container.id.substring(0,container.id.indexOf("_"));
	    this.id   = container.id.substring(container.id.indexOf("_")+1);
		
		this.name = container.getElement(".summary").get("text");
		var start = container.getElement(".dtstart");
		if (start.hasClass("altered"))
		{
			this.alterations.date = true;
			this.alterations.any = true;
		}
		var startTime = start.getProperty("datetime")
		this.start = new Date(startTime); // TODO iPhone
		
		var attendedChk = container.getElement(".attendance img");
		if (attendedChk != null) // No box for cancelled events
		{
			this.attended = attendedChk.getProperty("src").indexOf("tickbox") == -1;
		}
		
		if (this.type == "walk")
		{
			// Split the rating element into grades & distance
			var rating = container.getElement(".rating").get("text");
			var ratingRE = /([A-Z])([0-9])[^0-9]*([0-9.]+)mi/;
			var matches = ratingRE.exec(rating);
			if (matches)
			{
				this.distanceGrade = matches[1];
				this.difficultyGrade = matches[2];
				this.miles = matches[3];
			}
			
			this.organiser = new Organiser();
			var organiserBits = container.getElement(".leader .val");
			var nameEl = organiserBits.getElement("[itemprop=name]")
			if (nameEl)
				this.organiser.displayName = nameEl.innerHTML;
			var telEl = organiserBits.getElement("[itemprop=telephone]")
			if (telEl)
				this.organiser.telephone = telEl.innerHTML;
			this.organiser.noContactOfficeHours = organiserBits.hasClass("noContactOfficeHours");
			
			if (organiserBits.parentNode.hasClass("altered"))
			{
				this.alterations.organiser = true;
				this.alterations.any = true;
			}
			
			if (this.htmlBody.getElement(".transport") && this.htmlBody.getElement(".transport").hasClass("altered"))
			{
				this.alterations.placeTime = true;
				this.alterations.any = true;
			}
		}
		else if (this.type == "weekend")
		{
			if (this.htmlBody.getElement("weekendbooking") && this.htmlBody.getElement("weekendbooking").hasClass("altered"))
			{
				this.alterations.organiser = true;
				this.alterations.any = true;
			}
		}
		else if (this.type == "social")
		{
			if (
				(this.htmlBody.getElement("start") && this.htmlBody.getElement("start").hasClass("altered")) || 
				(this.htmlBody.getElement("location") && this.htmlBody.getElement("location").hasClass("altered"))
			)
			{
				this.alterations.placeTime = true;
				this.alterations.any = true;
			}
		}
		
		// End time might be a full date, or just a time on the start date
		var endTime = container.getElement(".dtend").getProperty("datetime");
		if (endTime.indexOf("T") == -1)
		{
			var endTime = startTime.substring(0, 10) + "T" + endTime;
		}
		this.end = new Date(endTime);
		
		// Look for remaining alterations
		
		if (container.getElement(".content").hasClass("cancelled"))
		{
			this.alterations.cancelled = true;
			this.alterations.any = true;
		}
		
		if (this.htmlBody.getElement(".description").hasClass("altered"))
		{
			this.alterations.details = true;
			this.alterations.any = true;
		}
		
		this.postWrapperSetup();
	},
	
	/**
	 * Set up the event from an array received via AJAX
	 * @param data Object Data to populate from
	 */
	populateFromArray: function(data)
	{
		for (var i in data)
		{
			// Parse dates. Remember the incoming dates are seconds from 1970, JS uses milliseconds.
			if (i == "start" || i == "end")
			{
				var date = new Date(data[i]*1000);
				this[i] = date;
			}
			else if (data.hasOwnProperty(i))
			{
				this[i] = data[i];
			}
		}
		
		// TODO: Populate organiser info
	},
	
	/**
	 * Create a new HTML representation of this event. The event must be populated from an array first.
	 * The generated HTML is not inserted into the document.
	 * @param Element container Optional - pass in an existing container to re-use it
	 * @return Element Element wrapping the new HTML representation
	 */
	createHTML: function(container)
	{
		if (container == undefined)
			container = new Element("div",{"class":"content "+this.type.toLowerCase()});
		
		this.container = container;
		
		displayEvent(this, container, false);
		
		if (this.alterations.cancelled)
		{
			var cancelledMsg = new Element("p", {
				'class' : 'cancelled-message',
				'html' : 'Cancelled'
			});
			container.adopt(cancelledMsg);
		}
		else
		{
		
			// Add attendance checkbox for past events
			if (canRecordAttendance && this.start < new Date())
			{
				var attendPara = new Element("p");
				var attendButton = new Element("a", {
					"class" : "attendance",
					"href" : window.location+"?"+Object.toQueryString({
						task : "attendance.attend",
						evttype : this.type,
						evtid : this.id,
						set : (this.attendedBy[userID] ? 0 : 1)
					}),
					"html" : "<img src='/images/icons/"+(this.attendedBy[userID] ? "tick" : "tickbox")+".png' width='19' height='16' /> You did this"
				});
				
				attendPara.adopt(attendButton);
				container.getElements(".eventinfo").adopt(attendPara);
				
				if (this.attendedBy[userID] && this.type.toLowerCase() == "walk")
				{
					container.getElements(".eventinfo").adopt(new Element("p", {html : "<a href='/whats-on/your-diary/upload-track?wi="+this.id+"'>Share GPS track</a>"}));
					container.getElements(".eventinfo").adopt(new Element("p", {html : "<a href='/photos/upload-photos'>Share photos</a>"}));
				}
				
			}
		}
		
		// Move extra stuff
		container.getElements(".description").adopt(container.getElements(".icons"));
		container.getElements(".eventinfo").adopt(new Element("div",{"class":"controls"}));
		
		return container;
	},
	
	/**
	 * Setup called after the event and container are both populated.
	 * @access private
	 */
	postWrapperSetup: function()
	{
		var self = this;
	    
	    // Standard map link
		// Get the map link
		this.mapLink = this.container.getElements('a[rel="toggle-map"]')[0];
		if (this.mapLink != undefined)
		{
			this.mapLink.addEvent('click',function(event)
			{
				self.openMap();
				event.stop();
			});
		}
	    
	    if (this.type == "walk")
    	{
			// Add events to other links
			var startLink = this.container.getElements('a[rel="map-start"]')[0];
			startLink.addEvent('click',function(event)
			{
				event.stop();
				self.openMap();
				self.map.showPoint(self.id, 'start');
			});
			var endLink = this.container.getElements('a[rel="map-end"]')[0];
			if (endLink != undefined)
			{
				endLink.addEvent('click',function(event)
				{
					event.stop();
					self.openMap();
					self.map.showPoint(self.id, 'end');
				});
			}
			var meetLink = this.container.getElements('a[rel="map-transport"]')[0];
			if (meetLink != undefined)
			{
				meetLink.addEvent('click',function(event)
				{
					event.stop();
					self.openMap();
					self.map.showPoint(self.id, 'meet');
				});
			}
    	}
	    else
    	{
			// Socials & weekends only have one location
			var generalLink = this.container.getElements('a[rel="map"]')[0];
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
			"id":"map_"+this.type+"_"+this.id,
			"class":"map"
		});
		this.mapContainer.adopt(this.mapElement);
		
		// Set up the highlighting controls
		if (hasLocalStorage())
		{
			var highlightWrapper = new Element("p");
			this.highlightLink = new Element("a",{
				"href":"#",
				"html":"Highlight",
				"title":"Highlight this "+this.type.toLowerCase()+" on your computer"
			});
			highlightWrapper.adopt(this.highlightLink);
			var possibleControls = this.container.getElements('[class="controls"]');
			if (possibleControls.length > 0)
				possibleControls[0].adopt(highlightWrapper);
			this.highlightLink.addEvent('click',function(event)
			{
				event.stop();
				self.highlightEvent();
			});
			
			// Should this event already be highlighted?
			if (
				highlightedEvents[this.type] != undefined &&
				highlightedEvents[this.type][this.id] != undefined &&
				highlightedEvents[this.type][this.id]
			)
			this.highlightEvent();
		}
		
		// Set up the attendance checkbox
		var checkbox = this.container.getElement("a.attendance");
		
		if (checkbox != null)
		{
			checkbox.addEvent('click',function(event)
			{
				// TODO: Lots of stuff here that can be abstracted out/simplified/etc
				event.stop();
				var attended = (checkbox.getElement("img").src.indexOf("tickbox") != -1);
				
				var type;
				if (self.type.toLowerCase() == "walk")
					type = 1;
				else if (self.type.toLowerCase() == "social")
					type = 2;
				else if (self.type.toLowerCase() == "weekend")
					type = 3;
				
				var attendParams = "evttype="+type+"&evtid="+self.id+"&set="+(attended ? 1 : 0);
				var request = new Request.JSON({
					// TODO: Should be format=json
					url: "?task=attendance.attend&"+attendParams+"&json=1&stats=1",
					onSuccess: function(data)
					{
						self.attended = attended;
						// TODO: Update attendees count on this event (could just redraw the event with JS methods used to load the bottomless page)
						
						if (attended)
						{
							checkbox.getElement("img").src = "/images/icons/tick.png";
							
							// Show the popup
							var title = self.name;
							
							var mainText = new Element("p", {
								'class' : 'diary-added-msg',
								'html' : "This "+self.type+" has been added to <a href='/your-diary'>your diary</a>."
							});
							
							var optionsList = new Element("ul", {'class' : 'diary-added-options'});
							/*var postToFB = new Element("li");
							var postToFBLink = new Element("a", {
								'href' : "?task=attendance.facebook&"+attendParams+"&json=1",
								'html' : "<img src='/images/icons/facebook_100.png' width='100' height='100' alt='' /><br />Post to Facebook"
							});
							postToFB.adopt(postToFBLink);*/
							var sharePhotos = new Element("li");
							var sharePhotosLink = new Element("a", {
								'href' : "/photos/upload-photos",
								'html' : "<img src='/images/icons/image_100.png' width='100' height='100' alt='' /><br/ >Share photos"
							});
							sharePhotos.adopt(sharePhotosLink);
							
							optionsList.adopt(/*postToFB, */sharePhotos);
							
							if (type == 1) // Event::Type_Walk
							{
								var shareTrack = new Element("li");
								shareTrackLink = new Element("a", {
									'href' : "/your-diary/upload-track?wi="+self.id,
									'html' : "<img src='/images/icons/track_100.png' width='100' height='100' alt='' /><br />Share GPS track"
								});
								shareTrack.adopt(shareTrackLink);
								optionsList.adopt(shareTrack);
							}
							
							var body = new Element("div");
							body.adopt(mainText, optionsList);
							
							Popup(title, body);
						}
						else
						{
							checkbox.getElement("img").src = "/images/icons/tickbox.png";
						}
						
						// Fire an event on the document element so any modules that show this info can update
						document.fireEvent("attendanceChange", data);
					}
				});
				// TODO: Show animation while waiting for response
				request.get();
			});
		}
		
		// Fire the event ready event
		window.fireEvent("eventReady", [this, this.container]);
	},
	
	setupMap : function()
	{
		// Put the map elements in place
		this.htmlBody.parentNode.adopt(this.mapContainer);
		
		// Create the map
		this.map = new SWGMap("map_"+this.type+"_"+this.id);
		if (this.type == "walk")
		{
			this.map.setDefaultMap("landscape");
			var wi = this.map.addWalkInstance(this.id);
			this.map.addLoadedHandler(function()
			{
				var route = new Route("Planned route");
				route.load("walkinstance", wi.id, 10, wi);
				
				var track = new Route("Recorded track");
				track.load("walkinstance", wi.id, 20, wi);
			});
		}
		else if (this.type == "social")
		{
			this.map.addSocial(this.id);
			this.map.setDefaultMap("street");
		}
		else if (this.type == "weekend")
		{
			this.map.addWeekend(this.id);
			this.map.setDefaultMap("street"); // Landscape map doesn't work well at small scales
		}
	},
		
	openMap : function()
	{
		if (this.mapOpen)
			return;
		
		// Close all (other) map elements
		if (typeof(events) != "undefined")
		{
			for (var i=0; i<events.length; i++)
			{
				events[i].closeMap();
			}
		}
		
		this.setupMap();
		
		// Open the map
		var self = this;
		var openFx = new Fx.Tween(this.mapContainer, {
			transition:Fx.Transitions.Quad.easeOut,
			onComplete: function() {
				self.mapOpen = true;
				
				if (typeof (self.mapLink) != "undefined")
				{
					self.mapLink.set('html',"Hide map");
					self.mapLink.removeEvents();
					self.mapLink.addEvent('click',function(e){
						self.closeMap();
						e.stop();
					});
				}
			}
		});
		
		// Need to also set the height of the event container if one is set
		if(this.container.style.height)
		{
			var openContainer = new Fx.Tween(this.container, 
			{
				transition: Fx.Transitions.Quad.easeOut
			});
			openContainer.start("height", this.container.getStyle('height').toInt() + 400);
		}
		
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
				
				if (typeof(self.mapLink) != "undefined")
				{
					self.mapLink.set('html',"Show map");
					self.mapLink.removeEvents();
					self.mapLink.addEvent('click',function(e){
						self.openMap();
						e.stop();
					});
				}
			}
		});
		
		// Need to also set the height of the event container if one is set
		// If the height is 0, it's already closed, so do nothing
		if(this.container.style.height && this.container.style.height > 0)
		{
			var openContainer = new Fx.Tween(this.container, 
			{
				transition: Fx.Transitions.Quad.easeOut
			});
			openContainer.start("height", this.container.retrieve("openheight"));
		}
		
		closeFx.start("height",0);
	},
	
	highlightEvent : function()
	{
		this.container.addClass("highlighted");
		
		// Store the highlighting
		if (highlightedEvents[this.type] == undefined)
		{
			highlightedEvents[this.type] = new Object();
		}
		highlightedEvents[this.type][this.id] = true;
		localStorage.highlightedEvents = JSON.stringify(highlightedEvents);
		
		// Swap the event type
		this.highlightLink.removeEvents();
		var self = this;
		this.highlightLink.set('html','Remove highlight');
		this.highlightLink.set('title','Remove the highlight from this '+this.type.toLowerCase());
		this.highlightLink.addEvent('click',function(e){
			e.stop();
			self.unhighlightEvent();
			
		});
	},
	
	unhighlightEvent : function()
	{
		this.container.removeClass("highlighted");
		
		// Clear the highlighting from storage
		delete highlightedEvents[this.type][this.id];
		localStorage.highlightedEvents = JSON.stringify(highlightedEvents);
		
		// Swap the event type
		this.highlightLink.removeEvents();
		var self = this;
		this.highlightLink.set('html','Highlight');
		this.highlightLink.set('title',"Highlight this "+this.type.toLowerCase()+" on your computer");
		this.highlightLink.addEvent('click',function(e){
			e.stop();
			self.highlightEvent();
			
		});
	},
	
	phoneCallLeaderPopup : function()
	{
		var leader = this.organiser;
		
		var header = "Contact leader";
		
		var callButton = "<a class='contact-leader call'";
		
		var content = "<p>Contact the leader to ask about this walk, or text them to book on. "+
			"Please be considerate and avoid calling late at night, for example." +
			(leader.noContactOfficeHours ? "<br>This leader has asked not to be contacted during office hours.":"")+"</p>";
		
		if (!leader.noContactOfficeHours || function() {
			var date = new Date();
			// Return true at the weekend, or after 17:30 on weekdays
			return (
				date.getDay == 0 || date.getDay == 6 ||
				date.getHours() >= 18 ||
				date.getHours() == 17 && date.getMinutes() >= 30
			);
		})
		{
			callButton += " href='tel:"+leader.telephone+"'>Call leader to ask questions";
		}
		else
		{
			callButton += ">This leader does not want to be called during office hours";
		}
		callButton += "</a>";
		content += callButton;
		
		content += "<a class='contact-leader text' href='sms:"+leader.telephone+"'>Text leader to book on this walk.<br>Leaders are not expected to reply to texts.</a>";
		Popup(header, content)
	}
});

var Organiser = new Class({
	displayName: "",
	telephone: "",
	noContactOfficeHours: false
});

/**
 * Populates a container with details of an event
 * @param Event event Event to display
 * @param Element container HTML element to populate
 * @param bool newMembers This display is aimed at new members
 */
function displayEvent(event, container, newMembers) {
	currentEvent = event;
	// Add alterations to the whole event
	
	if (event.alterations.cancelled) {
		container.addClass("cancelled");
	}
		
	// Build up the basic components that apply to all event types
	var infoHeader = new Element("div", {"class":"eventheader"});
	var eventName = new Element("h3", {"text":event.name});
	var eventDate = new Element("time",{
		"class":"dtstart date",
		"text":displayDate(event.start),
		"datetime":displayTimestamp(event.start),
	});
	// TODO: End time
	if (event.alterations.date)
		eventDate.addClass("altered");
	
	infoHeader.adopt(eventDate, eventName);
	container.adopt(infoHeader);
	
	this.eventBody = new Element("div", {"class":"eventbody"});
	container.adopt(this.eventBody);
	
	var description = new Element("div", {"class":"description","html":event.description});
	this.eventBody.adopt(description);
	if (event.alterations.details)
		description.addClass("altered");
	
	var eventInfo = new Element("div", {"class":"eventinfo"});
	this.eventBody.adopt(eventInfo);
	this.eventBody.adopt(new Element("div", {"style":"clear:both;"}));
	
	var extraHead = new Element("p", {"class":"headerextra"});
	extraHead.inject(eventName,'before');
	
	// Now do specific components
	switch(event.type.toLowerCase()) {
		case "walk":
			// Add the day to the class
			var day = "weekday";
			if (event.start.getDay() == 0)
				day = "sunday";
			else if (event.start.getDay() == 6)
				day = "saturday";
			container.addClass("walk"+day);
			
			var rating = new Element("span", {"class":"rating", "text":event.distanceGrade+event.difficultyGrade});
			var distance = new Element("span", {"class":"distance", "text":" ("+event.miles+" miles)"});
			extraHead.adopt(rating);
			extraHead.adopt(distance);
			
			// TODO: icons
			var start = new Element("p", {
				"class":"start", 
				"html":
					"<span>Start:</span> "
				});
			var startLink = new Element("a", {
				"title":"Map of approximate location",
				"href":"http://www.streetmap.com/loc/"+event.startGridRef,
				"target":"_blank",
				"rel":"map-start",
				"html":event.startGridRef+", "+event.startPlaceName
			});
			startLink.addEvent("click",function(event){
				event.stop();
// 				if (map == null)
// 				{
// 					showMap();
// 				}
// 				map.showPoint(currentEvent.id, 'start');
			});
			start.adopt(startLink);
			eventInfo.adopt(start);
			if (event.isLinear == true) {
				// TODO: Map of end
				var end = new Element("p", {
					"class":"end", 
					"html":
						"<span>End:</span> " +
						event.endGridRef+", "+event.endPlaceName
				});
				eventInfo.adopt(end);
			}
			
			var leaderText = event.leader.displayName;
			
			var leader = new Element("p", {
				"class":"leader",
				"html":"<span>Leader:</span> "+leaderText
			});
			if (event.alterations.organiser)
				leader.addClass("altered");
			eventInfo.adopt(leader);
			
			var backmarker = new Element("p", {
				"class":"backmarker",
				"html":"<span>Backmarker:</span> "+event.backmarker.displayName
			});
			eventInfo.adopt(backmarker);
			
			// Walk icons
			var iconContainer = new Element("p", {
				"class":"icons"
			});
			if (event.isLinear == true)
			{
				var linearIcon = new Element("img", {
					"src":"/images/stories/linearwalk.png",
					"border":0,
					"alt":"Linear walks start at one place and finish at another; usually this means we have to use public transport",
					"title":"Linear walks start at one place and finish at another; usually this means we have to use public transport"
				});
				iconContainer.adopt(linearIcon);
			}
			if (event.dogFriendly == true)
			{
				var dogIcon = new Element("img", {
					"src":"/images/stories/dogs.png",
					"border":0,
					"alt":"Dog-friendly: the route is suitable for bringing dogs along",
					"title":"Dog-friendly: the route is suitable for bringing dogs along"
				});
				iconContainer.adopt(dogIcon);
			}
			if (event.childFriendly == true)
			{
				var pramIcon = new Element("img", {
					"src":"/images/stories/pushchair.png",
					"border":0,
					"alt":"Kiddy-friendly: route (and pace) of walk are suitable for bringing infants. Check with the walk leader what kind of prams/buggies can be used.",
					"title":"Kiddy-friendly: route (and pace) of walk are suitable for bringing infants. Check with the walk leader what kind of prams/buggies can be used."
				});
				iconContainer.adopt(pramIcon);
			}
			if (event.speedy == true)
			{
				var speedIcon = new Element("img", {
					"src":"/images/stories/speedy.png",
					"border":0,
					"alt":"Fast-paced walk. This kind of walk will be done faster than usual, aiming for an early finish.",
					"title":"Fast-paced walk. This kind of walk will be done faster than usual, aiming for an early finish."
				});
				iconContainer.adopt(speedIcon);
			}
			eventInfo.adopt(iconContainer);
			
			//showMap();
			
			break;
		case "weekend":
			var area = new Element("span", {
				"class":"area",
				"text":event.area
			});
			extraHead.adopt(area,'before');
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
				"html":"<span>Payment due:</span> "+displayDate(new Date(event.paymentDue*1000))
			});
			if (event.alterations.organiser)
				contact.addClass("altered");
			eventInfo.adopt(moreInfo,places,bookingsOpen,contact,paymentDue);
			break;
		case "social":
			if (event.bookingsInfo)
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
				startTime = displayTime(event.newMemberStart);
			else if (event.start != undefined && displayTime(event.start) != "00:00")
				startTime = displayTime(event.start);
			
			if (newMembers && event.newMemberEnd != undefined)
				endTime = displayTime(event.newMemberEnd);
			else if (event.end != undefined)
				endTime = displayTime(event.end);
			
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
			
			break;
	}
	
	// Add a fancy scrollbar to the description
	if (description.getScrollSize().y > description.getSize().y) {
		description.style.width="290px"; // Make room for the scrollbar
		var scrollbar = new Element("div",{"class":"scrollbar-vert", "id":"scrollbar"});
		var scrollHandle = new Element("div",{"class":"handle-vert", "id":"scrollHandle"});
		description.grab(scrollbar,'before');
		scrollbar.adopt(scrollHandle);
		
		makeScrollbar(description,scrollbar,scrollHandle,false,false);
	}
	
	// Register this popup for rating tooltips
	if (event.type.toLowerCase() == "walk")
		ratingTips.attach(rating);
}

function displayDate(date) {
	var dayNames = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
	var monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
	var dayName = dayNames[date.getDay()];
	var dayDate = date.getDate();
	var month = monthNames[date.getMonth()];
	var year = date.getFullYear();
	
	// Dirty, but saves unnecessary string conversion & length calculations
	var suffix = "th";
	if (dayDate == 1 || dayDate == 11 || dayDate == 21 || dayDate == 31)
		suffix = "st";
	else if (dayDate == 2 || dayDate == 22)
		suffix = "nd";
	else if (dayDate == 3 || dayDate == 23)
		suffix = "rd";
	return dayName+" "+dayDate+suffix+" "+month+(year != new Date().getFullYear() ? " "+year:"");
}

function displayTime(date) {
	var hours = date.getHours();
	var minutes = date.getMinutes();
	if (hours < 10)
		hours = "0"+hours;
	if (minutes < 10)
		minutes = "0"+minutes;
	return hours+":"+minutes;
}

function displayTimestamp(date)
{
	var year = date.getFullYear();
	var month = date.getMonth()+1; // Zero indexed
	var day = date.getDate();
	var time = displayTime(date);
	var offset = -date.getTimezoneOffset(); // This is weird - it's in minutes (OK), and the other way round from normal
	var tzHours = Math.floor(offset/60);
	var tzMins = offset%60;
	if (month < 10)
		month = "0"+month;
	if (day < 10)
		day = "0"+month;
	if (tzHours < 10)
		tzHours = "0"+tzHours;
	if (tzHours >= 0)
		tzHours = "+"+tzHours;
	if (tzMins < 10)
		tzMins = "0"+tzMins;
	return year + "-" + month + "-" + day + "T" + time + tzHours + ":" + tzMins;
}