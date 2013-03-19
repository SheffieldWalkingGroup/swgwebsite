var JFormFieldPlaceName = new Class({
	textField: null,
	button: null,
	
	initialize: function(textFieldId, buttonId)
	{
		var textField = document.id(textFieldId);
		var button = document.id(buttonId);
		
		// Store a reference to this JS object so we can connect the map
		textField.store("controller", this);
		
		// Set up a click listener on the button to search for the value of the text field
		button.addEvent("click", function(e) {
			var value = textField.value;
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
									var data = result.retrieve("data")
									textField.fireEvent("change", data);
									container.destroy();
									backing.destroy();
								});
								container.adopt(result);
							})(results[i]);
						}
					}
					
					// Display the list
					container.inject(button, "after");
					
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
		});
	}
})