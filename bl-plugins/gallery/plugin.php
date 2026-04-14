<?php

class pluginGallery extends Plugin
{
	private $galleriesPath;
	private $galleriesHtmlPath;

	public function init()
	{
		$this->dbFields = array(
			'galleries' => '{}'
		);

		// Hide default Save/Cancel buttons, the plugin manages its own forms
		$this->formButtons = false;
	}

	public function install($position = 1)
	{
		parent::install($position);
		$this->galleriesPath = PATH_UPLOADS . 'galleries' . DS;
		if (!Filesystem::directoryExists($this->galleriesPath)) {
			Filesystem::mkdir($this->galleriesPath, true);
		}
	}

	private function galleriesPath()
	{
		return PATH_UPLOADS . 'galleries' . DS;
	}

	private function galleriesHtmlPath()
	{
		return HTML_PATH_UPLOADS . 'galleries/';
	}

	private function getGalleries()
	{
		$raw = $this->getValue('galleries', false);
		$galleries = json_decode($raw, true);
		if (!is_array($galleries)) {
			return array();
		}
		return $galleries;
	}

	private function saveGalleries($galleries)
	{
		$this->db['galleries'] = json_encode($galleries);
		return $this->save();
	}

	private function generateSlug($name)
	{
		$slug = Text::removeSpecialCharacters($name, '-');
		$slug = Text::removeQuotes($slug);
		$slug = Text::removeSpaces($slug, '-');
		$slug = Text::lowercase($slug);
		return $slug;
	}

	// =========================================================================
	// Admin form (Gallery management UI)
	// =========================================================================

	public function form()
	{
		global $L;

		$galleries = $this->getGalleries();
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$gallerySlug = isset($_GET['gallery']) ? $_GET['gallery'] : '';

		$html = '';

		if ($action === 'manage' && !empty($gallerySlug) && isset($galleries[$gallerySlug])) {
			$html .= $this->formManageGallery($gallerySlug, $galleries[$gallerySlug]);
		} else {
			$html .= $this->formListGalleries($galleries);
		}

		return $html;
	}

	private function formListGalleries($galleries)
	{
		global $L;
		global $security;

		$html = '';

		// Create gallery form
		$html .= '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		$html .= '<h5 class="mb-3">' . $L->get('create-gallery') . '</h5>';
		$html .= '<div class="row mb-4 align-items-end">';
		$html .= '<div class="col-sm-4">';
		$html .= '<input type="text" class="form-control" id="jsGalleryName" name="galleryName" placeholder="' . $L->get('gallery-name') . '">';
		$html .= '</div>';
		$html .= '<div class="col-sm-5">';
		$html .= '<input type="text" class="form-control" id="jsGalleryDescription" name="galleryDescription" placeholder="' . $L->get('gallery-description') . '">';
		$html .= '</div>';
		$html .= '<div class="col-sm-3">';
		$html .= '<button type="submit" name="createGallery" value="true" class="btn btn-sm btn-primary">' . $L->get('create-gallery') . '</button>';
		$html .= '</div>';
		$html .= '</div>';

		// Gallery list
		$html .= '<h5 class="mb-3">' . $L->get('galleries') . '</h5>';

		if (empty($galleries)) {
			$html .= '<p class="text-muted">' . $L->get('no-galleries') . '</p>';
		} else {
			$html .= '<div class="table-responsive">';
			$html .= '<table class="table table-striped">';
			$html .= '<thead><tr>';
			$html .= '<th>' . $L->get('gallery-name') . '</th>';
			$html .= '<th>' . $L->get('gallery-description') . '</th>';
			$html .= '<th>' . $L->get('images-count') . '</th>';
			$html .= '<th></th>';
			$html .= '</tr></thead>';
			$html .= '<tbody>';

			foreach ($galleries as $slug => $gallery) {
				$imageCount = isset($gallery['images']) ? count($gallery['images']) : 0;
				$manageUrl = HTML_PATH_ADMIN_ROOT . 'configure-plugin/pluginGallery?action=manage&gallery=' . $slug;
				$html .= '<tr>';
				$html .= '<td><strong>' . Sanitize::html($gallery['name']) . '</strong></td>';
				$html .= '<td>' . Sanitize::html($gallery['description']) . '</td>';
				$html .= '<td>' . $imageCount . ' ' . $L->get('images-count') . '</td>';
				$html .= '<td class="text-right">';
				$html .= '<a href="' . $manageUrl . '" class="btn btn-sm btn-outline-primary mr-1">' . $L->get('manage-images') . '</a>';
				$html .= '<button type="submit" name="deleteGallery" value="' . $slug . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'' . $L->get('delete-gallery-confirm') . '\')">' . $L->get('delete-gallery') . '</button>';
				$html .= '</td>';
				$html .= '</tr>';
			}

			$html .= '</tbody></table></div>';
		}

		return $html;
	}

