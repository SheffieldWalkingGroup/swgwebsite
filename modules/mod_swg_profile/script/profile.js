/**
 * The element containing this module
 */
var container = null;

var walkCount;
var walkMiles;
window.addEvent("domready", function()
{
	container = document.getElement("#swg_profile");
});

// Listen for attendance change events
// TODO: Total change
document.addEvent("attendanceChange", function(data) {
	container.getElement(".walks .num").set("text", data.stats.walks.alltime.count);
	container.getElement(".walks .distance").set("text", data.stats.walks.alltime.sum_distance);
});