/*
 * TODO: Support non-maps
 */

var mapContainer, mapElement, map, lastOpenedEventWrapper, totalEvents, loadingEvents=false, apiParams, loadedEvents=100, canLoadMore;

var events = new Array();

function registerMapLinks()
{
	setupEventsShared();
	events = new Array();
	
	var eventElements = $(document.body).getElements("div.event");
	for (var i=0; i<eventElements.length; i++)
	{
		var event= new Event();
		event.populateFromHTML(eventElements[i]);
		
		// Read the event data
		
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
	
	var params = apiParams;
	params.format = "json";
	// Get the date of the last event on the page. This will be parsed by PHP with strtotime
	// TODO: If we switch to proper JS-representations of these events we can use real date objects
	// We repeat the date in case we've loaded some events on this date but not all
	if (params.order == 1) // Most recent first
	{
		params.endDateType = 5;
		params.endDateSpecify = events[events.length-1].start.toISOString();
	}
	else // Earliest first
	{
		params.startDateType = 5;
		params.startDateSpecify = events[events.length-1].start.toISOString();
	}
	
	var qry = Object.toQueryString(params);
	var request = new Request.JSON({
		url:"/api/eventlisting.js?"+qry,
		onSuccess: function(data)
		{
			var passedRepeatEvents = false;
			var addedEvents = 0;
			for (var i=0; i<data.length; i++)
			{
				// Use event object to parse and create HTML output
				var event = new Event();
				event.populateFromArray(data[i]);
				
				if (!passedRepeatEvents)
				{
					// If this event is already on the page, ignore it and continue with the next one
					// Start counting from the last event on the page, and stop if we hit an event with a different date from this one
					// Once we've hit the first non-repeated event we know there won't be more, so there's no need to keep checking
					var skipEvt = false;
					for (var j=events.length-1; j >= 0; j--)
					{
						if (events[j].type.toLowerCase() == event.type.toLowerCase() && events[j].id == event.id)
						{
							skipEvt = true;
							break;
						}
						else if (events[j].start.getDate() != event.start.getDate()) // Only want to compare the day, not the time
						{
							passedRepeatEvents = true;
							break;
						}
					}
					
					if (skipEvt)
						continue;
					else
						passedRepeatEvents = true;
				}
				
				// This event is good, add it to the page
				var wrapper = new Element("div", {
					"id" : event.type.toLowerCase()+"_"+event.id,
					"class" : "event vevent published"
				});
				wrapper.adopt(event.createHTML());
				wrapper.inject(before, "before");
				event.postWrapperSetup();
				
				events.push(event);
				addedEvents++;
			}
			loadedEvents += addedEvents;
			loadingEvents = false;
			loadBar.dispose();
		}
	});
	request.get();
	
	
}
