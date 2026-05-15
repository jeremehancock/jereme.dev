
function compareVersions(a, b) {
	var pa = a.split('.').map(function (n) { return parseInt(n, 10) || 0; });
	var pb = b.split('.').map(function (n) { return parseInt(n, 10) || 0; });
	var len = Math.max(pa.length, pb.length);
	for (var i = 0; i < len; i++) {
		var x = pa[i] || 0, y = pb[i] || 0;
		if (x > y) return 1;
		if (x < y) return -1;
	}
	return 0;
}

function getLatestVersion() {

	console.log("[INFO] [PLUGIN VERSION] Getting list of versions of Bludit.");

	$.ajax({
		url: "https://api.github.com/repos/bludit/bludit/releases/latest",
		method: "GET",
		dataType: 'json',
		success: function(json) {
			// Constant BLUDIT_VERSION is defined on variables.js
			var latest = (json && json.tag_name ? json.tag_name : '').replace(/^v/i, '');
			if (latest && compareVersions(latest, BLUDIT_VERSION) > 0) {
				$("#current-version").hide();
				$("#new-version").show();
			}
		},
		error: function() {
			console.log("[WARN] [PLUGIN VERSION] There is some issue to get the version status.");
		}
	});
}

getLatestVersion();