	private function formManageGallery($slug, $gallery)
	{
		global $L;
		global $security;

		$thumbnailsHtmlPath = $this->galleriesHtmlPath() . $slug . '/thumbnails/';
		$imagesHtmlPath = $this->galleriesHtmlPath() . $slug . '/';
		$backUrl = HTML_PATH_ADMIN_ROOT . 'configure-plugin/pluginGallery';

		$html = '';

		// Back link
		$html .= '<a href="' . $backUrl . '" class="btn btn-sm btn-outline-secondary mb-3">&larr; ' . $L->get('back-to-galleries') . '</a>';

		// Edit gallery info
		$html .= '<div class="row mb-3 align-items-end">';
		$html .= '<div class="col-sm-3">';
		$html .= '<label class="small text-muted">' . $L->get('gallery-name') . '</label>';
		$html .= '<input type="text" class="form-control" name="editGalleryName" value="' . Sanitize::html($gallery['name']) . '" placeholder="' . $L->get('gallery-name') . '">';
		$html .= '</div>';
		$html .= '<div class="col-sm-3">';
		$html .= '<label class="small text-muted">' . $L->get('gallery-slug') . '</label>';
		$html .= '<input type="text" class="form-control" name="editGalleryNewSlug" value="' . Sanitize::html($slug) . '" placeholder="' . $L->get('gallery-slug') . '">';
		$html .= '</div>';
		$html .= '<div class="col-sm-3">';
		$html .= '<label class="small text-muted">' . $L->get('gallery-description') . '</label>';
		$html .= '<input type="text" class="form-control" name="editGalleryDescription" value="' . Sanitize::html($gallery['description']) . '" placeholder="' . $L->get('gallery-description') . '">';
		$html .= '</div>';
		$html .= '<div class="col-sm-3">';
		$html .= '<input type="hidden" name="editGallerySlug" value="' . Sanitize::html($slug) . '">';
		$html .= '<button type="submit" name="editGallery" value="true" class="btn btn-sm btn-primary">' . $L->get('edit-gallery') . '</button>';
		$html .= '</div>';
		$html .= '</div>';

		// Upload area
		$html .= '<div class="gallery-upload-area mb-4" id="jsGalleryDropzone">';
		$html .= '<div class="gallery-upload-inner text-center p-4 border border-dashed rounded">';
		$html .= '<p class="mb-2"><span class="fa fa-cloud-upload fa-2x"></span></p>';
		$html .= '<p class="mb-2">' . $L->get('drop-images-here') . '</p>';
		$html .= '<input type="file" id="jsGalleryFileInput" multiple accept="image/*" class="d-none">';
		$html .= '<button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById(\'jsGalleryFileInput\').click()">' . $L->get('upload-images') . '</button>';
		$html .= '</div>';
		$html .= '<div class="progress mt-2" style="display:none">';
		$html .= '<div id="jsGalleryProgressBar" class="progress-bar bg-primary" role="progressbar" style="width:0%"></div>';
		$html .= '</div>';
		$html .= '</div>';

		// Image grid
		$images = isset($gallery['images']) ? $gallery['images'] : array();
		$html .= '<div class="row" id="jsGalleryImageGrid">';

		if (empty($images)) {
			$html .= '<div class="col-12"><p class="text-muted">' . $L->get('no-images') . '</p></div>';
		} else {
			foreach ($images as $image) {
				$thumbUrl = $thumbnailsHtmlPath . $image;
				$fullUrl = $imagesHtmlPath . $image;
				$html .= '<div class="col-6 col-md-3 mb-3" id="jsGalleryImage-' . Sanitize::html($image) . '">';
				$html .= '<div class="card">';
				$html .= '<img src="' . $thumbUrl . '" class="card-img-top" alt="' . Sanitize::html($image) . '">';
				$html .= '<div class="card-body p-2 text-center">';
				$html .= '<small class="text-muted d-block text-truncate">' . Sanitize::html($image) . '</small>';
				$html .= '<button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="galleryDeleteImage(\'' . $slug . '\', \'' . Sanitize::html($image) . '\')">' . $L->get('delete-image') . '</button>';
				$html .= '</div></div></div>';
			}
		}

		$html .= '</div>';

		// Pass data to JS
		$html .= '<script>';
		$html .= 'var gallerySlug = "' . $slug . '";';
		$html .= 'var galleryWebhookUrl = "' . HTML_PATH_ROOT . 'gallery/";';
		$html .= 'var galleryTokenCSRF = "' . $security->getTokenCSRF() . '";';
		$html .= '</script>';

		return $html;
	}

