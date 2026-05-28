jQuery(document).ready(function ($) {

	// Function to retrieve parameter value from URL
	function getParameterValue(paramName) {
		// Get the URL of the current page
		var currentURL = window.location.href;
		// Create a URL object
		var url = new URL(currentURL);
		// Get the search parameters from the URL
		var searchParams = new URLSearchParams(url.search);
		// Get the value of the specified parameter
		var paramValue = searchParams.get(paramName);

		return paramValue;
	}

	var paramValue = getParameterValue("feedlink");

	if (paramValue) {
		try {
			var parsedUrl = new URL(paramValue);
			if (parsedUrl.protocol === 'http:' || parsedUrl.protocol === 'https:') {
				document.getElementById("feedlink").value = paramValue;
				setTimeout(function () {
					jQuery("#getfeedbutton").trigger("click");
				}, 500);
			}
		} catch (e) {}
	}
});