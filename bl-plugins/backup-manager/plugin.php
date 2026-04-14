<?php
/*
	BACKUP MANAGER
	This plugin is delivery with BLUDIT PRO.
	This plugin is NOT FREE.
	This plugin is NOT Open Source.
	You can NOT distribute this plugin.
	You can NOT modify this plugin.

	Copyright 2026
	Author: Diego Najar - dignajar@gmail.com
*/
class pluginBackupManager extends Plugin {

	private $zipAvailable = false;

	public function init()
	{
		$this->dbFields = array(
			'maxBackups' => 10,
			'backupPassword' => ''
		);

		$this->zipAvailable = extension_loaded('zip');
	}

	public function install($position = 1)
	{
		parent::install($position);
		$workspace = $this->workspace();
		if (!Filesystem::directoryExists($workspace)) {
			Filesystem::mkdir($workspace, true);
		}
		return true;
	}

	public function adminSidebar()
	{
		return '<a class="nav-link" href="' . HTML_PATH_ADMIN_ROOT . 'configure-plugin/' . $this->className() . '"><span class="fa fa-archive"></span> ' . $this->name() . '</a>';
	}

	private function copyContentExcludingBackups($source, $destination)
	{
		$source = rtrim($source, DS);
		$destination = rtrim($destination, DS);

		if (!Filesystem::directoryExists($source)) {
			return false;
		}

		if (!Filesystem::directoryExists($destination)) {
			if (!Filesystem::mkdir($destination, true)) {
				return false;
			}
		}

		$excludePath = $this->workspace();

		foreach ($iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		) as $item) {
			$currentPath = $item->getPathName();

			if (strpos($currentPath, $excludePath) === 0) {
				continue;
			}

			$relativePath = substr($currentPath, strlen($source) + 1);

			if ($item->isDir()) {
				@mkdir($destination . DS . $relativePath);
			} else {
				copy($item, $destination . DS . $relativePath);
			}
		}