	// =========================================================================
	// POST handler
	// =========================================================================

	public function post()
	{
		$galleries = $this->getGalleries();

		// Create gallery
		if (isset($_POST['createGallery'])) {
			$name = isset($_POST['galleryName']) ? trim($_POST['galleryName']) : '';
			$description = isset($_POST['galleryDescription']) ? trim($_POST['galleryDescription']) : '';

			if (!empty($name)) {
				$slug = $this->generateSlug($name);
				if (!isset($galleries[$slug])) {
					$galleryDir = $this->galleriesPath() . $slug . DS;
					$thumbDir = $galleryDir . 'thumbnails' . DS;
					Filesystem::mkdir($galleryDir, true);
					Filesystem::mkdir($thumbDir, true);

					$galleries[$slug] = array(
						'name' => $name,
						'description' => $description,
						'date' => Date::current('Y-m-d'),
						'images' => array()
					);
					$this->saveGalleries($galleries);
				}
			}
		}

		// Edit gallery
		if (isset($_POST['editGallery'])) {
			$oldSlug = isset($_POST['editGallerySlug']) ? $_POST['editGallerySlug'] : '';
			$name = isset($_POST['editGalleryName']) ? trim($_POST['editGalleryName']) : '';
			$description = isset($_POST['editGalleryDescription']) ? trim($_POST['editGalleryDescription']) : '';
			$newSlug = isset($_POST['editGalleryNewSlug']) ? trim($_POST['editGalleryNewSlug']) : '';
			$newSlug = $this->generateSlug($newSlug);

			if (!empty($oldSlug) && isset($galleries[$oldSlug]) && !empty($name) && !empty($newSlug)) {
				$galleryData = $galleries[$oldSlug];
				$galleryData['name'] = $name;
				$galleryData['description'] = $description;

				if ($newSlug !== $oldSlug) {
					// Slug changed — rename directory and update DB key
					if (!isset($galleries[$newSlug])) {
						$oldDir = $this->galleriesPath() . $oldSlug;
						$newDir = $this->galleriesPath() . $newSlug;
						if (Filesystem::directoryExists($oldDir)) {
							Filesystem::mv($oldDir, $newDir);
						}
						unset($galleries[$oldSlug]);
						$galleries[$newSlug] = $galleryData;
					}
				} else {
					$galleries[$oldSlug] = $galleryData;
				}

				$this->saveGalleries($galleries);
			}
		}

		// Delete gallery
		if (isset($_POST['deleteGallery'])) {
			$slug = $_POST['deleteGallery'];
			if (isset($galleries[$slug])) {
				$galleryDir = $this->galleriesPath() . $slug;
				if (Filesystem::directoryExists($galleryDir)) {
					Filesystem::deleteRecursive($galleryDir);
				}
				unset($galleries[$slug]);
				$this->saveGalleries($galleries);
			}
		}
	}

	// =========================================================================
	// Webhook AJAX endpoints (runs via beforeAll on site side)
	// =========================================================================

