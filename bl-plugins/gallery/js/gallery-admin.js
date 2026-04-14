// Gallery plugin admin JavaScript

$(document).ready(function() {

	// =========================================================================
	// Gallery management page (configure-plugin)
	// =========================================================================

	var $dropzone = $("#jsGalleryDropzone");
	var $fileInput = $("#jsGalleryFileInput");
	var $progressBar = $("#jsGalleryProgressBar");

	if ($dropzone.length) {
		// File input change
		$fileInput.on("change", function() {
			if (this.files.length > 0) {
				galleryUploadFiles(this.files);
			}
		});

		// Drag and drop
		$dropzone.on("dragover dragenter", function(e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.find(".gallery-upload-inner").addClass("dragover");
		});

		$dropzone.on("dragleave drop", function(e) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.find(".gallery-upload-inner").removeClass("dragover");
		});

		$dropzone.on("drop", function(e) {
			var files = e.originalEvent.dataTransfer.files;
			if (files.length > 0) {
				galleryUploadFiles(files);
			}
		});
	}

	function galleryUploadFiles(files) {
		var validTypes = ["image/gif", "image/png", "image/jpeg", "image/svg+xml", "image/webp"];
		var formData = new FormData();
		var validCount = 0;

		for (var i = 0; i < files.length; i++) {
			if (validTypes.indexOf(files[i].type) === -1) {
				continue;
			}
			formData.append("images[]", files[i], files[i].name);
			validCount++;
		}

		if (validCount === 0) {
			return;
		}

		formData.append("tokenCSRF", galleryTokenCSRF);
		formData.append("gallery", gallerySlug);

		$progressBar.parent().show();
		$progressBar.css("width", "0%").removeClass("bg-success bg-danger").addClass("bg-primary");

		$.ajax({
			url: galleryWebhookUrl + "upload",
			type: "POST",
			data: formData,
			contentType: false,
			processData: false,
			xhr: function() {
				var xhr = new XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(e) {
					if (e.lengthComputable) {
						var pct = Math.round((e.loaded / e.total) * 100);
						$progressBar.css("width", pct + "%");
					}
				});
				return xhr;
			},
			success: function(response) {
				$progressBar.css("width", "100%");
				if (response.status === 0) {
					$progressBar.removeClass("bg-primary").addClass("bg-success");
					// Reload page to show new images
					setTimeout(function() {
						location.reload();
					}, 500);
				} else {
					$progressBar.removeClass("bg-primary").addClass("bg-danger");
				}
			},
			error: function() {
				$progressBar.css("width", "100%").removeClass("bg-primary").addClass("bg-danger");
			}
		});
	}

	// =========================================================================
	// Gallery select modal (editor integration)
	// =========================================================================

	var $selectModal = $("#jsGallerySelectModal");
	if ($selectModal.length) {
		$selectModal.on("show.bs.modal", function() {
			loadGalleryList();
		});
	}

	function loadGalleryList() {
		var $body = $("#jsGallerySelectBody");
		$body.html('<p class="text-muted">Loading...</p>');

		$.ajax({
			url: galleryWebhookUrl + "list",
			type: "GET",
			dataType: "json",
			success: function(response) {
				if (response.status !== 0 || !response.galleries || response.galleries.length === 0) {
					$body.html('<p class="text-muted">' + galleryNoGalleriesLabel + '</p>');
					return;
				}

				var html = "";
				for (var i = 0; i < response.galleries.length; i++) {
					var g = response.galleries[i];
					html += '<div class="gallery-select-item" data-slug="' + g.slug + '">';
					html += '<div class="d-flex justify-content-between align-items-center">';
					html += '<div>';
					html += '<strong>' + escapeHtml(g.name) + '</strong>';
					html += '<span class="text-muted ml-2">(' + g.imageCount + ' ' + galleryImagesLabel + ')</span>';
					html += '</div>';
					html += '<button type="button" class="btn btn-sm btn-primary js-gallery-insert" data-slug="' + g.slug + '">' + galleryInsertLabel + '</button>';
					html += '</div>';

					if (g.images && g.images.length > 0) {
						html += '<div class="gallery-thumb-grid">';
						var max = Math.min(g.images.length, 8);
						for (var j = 0; j < max; j++) {
							html += '<img src="' + g.images[j].thumbnail + '" alt="">';
						}
						if (g.images.length > 8) {
							html += '<span class="text-muted align-self-center ml-1">+' + (g.images.length - 8) + '</span>';
						}
						html += '</div>';
					}

					html += '</div>';
				}

				$body.html(html);

				// Bind insert buttons
				$body.find(".js-gallery-insert").on("click", function() {
					var slug = $(this).data("slug");
					insertGalleryTag(slug);
					$selectModal.modal("hide");
				});
			},
			error: function() {
				$body.html('<p class="text-danger">Error loading galleries.</p>');
			}
		});
	}

	function insertGalleryTag(slug) {
		var tag = '[gallery:' + slug + ']';

		if (typeof tinymce !== "undefined" && tinymce.activeEditor) {
			// TinyMCE: insert as plain text so it's visible and not stripped
			tinymce.activeEditor.insertContent(tag);
		} else if (typeof easymde !== "undefined" && easymde.codemirror) {
			// EasyMDE (Markdown editor)
			var cm = easymde.codemirror;
			var cursor = cm.getCursor();
			cm.replaceRange('\n' + tag + '\n', cursor);
		} else {
			// Plain textarea fallback
			var $editor = $("#jseditor");
			if ($editor.length) {
				$editor.val($editor.val() + "\n" + tag + "\n");
			}
		}
	}

	function escapeHtml(text) {
		var div = document.createElement("div");
		div.appendChild(document.createTextNode(text));
		return div.innerHTML;
	}
});

// Global function for delete button onclick
function galleryDeleteImage(gallerySlug, filename) {
	if (!confirm("Are you sure you want to delete this image?")) {
		return;
	}

	$.ajax({
		url: galleryWebhookUrl + "delete-image",
		type: "POST",
		data: {
			tokenCSRF: galleryTokenCSRF,
			gallery: gallerySlug,
			filename: filename
		},
		dataType: "json",
		success: function(response) {
			if (response.status === 0) {
				$("#jsGalleryImage-" + CSS.escape(filename)).fadeOut(300, function() {
					$(this).remove();
				});
			}
		}
	});
}