		return true;
	}

	private function createEncryptedZip($sourcePath, $zipPath, $password)
	{
		if (!class_exists('ZipArchive')) {
			return false;
		}

		// ZipArchive encryption support depends on libzip build.
		if (!method_exists('ZipArchive', 'setEncryptionName') || !defined('ZipArchive::EM_AES_256')) {
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
			return false;
		}

		$zip->setPassword($password);

		$sourcePath = rtrim($sourcePath, DS);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$filePath = $item->getPathName();
			$relativePath = substr($filePath, strlen($sourcePath) + 1);

			if ($item->isDir()) {
				$zip->addEmptyDir($relativePath);
			} else {
				$zip->addFile($filePath, $relativePath);
				$zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
			}
		}

		$zip->close();
		return true;
	}

	private function extractEncryptedZip($zipPath, $destination, $password)
	{
		if (!class_exists('ZipArchive')) {
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipPath) !== true) {
			return false;
		}

		$zip->setPassword($password);

		if (!$zip->extractTo($destination)) {
			$zip->close();
			return false;
		}

		$zip->close();
		return true;
	}

	public function post()
	{
		global $syslog, $language;

		// Default Save button (top-right) uses the admin wrapper form.
		// Backup actions (create/restore/delete) are submitted via action buttons.
		$isSave = isset($_POST['save']);
		$hasAction = isset($_POST['backup-action']);
		if ($isSave || !$hasAction) {
			// Behave like Plugin::post(), but preserve the current password if left empty.
			$args = $_POST;
			if (isset($args['backupPassword']) && $args['backupPassword'] === '') {
				unset($args['backupPassword']);
			}

			foreach ($this->dbFields as $field => $defaultValue) {
				if (isset($args[$field])) {
					$finalValue = Sanitize::html($args[$field]);
					if ($finalValue === 'false') {
						$finalValue = false;
					} elseif ($finalValue === 'true') {
						$finalValue = true;
					}
					settype($finalValue, gettype($defaultValue));
					$this->db[$field] = $finalValue;
				}
			}

			return $this->save();
		}

		$actionRaw = (string)$_POST['backup-action'];
		$action = $actionRaw;
		$backupId = '';
		if (strpos($actionRaw, ':') !== false) {
			$parts = explode(':', $actionRaw, 2);
			$action = $parts[0];
			$backupId = $parts[1];
		}

		if ($action === 'create') {
			$password = $this->getValue('backupPassword');
			if (empty($password)) {
				Alert::set($language->get('password-required'), ALERT_STATUS_FAIL);
				return false;
			}

			$backupId = $this->createBackup();
			if ($backupId !== false) {
				$this->removeOldBackups();
				$syslog->add(array(
					'dictionaryKey' => 'backup-created',
					'notes' => 'Backup ID: ' . $backupId
				));

				Alert::set($language->get('backup-created'), ALERT_STATUS_OK);
				return true;
			} else {
				Alert::set($language->get('backup-failed'), ALERT_STATUS_FAIL);
				return false;
			}
		}
		elseif ($action === 'restore') {
			if (empty($backupId) && isset($_POST['backup-id'])) {
				$backupId = Sanitize::html($_POST['backup-id']);
			}

			if (empty($backupId)) {
				Alert::set($language->get('restore-failed'), ALERT_STATUS_FAIL);
				return false;
			}

			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $backupId)) {
				Alert::set($language->get('restore-failed'), ALERT_STATUS_FAIL);
				return false;
			}

			if ($this->restoreBackup($backupId)) {
				$syslog->add(array(
					'dictionaryKey' => 'backup-restored',
					'notes' => 'Restored from backup ID: ' . $backupId
				));

				Alert::set($language->get('backup-restored'), ALERT_STATUS_OK);
				return true;
			} else {
				Alert::set($language->get('restore-failed'), ALERT_STATUS_FAIL);
				return false;
			}
		}
		elseif ($action === 'delete') {
			if (empty($backupId) && isset($_POST['backup-id'])) {
				$backupId = Sanitize::html($_POST['backup-id']);
			}

			if (empty($backupId)) {
				Alert::set($language->get('delete-failed'), ALERT_STATUS_FAIL);
				return false;
			}

			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $backupId)) {
				Alert::set($language->get('delete-failed'), ALERT_STATUS_FAIL);
				return false;
			}

			if ($this->deleteBackup($backupId)) {
				$syslog->add(array(
					'dictionaryKey' => 'backup-deleted',
					'notes' => 'Deleted backup ID: ' . $backupId
				));

				Alert::set($language->get('backup-deleted'), ALERT_STATUS_OK);
				return true;
			} else {
				Alert::set($language->get('delete-failed'), ALERT_STATUS_FAIL);
				return false;
			}
		}

		return false;
	}

	public function form()
	{
		global $language;

		$html = '';

		$html .= '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		if (!$this->zipAvailable) {
			$html .= '<div class="alert alert-warning" role="alert">';
			$html .= '<i class="fa fa-exclamation-triangle"></i> ' . $language->get('zip-unavailable');
			$html .= '</div>';
		}

		$html .= '<div class="card mb-4">';
		$html .= '<div class="card-header"><i class="fa fa-cog"></i> ' . $language->get('settings') . '</div>';
		$html .= '<div class="card-body">';

		$html .= '<div class="form-group">';
		$html .= '<label for="backupPassword">' . $language->get('backup-password');
		if (empty($this->getValue('backupPassword'))) {
			$html .= ' <span class="text-danger">*</span>';
		}
		$html .= '</label>';
		$placeholder = empty($this->getValue('backupPassword')) ? $language->get('backup-password-placeholder') : '••••••••';
		$required = empty($this->getValue('backupPassword')) ? ' required' : '';
		$html .= '<input type="password" class="form-control" id="backupPassword" name="backupPassword" value="" placeholder="' . $placeholder . '"' . $required . '>';
		if (empty($this->getValue('backupPassword'))) {
			$html .= '<small class="form-text text-danger"><i class="fa fa-exclamation-triangle"></i> ' . $language->get('backup-password-warning') . '</small>';
		} else {
			$hint = $language->get('leave-empty-to-keep-current');
			if ($hint === 'leave-empty-to-keep-current') {
				$hint = $language->get('Leave empty to keep the current password');
			}
			$html .= '<small class="form-text text-muted">' . $hint . '</small>';
		}
		$html .= '</div>';

		$html .= '<div class="form-group">';
		$html .= '<label for="maxBackups">' . $language->get('max-backups') . '</label>';
		$html .= '<input type="number" class="form-control" id="maxBackups" name="maxBackups" value="' . $this->getValue('maxBackups') . '" min="1" max="100">';
		$html .= '<small class="form-text text-muted">' . $language->get('max-backups-help') . '</small>';
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		$password = $this->getValue('backupPassword');
		if (!empty($password)) {
			$html .= '<div class="mb-4">';
			$html .= '<button type="submit" class="btn btn-primary btn-lg" name="backup-action" value="create" formnovalidate>';
			$html .= '<i class="fa fa-download"></i> ' . $language->get('create-backup');
			$html .= '</button>';
			$html .= '</div>';
		} else {
			$html .= '<div class="alert alert-info mb-4" role="alert">';
			$html .= '<i class="fa fa-info-circle"></i> ' . $language->get('set-password-first');
			$html .= '</div>';
		}

		$html .= '<h4 class="mt-4 mb-3">' . $language->get('backup-list') . '</h4>';

		$backups = $this->getBackupList();
		// Safe JS string literals inside HTML attributes
		$confirmRestore = htmlspecialchars(json_encode($language->get('confirm-restore')), ENT_QUOTES, CHARSET);
		$confirmDelete = htmlspecialchars(json_encode($language->get('confirm-delete')), ENT_QUOTES, CHARSET);

		if (empty($backups)) {
			$html .= '<div class="alert alert-info" role="alert">';
			$html .= $language->get('no-backups');
			$html .= '</div>';
		} else {
			$html .= '<div class="table-responsive">';
			$html .= '<table class="table table-striped">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th>' . $language->get('date') . '</th>';
			$html .= '<th>' . $language->get('type') . '</th>';
			$html .= '<th>' . $language->get('size') . '</th>';
			$html .= '<th class="text-center">' . $language->get('actions') . '</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';

			foreach ($backups as $backup) {
				$html .= '<tr>';

				$backupIdRaw = $backup['id'];
				$backupId = Sanitize::html($backupIdRaw);

				$html .= '<td>';
				$html .= '<strong>' . $backup['date'] . '</strong><br>';
				$html .= '<small class="text-muted">' . $backupId . '</small>';
				$html .= '</td>';

				$html .= '<td>';
				if ($backup['isZip']) {
					$html .= '<span class="badge badge-success">' . $language->get('compressed') . '</span>';
					if (isset($backup['encrypted']) && $backup['encrypted']) {
						$html .= ' <span class="badge badge-warning">' . $language->get('encrypted') . '</span>';
					}
				} else {
					$html .= '<span class="badge badge-secondary">' . $language->get('folder') . '</span>';
				}

				if (strpos($backupIdRaw, 'emergency-') === 0) {
					$html .= '<br><small class="text-warning">' . $language->get('emergency-backup') . '</small>';
				} else {
					$html .= '<br><small class="text-muted">' . $language->get('manual-backup') . '</small>';
				}
				$html .= '</td>';

				$html .= '<td>' . $backup['size'] . '</td>';

				$html .= '<td class="text-center">';

				$html .= '<button type="submit" class="btn btn-sm btn-primary" title="' . $language->get('restore') . '" name="backup-action" value="restore:' . $backupId . '" formnovalidate onclick="return confirm(' . $confirmRestore . ');">';
				$html .= '<i class="fa fa-upload"></i> ' . $language->get('restore');
				$html .= '</button>';
				$html .= ' ';

				$html .= '<button type="submit" class="btn btn-sm btn-danger" title="' . $language->get('delete') . '" name="backup-action" value="delete:' . $backupId . '" formnovalidate onclick="return confirm(' . $confirmDelete . ');">';
				$html .= '<i class="fa fa-trash"></i> ' . $language->get('delete');
				$html .= '</button>';

				$html .= '</td>';
				$html .= '</tr>';
			}

			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div>';
		}

		return $html;
	}

	private function createBackup($isEmergency = false)
	{
		try {
			$timestamp = date('YmdHis');
			$backupId = $isEmergency ? 'emergency-' . $timestamp : 'backup-' . $timestamp;

			$workspace = $this->workspace();
			$backupPath = $workspace . $backupId . DS;

			if (!Filesystem::mkdir($backupPath, true)) {
				Log::set(__METHOD__ . LOG_SEP . 'Failed to create backup directory: ' . $backupPath);
				return false;
			}

			if (!$this->copyContentExcludingBackups(PATH_CONTENT, $backupPath . 'bl-content')) {
				Log::set(__METHOD__ . LOG_SEP . 'Failed to copy content to backup');
				Filesystem::deleteRecursive($backupPath);
				return false;
			}

			$metadata = array(
				'id' => $backupId,
				'timestamp' => $timestamp,
				'date' => date('Y-m-d H:i:s'),
				'isEmergency' => $isEmergency,
				'compressed' => false,
				'encrypted' => false
			);

			if ($this->zipAvailable) {
				$zipPath = $workspace . $backupId . '.zip';
				$password = $this->getValue('backupPassword');

				if (!empty($password)) {
					if ($this->createEncryptedZip($backupPath, $zipPath, $password)) {
						Filesystem::deleteRecursive($backupPath);
						$metadata['compressed'] = true;
						$metadata['encrypted'] = true;
						Log::set(__METHOD__ . LOG_SEP . 'Backup created with AES-256 encryption: ' . $backupId);
					} else {
						Log::set(__METHOD__ . LOG_SEP . 'Encrypted ZIP failed, using folder backup: ' . $backupId);
						$metadataPath = $backupPath . 'metadata.json';
						file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
						return $backupId;
					}
				} else {
					if (Filesystem::zip($backupPath, $zipPath)) {
						Filesystem::deleteRecursive($backupPath);
						$metadata['compressed'] = true;
						Log::set(__METHOD__ . LOG_SEP . 'Backup created and compressed: ' . $backupId);
					} else {
						Log::set(__METHOD__ . LOG_SEP . 'ZIP compression failed, using folder backup: ' . $backupId);
						$metadataPath = $backupPath . 'metadata.json';
						file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
						return $backupId;
					}
				}

				$metadataPath = $workspace . $backupId . '.json';
				file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
			} else {
				$metadataPath = $backupPath . 'metadata.json';
				file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

				Log::set(__METHOD__ . LOG_SEP . 'Backup created as folder: ' . $backupId);
			}

			return $backupId;
		} catch (Exception $e) {
			Log::set(__METHOD__ . LOG_SEP . 'Exception during backup: ' . $e->getMessage());
			return false;
		}
	}

	private function restoreBackup($backupId)
	{
		try {
			$workspace = $this->workspace();
			$backupPath = $workspace . $backupId . DS;
			$zipPath = $workspace . $backupId . '.zip';

			$isZip = file_exists($zipPath);
			$isFolder = Filesystem::directoryExists($backupPath);

			if (!$isZip && !$isFolder) {
				Log::set(__METHOD__ . LOG_SEP . 'Backup not found: ' . $backupId);
				return false;
			}

			$emergencyBackupId = $this->createBackup(true);
			if ($emergencyBackupId === false) {
				Log::set(__METHOD__ . LOG_SEP . 'Failed to create emergency backup before restore');
			}

			$backupManagerWorkspace = $this->workspace();
			$tempBackupManager = PATH_TMP . 'backup-manager-preserve-' . time() . DS;
			if (Filesystem::directoryExists($backupManagerWorkspace)) {
				Filesystem::mkdir($tempBackupManager, true);
				Filesystem::copyRecursive($backupManagerWorkspace, $tempBackupManager, false);
				Log::set(__METHOD__ . LOG_SEP . 'Preserved backup-manager workspace to: ' . $tempBackupManager);
			}

			$tempPath = PATH_TMP . 'restore-' . time() . DS;
			Filesystem::mkdir($tempPath, true);

			if ($isZip) {
				$metadataPath = $workspace . $backupId . '.json';
				$isEncrypted = false;

				if (file_exists($metadataPath)) {
					$metadata = json_decode(file_get_contents($metadataPath), true);
					$isEncrypted = isset($metadata['encrypted']) && $metadata['encrypted'];
				}

				if ($isEncrypted) {
					$password = $this->getValue('backupPassword');
					if (!$this->extractEncryptedZip($zipPath, $tempPath, $password)) {
						Log::set(__METHOD__ . LOG_SEP . 'Failed to extract encrypted ZIP backup');
						Filesystem::deleteRecursive($tempPath);
						return false;
					}
				} else {
					if (!Filesystem::unzip($zipPath, $tempPath)) {
						Log::set(__METHOD__ . LOG_SEP . 'Failed to extract ZIP backup');
						Filesystem::deleteRecursive($tempPath);
						return false;
					}
				}
			} else {
				if (!Filesystem::copyRecursive($backupPath . 'bl-content', $tempPath . 'bl-content', $skipDirectory = false)) {
					Log::set(__METHOD__ . LOG_SEP . 'Failed to copy folder backup');
					Filesystem::deleteRecursive($tempPath);
					return false;
				}
			}

			$contentDirs = Filesystem::listDirectories(PATH_CONTENT);
			foreach ($contentDirs as $dir) {
				$dirName = basename($dir);

				if ($dirName === 'tmp') {
					continue;
				}

				Filesystem::deleteRecursive($dir);
			}

			$restoredContent = $tempPath . 'bl-content' . DS;
			if (Filesystem::directoryExists($restoredContent)) {
				$restoreDirs = Filesystem::listDirectories($restoredContent);
				foreach ($restoreDirs as $dir) {
					$dirName = basename($dir);
					$targetPath = PATH_CONTENT . $dirName;

					if ($dirName === 'tmp') {
						continue;
					}

					if (!Filesystem::mv($dir, $targetPath)) {
						Log::set(__METHOD__ . LOG_SEP . 'Failed to move directory: ' . $dirName);
					}
				}

				$files = Filesystem::listFiles($restoredContent);
				foreach ($files as $file) {
					$fileName = basename($file);
					$targetPath = PATH_CONTENT . $fileName;
					Filesystem::mv($file, $targetPath);
				}
			}

			Filesystem::deleteRecursive($tempPath);

			if (Filesystem::directoryExists($tempBackupManager)) {
				if (!Filesystem::directoryExists(PATH_WORKSPACES)) {
					Filesystem::mkdir(PATH_WORKSPACES, true);
				}
				if (Filesystem::directoryExists($backupManagerWorkspace)) {
					Filesystem::deleteRecursive($backupManagerWorkspace);
				}
				Filesystem::copyRecursive($tempBackupManager, $backupManagerWorkspace, false);
				Filesystem::deleteRecursive($tempBackupManager);
				Log::set(__METHOD__ . LOG_SEP . 'Restored backup-manager workspace from temp');
			}

			Log::set(__METHOD__ . LOG_SEP . 'Backup restored successfully: ' . $backupId);
			return true;
		} catch (Exception $e) {
			Log::set(__METHOD__ . LOG_SEP . 'Exception during restore: ' . $e->getMessage());
			return false;
		}
	}

	private function deleteBackup($backupId)
	{
		try {
			$workspace = $this->workspace();
			$backupPath = $workspace . $backupId . DS;
			$zipPath = $workspace . $backupId . '.zip';
			$metadataPath = $workspace . $backupId . '.json';

			if (file_exists($zipPath)) {
				Filesystem::rmfile($zipPath);
			}

			if (file_exists($metadataPath)) {
				Filesystem::rmfile($metadataPath);
			}

			if (Filesystem::directoryExists($backupPath)) {
				Filesystem::deleteRecursive($backupPath);
			}

			Log::set(__METHOD__ . LOG_SEP . 'Backup deleted: ' . $backupId);
			return true;
		} catch (Exception $e) {
			Log::set(__METHOD__ . LOG_SEP . 'Exception during delete: ' . $e->getMessage());
			return false;
		}
	}

	private function removeOldBackups()
	{
		try {
			$maxBackups = (int)$this->getValue('maxBackups');
			if ($maxBackups <= 0) {
				return;
			}

			$backups = $this->getBackupList();

			// Only enforce maxBackups for non-emergency backups
			$regularBackups = array();
			foreach ($backups as $backup) {
				if (strpos($backup['id'], 'emergency-') === 0) {
					continue;
				}
				$regularBackups[] = $backup;
			}

			$backupCount = count($regularBackups);
			if ($backupCount <= $maxBackups) {
				return;
			}

			$toRemove = $backupCount - $maxBackups;
			$backupsToRemove = array_slice($regularBackups, -$toRemove);
			foreach ($backupsToRemove as $backup) {
				$this->deleteBackup($backup['id']);
			}

			Log::set(__METHOD__ . LOG_SEP . 'Removed ' . count($backupsToRemove) . ' old backup(s)');
		} catch (Exception $e) {
			Log::set(__METHOD__ . LOG_SEP . 'Exception during cleanup: ' . $e->getMessage());
		}
	}

	private function getBackupList()
	{
		$workspace = $this->workspace();
		$backups = array();

		if (!Filesystem::directoryExists($workspace)) {
			return $backups;
		}

		$directories = Filesystem::listDirectories($workspace, '*', $sortByDate = true);
		foreach ($directories as $dir) {
			$backupId = basename($dir);
			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $backupId)) {
				continue;
			}
			$metadataPath = $dir . DS . 'metadata.json';

			$metadata = array(
				'id' => $backupId,
				'date' => date('Y-m-d H:i:s', filemtime($dir)),
				'isZip' => false,
				'size' => Filesystem::bytesToHumanFileSize(Filesystem::getSize($dir))
			);

			if (file_exists($metadataPath)) {
				$metadataContent = json_decode(file_get_contents($metadataPath), true);
				if ($metadataContent) {
					$metadata = array_merge($metadata, $metadataContent);
				}
			}

			// Never allow metadata to override the filesystem-derived backup ID
			$metadata['id'] = $backupId;

			$backups[] = $metadata;
		}

		$files = Filesystem::listFiles($workspace, '*', 'zip', $sortByDate = true);
		foreach ($files as $file) {
			$backupId = basename($file, '.zip');
			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $backupId)) {
				continue;
			}
			$metadataPath = $workspace . $backupId . '.json';

			$metadata = array(
				'id' => $backupId,
				'date' => date('Y-m-d H:i:s', filemtime($file)),
				'isZip' => true,
				'size' => Filesystem::bytesToHumanFileSize(filesize($file))
			);

			if (file_exists($metadataPath)) {
				$metadataContent = json_decode(file_get_contents($metadataPath), true);
				if ($metadataContent) {
					$metadata = array_merge($metadata, $metadataContent);
				}
			}

			// Never allow metadata to override the filesystem-derived backup ID
			$metadata['id'] = $backupId;

			$backups[] = $metadata;
		}

		usort($backups, function($a, $b) {
			return strcmp($b['date'], $a['date']);
		});

		return $backups;
	}
}