	public function beforeAll()
	{
		$URI = $this->webhook('gallery', $returnsAfterURI = true, $fixed = false);
		if ($URI === false) {
			return false;
		}

		// Check user is logged in
		$login = new Login();
		if (!$login->isLogged()) {
			$this->webhookResponse(1, 'Unauthorized');
		}

		// Check user role
		$username = Session::get('username');
		try {
			$user = new User($username);
			if (!in_array($user->role(), array('admin', 'editor'))) {
				$this->webhookResponse(1, 'Access denied');
			}
		} catch (Exception $e) {
			$this->webhookResponse(1, 'Access denied');
		}

		header('Content-Type: application/json');
		$method = $_SERVER['REQUEST_METHOD'];
		$parts = explode('/', trim($URI, '/'));
		$endpoint = isset($parts[0]) ? $parts[0] : '';

		if ($method === 'POST' && $endpoint === 'upload') {
			$this->webhookUpload();
		} elseif ($method === 'POST' && $endpoint === 'delete-image') {
			$this->webhookDeleteImage();
		} elseif ($method === 'GET' && $endpoint === 'list') {
			$this->webhookList();
		} else {
			$this->webhookResponse(1, 'Invalid endpoint');
		}
	}

	private function webhookResponse($status, $message, $data = array())
	{
		$default = array('status' => $status, 'message' => $message);
		$output = array_merge($default, $data);
		header('Content-Type: application/json');
		exit(json_encode($output));
	}

	private function webhookUpload()
	{
		global $security;

		// CSRF validation
		$token = isset($_POST['tokenCSRF']) ? $_POST['tokenCSRF'] : '';
		if (!$security->validateTokenCSRF($token)) {
			$this->webhookResponse(1, 'Invalid CSRF token');
		}

		$gallerySlug = isset($_POST['gallery']) ? $_POST['gallery'] : '';

		// Validate slug: strict whitelist (matches generateSlug() output)
		if (empty($gallerySlug) || !preg_match('/^[a-z0-9-]+$/', $gallerySlug)) {
			$this->webhookResponse(1, 'Invalid gallery');
		}

		$galleries = $this->getGalleries();
		if (!isset($galleries[$gallerySlug])) {
			$this->webhookResponse(1, 'Gallery not found');
		}

		$galleryDir = $this->galleriesPath() . $gallerySlug . DS;
		$thumbDir = $galleryDir . 'thumbnails' . DS;

		if (!Filesystem::directoryExists($thumbDir)) {
			Filesystem::mkdir($thumbDir, true);
		}

		if (empty($_FILES['images'])) {
			$this->webhookResponse(1, 'No files uploaded');
		}

		// Ensure temp directory exists
		if (!Filesystem::directoryExists(PATH_TMP)) {
			Filesystem::mkdir(PATH_TMP, true);
		}

		$uploaded = array();
		$errors = array();

		foreach ($_FILES['images']['name'] as $i => $filename) {
			// Check for upload errors
			if ($_FILES['images']['error'][$i] != 0) {
				$errors[] = $filename . ': upload error';
				continue;
			}

			// Validate extension
			$fileExtension = Filesystem::extension($filename);
			$fileExtension = Text::lowercase($fileExtension);
			if (!in_array($fileExtension, $GLOBALS['ALLOWED_IMG_EXTENSION'])) {
				$errors[] = $filename . ': unsupported file type';
				continue;
			}

			// Validate MIME type (strict: reject if detection fails)
			$fileMimeType = Filesystem::mimeType($_FILES['images']['tmp_name'][$i]);
			if ($fileMimeType === false || !in_array($fileMimeType, $GLOBALS['ALLOWED_IMG_MIMETYPES'])) {
				$errors[] = $filename . ': invalid MIME type';
				continue;
			}

			// Check path traversal on filename
			$filename = urldecode($filename);
			if (Text::stringContains($filename, DS, false)) {
				$errors[] = $filename . ': invalid filename';
				continue;
			}

			// Move to temp (transformImage handles sanitization)
			$tmpFile = PATH_TMP . basename($filename);
			$moved = Filesystem::mv($_FILES['images']['tmp_name'][$i], $tmpFile);
			if (!$moved) {
				$errors[] = $filename . ': failed to move to temp';
				continue;
			}

			// Transform image and generate thumbnail
			$image = transformImage($tmpFile, $galleryDir, $thumbDir);
			if ($image) {
				chmod($image, 0644);
				$finalFilename = Filesystem::filename($image);
				$uploaded[] = $finalFilename;
			} else {
				// Clean up temp file on failure
				if (file_exists($tmpFile)) {
					Filesystem::rmfile($tmpFile);
				}
				$errors[] = $filename . ': processing failed';
			}
		}

		// Update gallery image list in DB
		if (!empty($uploaded)) {
			$galleries = $this->getGalleries();
			if (isset($galleries[$gallerySlug])) {
				$currentImages = isset($galleries[$gallerySlug]['images']) ? $galleries[$gallerySlug]['images'] : array();
				$galleries[$gallerySlug]['images'] = array_values(array_unique(array_merge($currentImages, $uploaded)));
				$this->saveGalleries($galleries);
			}
		}

		$this->webhookResponse(0, 'Upload complete', array(
			'images' => $uploaded,
			'errors' => $errors
		));
	}

