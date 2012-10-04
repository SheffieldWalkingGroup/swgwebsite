var ratingTips
var setupRatingTips = function() {
	ratingTips = new Tips(".rating",{
    	showDelay:250,
    	hideDelay:250,
    	className:"ratingTip",
    	fixed:true,
    	id:"ratingTooltip",
    	waiAria:true,
    	title:function(el) {
    		return "Walk grades";
    	},
    	text:function(el) {
    		return "<h4>Distance</h4>" +
    				"<ol style='list-style-type:upper-alpha'>" +
    				"<li>Short - up to 8 miles</li><li>Medium - 8 to 12 miles</li><li>Long - more than 12 miles</li>" +
    				"</ol>"+
    				"<h4>Difficulty</h4>" +
    				"<ol style='list-style-type:numeric;'>" +
    				"<li>Easy terrain with a couple of mild climbs</li><li>Moderate terrain with some tricky parts and steady climbs</li><li>Hard terrain, possibly with exposure and scrambling, with steep and long ascents</li>" +
    				"</ol>";
    	}
    });
}

window.addEvent('domready', setupRatingTips);