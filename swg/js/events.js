function displayEvent(event, container, newMembers) {
	currentEvent = event;
	// Add alterations to the whole event
	
	if (event.alterations.cancelled) {
		container.addClass("cancelled");
		
	}
		
	
	// Build up the basic components that apply to all event types
	var infoHeader = new Element("div", {"class":"eventheader"});
	var eventName = new Element("h3", {"text":event.name});
	var eventDate = new Element("span",{"class":"date","text":timestampToDate(event.start)});
	if (event.alterations.date)
		eventDate.addClass("altered");
	
	infoHeader.adopt(eventDate, eventName);
	container.adopt(infoHeader);
	
	var eventBody = new Element("div", {"class":"eventbody"});
	container.adopt(eventBody);
	
	var description = new Element("div", {"class":"description","html":"<p>"+event.description+"</p>"});
	eventBody.adopt(description);
	if (event.alterations.details)
		description.addClass("altered");
	
	var eventInfo = new Element("div", {"class":"eventinfo"});
	eventBody.adopt(eventInfo);
	eventBody.adopt(new Element("div", {"style":"clear:both;"}));
	
	// Now do specific components
	switch(event.type.toLowerCase()) {
		case "walk":
			// Add the day to the class
			var start = new Date(event.start*1000);
			var day = "weekday";
			if (start.getDay() == 0)
				day = "sunday";
			else if (start.getDay() == 6)
				day = "saturday";
			container.addClass("walk"+day);
			
			var rating = new Element("span", {"class":"rating", "text":event.distanceGrade+event.difficultyGrade+" ("+event.miles+" miles)"});
			rating.inject(eventName,'before');
			
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
			
			var transportText = "";
			if (event.meetPoint.id != 7) // TODO: Remove magic number
			{
				// Parse the meeting time
				var meetTime = new Date(event.meetPoint.meetTime*1000);
				transportText += "Meet at "+meetTime.format("%H:%M")+" at ";
			}
									
			var transport = new Element("p", {
				"class":"transport",
				"html":"<span>Transport:</span> " + transportText
			});
			if (event.meetPoint.location != null)
			{	
				var transportLink = new Element("a", {
					"title":"Map of meeting point",
					"href":"http://www.streetmap.com/loc/N"+event.meetPoint.location.lat+",E"+event.meetPoint.location.lng,
					"target":"_blank",
					"rel":"map-transport",
					"html":event.meetPoint.longDesc
				});
// 				transportLink.addEvent("click",function(event){
// 					event.stop();
// 					showMap();
// 					map.showPoint(currentEvent.id, 'meet');
// 				});
				transport.adopt(transportLink);

				transport.adopt(document.createTextNode(". "));
			}
			else
			{
				transport.adopt(document.createTextNode(event.meetPoint.longDesc+". "));
			}
			
			if (event.meetPoint.extra != null)
			{
				transport.adopt(document.createTextNode(event.meetPoint.extra));
			}
			eventInfo.adopt(transport);
			if (event.alterations.placeTime)
				transport.addClass("altered");
			
			var leaderText = event.leader.displayName+
				" ("+event.leader.telephone+")";
			if (event.leader.noContactOfficeHours)
				leaderText += " &ndash; don't call during office hours";
			
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
			area.inject(eventName,'before');
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
				"html":"<span>Payment due:</span> "+timestampToDate(event.paymentDue)
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
				startTime = timestampToTime(event.newMemberStart);
			else if (event.start != undefined && timestampToTime(event.start) != "00:00")
				startTime = timestampToTime(event.start);
			
			if (newMembers && event.newMemberEnd != undefined)
				endTime = timestampToTime(event.newMemberEnd);
			else if (event.end != undefined)
				endTime = timestampToTime(event.end);
			
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

function timestampToDate(timestamp) {
	var dayNames = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
	var monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
	var date = new Date(timestamp*1000); // Convert to milliseconds
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

function timestampToTime(timestamp) {
	var date = new Date(timestamp*1000);
	var hours = date.getHours();
	var minutes = date.getMinutes();
	if (hours < 10)
		hours = "0"+hours;
	if (minutes < 10)
		minutes = "0"+minutes;
	return hours+":"+minutes;
}