	private function webhookDeleteImage()
	{
		global $security;

		// CSRF validation
		$token = isset($_POST['tokenCSRF']) ? $_POST['tokenCSRF'] : '';
		if (!$security->validateTokenCSRF($token)) {
			$this->webhookResponse(1, 'Invalid CSRF token');
		}

		$gallerySlug = isset($_POST['gallery']) ? $_POST['gallery'] : '';
		$filename = isset($_POST['filename']) ? $_POST['filename'] : '';

		if (empty($gallerySlug) || empty($filename)) {
			$this->webhookResponse(1, 'Missing parameters');
		}

		// Strict whitelist validation: slugs are [a-z0-9-]; filenames are
		// generated by uniqid()+ext, so word chars + dot are sufficient.
		if (!preg_match('/^[a-z0-9-]+$/', $gallerySlug) || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
			$this->webhookResponse(1, 'Invalid parameters');
		}

		$galleries = $this->getGalleries();
		if (!isset($galleries[$gallerySlug])) {
			$this->webhookResponse(1, 'Gallery not found');
		}

		$galleryDir = $this->galleriesPath() . $gallerySlug . DS;
		$imageFile = $galleryDir . $filename;
		$thumbFile = $galleryDir . 'thumbnails' . DS . $filename;

		// Delete image and thumbnail
		if (file_exists($imageFile)) {
			Filesystem::rmfile($imageFile);
		}
		if (file_exists($thumbFile)) {
			Filesystem::rmfile($thumbFile);
		}

		// Update DB
		$images = $galleries[$gallerySlug]['images'];
		$images = array_values(array_diff($images, array($filename)));
		$galleries[$gallerySlug]['images'] = $images;
		$this->saveGalleries($galleries);

		$this->webhookResponse(0, 'Image deleted', array(
			'filename' => $filename
		));
	}

	private function webhookList()
	{
		$galleries = $this->getGalleries();
		$result = array();

		foreach ($galleries as $slug => $gallery) {
			$images = isset($gallery['images']) ? $gallery['images'] : array();
			$thumbnails = array();
			foreach ($images as $image) {
				$thumbnails[] = array(
					'filename' => $image,
					'thumbnail' => $this->galleriesHtmlPath() . $slug . '/thumbnails/' . $image,
					'full' => $this->galleriesHtmlPath() . $slug . '/' . $image
				);
			}
			$result[] = array(
				'name' => $gallery['name'],
				'slug' => $slug,
				'description' => $gallery['description'],
				'imageCount' => count($images),
				'images' => $thumbnails
			);
		}

		$this->webhookResponse(0, 'OK', array('galleries' => $result));
	}

	// =========================================================================
	// Editor toolbar hook
	// =========================================================================

	public function editorToolbar()
	{
		global $L;

		$html = '<button type="button" class="btn btn-light" data-toggle="modal" data-target="#jsGallerySelectModal">';
		$html .= '<span class="fa fa-th"></span> ' . $L->get('galleries');
		$html .= '</button>';

		return $html;
	}

	// =========================================================================
	// Frontend rendering (site side)
	// =========================================================================

	private $hasGalleries = false;

	public function beforeSiteLoad()
	{
		if ($GLOBALS['WHERE_AM_I'] == 'page') {
			$content = $this->parseGalleries($GLOBALS['page']);
			if ($content !== false) {
				$GLOBALS['page']->setField('content', $content);
			}
		} else {
			foreach ($GLOBALS['content'] as $key => $page) {
				$content = $this->parseGalleries($GLOBALS['content'][$key]);
				if ($content !== false) {
					$GLOBALS['content'][$key]->setField('content', $content);
				}
			}
		}
	}

