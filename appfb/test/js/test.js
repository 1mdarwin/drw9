// This file is for interactive with canvas facebook form jquery
// Is an example from "Design and code" 
var tabContent = $('.tab_content');
	tabs = $('ul.tabs li');
	tabContent.hide();
	tabs.eq(0).addClass("active").show(); //Default to first tab
	tabs.eq(0).show(); // Show the default tabs content
	
// When the user clics on the tab
tabs.click(function(e)
	{
		var li = $(this),
			activeTab = li.find('a'); // Get the heref attribute value
		tabs.classRemove("active"); // Remove the active class
		li.addClass("active"); // Add to active tab to the selected tab
		tabContent.hide(); // Hidel all other tab content
		
		activeTab.fadeIn(); // Fade tab in
		e.preventDefault();
	}
);
	
	
	
