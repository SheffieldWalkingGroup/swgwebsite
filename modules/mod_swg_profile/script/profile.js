/**
 * The element containing this module
 */
var container = null;

var walkCount;
var walkMiles;
window.addEvent("domready", function()
{
	container = document.getElement("#swg_profile");

	walkCount = parseInt(container.getElement(".walks .num").get("text"),10);
	walkMiles = parseFloat(container.getElement(".walks .miles").get("text"));
});

// Listen for attendance change events
document.addEvent("attendanceChange", function(event) {
	switch (event.type.toLowerCase())
	{
		case "walk":
			if (event.attended)
			{
				walkCount++;
				walkMiles += parseFloat(event.miles);
			}
			else
			{
				walkCount--;
				walkMiles -= parseFloat(event.miles);
			}
			// Update the display
			container.getElement(".walks .num").set("text", walkCount);
			container.getElement(".walks .miles").set("text", walkMiles);
	}
});