	private function parseGalleries($page)
	{
		$content = $page->contentRaw();
		if (strpos($content, '[gallery:') === false) {
			return false;
		}

		$this->hasGalleries = true;
		$galleries = $this->getGalleries();

		// Replace gallery tags BEFORE Markdown parsing to avoid nesting issues
		$content = preg_replace_callback(
			'/\[gallery:([\w-]+)\]/',
			function ($matches) use ($galleries) {
				$slug = $matches[1];
				if (isset($galleries[$slug])) {
					return $this->renderGalleryHtml($slug, $galleries[$slug]);
				}
				return '';
			},
			$content
		);

		// Parse Markdown (same as custom-fields-parser pattern)
		if (MARKDOWN_PARSER) {
			$parsedown = new Parsedown();
			$content = $parsedown->text($content);
		}

		// Convert relative image URLs to absolute
		if (IMAGE_RELATIVE_TO_ABSOLUTE) {
			$domain = IMAGE_RESTRICT ? DOMAIN_UPLOADS_PAGES . $page->uuid() . '/' : DOMAIN_UPLOADS;
			$content = Text::imgRel2Abs($content, $domain);
		}

		return $content;
	}

	private function renderGalleryHtml($slug, $gallery)
	{
		$images = isset($gallery['images']) ? $gallery['images'] : array();
		if (empty($images)) {
			return '';
		}

		$basePath = $this->galleriesHtmlPath() . $slug . '/';
		$thumbPath = $basePath . 'thumbnails/';

		$html = '<div class="bludit-gallery" data-gallery="' . $slug . '">';
		$html .= '<div class="bludit-gallery-grid">';

		foreach ($images as $image) {
			$html .= '<a href="' . $basePath . $image . '" class="bludit-gallery-item" data-gallery-group="' . $slug . '">';
			$html .= '<img src="' . $thumbPath . $image . '" alt="' . Sanitize::html($image) . '" loading="lazy">';
			$html .= '</a>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public function siteHead()
	{
		return $this->includeCSS('gallery.css');
	}

	public function siteBodyEnd()
	{
		if (!$this->hasGalleries) {
			return false;
		}
		return $this->includeJS('gallery.js');
	}

	// =========================================================================
	// Admin hooks
	// =========================================================================

	public function adminHead()
	{
		if (!in_array($GLOBALS['ADMIN_CONTROLLER'], array('configure-plugin', 'new-content', 'edit-content'))) {
			return false;
		}

		$html = $this->includeCSS('gallery-admin.css');
		$html .= $this->includeJS('gallery-admin.js');
		return $html;
	}

	public function adminSidebar()
	{
		global $L;
		$html = '<a class="nav-link" href="' . HTML_PATH_ADMIN_ROOT . 'configure-plugin/pluginGallery">';
		$html .= '<span class="fa fa-th"></span>' . $L->get('galleries');
		$html .= '</a>';
		return $html;
	}

	public function adminBodyEnd()
	{
		if (!in_array($GLOBALS['ADMIN_CONTROLLER'], array('new-content', 'edit-content'))) {
			return false;
		}

		global $L;
		global $security;

		$html = '';

		// Gallery selection modal for the editor
		$html .= '<div id="jsGallerySelectModal" class="modal" tabindex="-1" role="dialog">';
		$html .= '<div class="modal-dialog modal-lg" role="document">';
		$html .= '<div class="modal-content">';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title">' . $L->get('select-gallery') . '</h5>';
		$html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
		$html .= '</div>';
		$html .= '<div class="modal-body" id="jsGallerySelectBody">';
		$html .= '<p class="text-muted">' . $L->get('Loading...') . '</p>';
		$html .= '</div>';
		$html .= '</div></div></div>';

		// JS config for editor integration
		$html .= '<script>';
		$html .= 'var galleryWebhookUrl = "' . HTML_PATH_ROOT . 'gallery/";';
		$html .= 'var galleryTokenCSRF = "' . $security->getTokenCSRF() . '";';
		$html .= 'var galleryInsertLabel = "' . $L->get('insert-gallery') . '";';
		$html .= 'var galleryNoGalleriesLabel = "' . $L->get('no-galleries') . '";';
		$html .= 'var galleryImagesLabel = "' . $L->get('images-count') . '";';
		$html .= '</script>';

		return $html;
	}
}
