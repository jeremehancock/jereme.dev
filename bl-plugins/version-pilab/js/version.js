
function getLatestVersion() {

	console.log("[INFO] [PLUGIN VERSION] Getting list of versions of Bludit.");

	$.ajax({
		url: "https://version.bludit.com",
		method: "GET",
		dataType: 'json',
		success: function(json) {
			// Constant BLUDIT_VERSION is defined on variables.js
			if (json.stable.version > BLUDIT_VERSION) {
				$("#current-version").hide();
				$("#new-version").show();
			}
		},
		error: function(json) {
			console.log("[WARN] [PLUGIN VERSION] There is some issue to get the version status.");
		}
	});
}

getLatestVersion();
