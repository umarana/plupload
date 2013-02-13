<?php

class PluploadComponent extends Component {

/**
 * Allowed file types and extensions
 *
 * @var array
 */
	public $allowedTypes = array(
		'doc' => array(
			'pdf',
		),
		'img' => array(
			'gif',
			'jpeg',
			'jpg',
			'png',
		),
		'vid' => array(
			'm4v',
			'mov',
			'mp4',
			'mpeg',
			'mpg',
			'webm',
		),
	);

/**
 * Holds the reference to Controller
 *
 * @var Controller
 */
	public $controller;

/**
 * Holds the reference to Controller::$request
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Holds the reference to Controller::$response
 *
 * @var CakeResponse
 */
	public $response;

/**
 * Settings
 *
 * @var array
 */
	public $settings;

/**
 * Constructor
 *
 * @param ComponentCollection $collection ComponentCollection object.
 * @param array $settings Array of settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->controller = $collection->getController();
		$this->request    = $this->controller->request;
		$this->response   = $this->controller->response;

		// Settings
		$this->settings = array_merge(array(
			// Base directory where files will be saved
			'targetDir' => WWW_ROOT . 'media'
		), $settings);
	}

/**
 * Upload file
 *
 * @param targetDirOverride (string) Optional override of default target directory.
 * @return array|false False if successfully uploaded a chunk. Array if successfully finished uploading file.
 * - name (string) Original filename
 * - basename (string) Unique filename that file was saved as
 * - ext (string) File extension
 * - type (string) File type
 * - size (integer) File size
 * @exception Throws code and message if there is an error uploading the file
 */
	public function upload($targetDirOverride = null) {
		$this->controller->disableCache();

		// Max execution time (per chunk)
		@set_time_limit(5 * MINUTE);

		// Get parameters
		$chunk      = isset($this->request->data['chunk']) ? intval($this->request->data['chunk']) : 0;
		$chunks     = isset($this->request->data['chunks']) ? intval($this->request->data['chunks']) : 0;
		$fileName   = isset($this->request->data['name']) ? $this->request->data['name'] : '';

		// Clean the fileName for security reasons
		$fileName   = preg_replace('/[^\w\._]+/', '_', $fileName);

		// Parse filename
		$name       = $this->getNameWithoutExtension($fileName);
		$ext        = $this->getExtension($fileName);
		$fileType   = $this->getFiletypeCode($ext);

		// Define output filepath
		if (!$targetDirOverride){
			$targetDir  = $this->settings['targetDir'];
			$targetDir .= DS . $fileType;
			$targetDir .= DS . 'original';
		} else {
			$targetDir = $targetDirOverride;
		}
		$uniqid     = uniqid();
		$output     = $targetDir . DS . $uniqid . '.' . $ext;

		// Make sure the fileName is unique but only if chunking is disabled
		if (($chunks < 2) && file_exists($targetDir . DS . $fileName)) {
			$count = 1;
			while (file_exists($targetDir . DS . $name . '_' . $count . '.' . $ext)) {
				$count++;
			}

			$fileName = $name . '_' . $count . '.' . $ext;
		}

		$filePath = $targetDir . DS . $fileName;

		// Remove old temp files
		if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DS . $file;

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - (5 * HOUR)) && ($tmpfilePath != "{$filePath}.part")) {
					@unlink($tmpfilePath);
				}
			}

			closedir($dir);
		} else {
			throw new Exception('Failed to open temp directory.', 100);
		}

		// Look for the content type header
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$contentType = $_SERVER['HTTP_CONTENT_TYPE'];
		}
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = $_SERVER['CONTENT_TYPE'];
		}

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, 'multipart') !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen("{$filePath}.part", ($chunk == 0) ? 'wb' : 'ab');
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], 'rb');

					if ($in) {
						while ($buff = fread($in, 4096)) {
							fwrite($out, $buff);
						}
					} else {
						throw new Exception('Failed to open input stream.', 101);
					}
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else {
					throw new Exception('Failed to open output stream.', 102);
				}
			} else {
				throw new Exception('Failed to move uploaded file.', 103);
			}
		} else {
			// Open temp file
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen('php://input', 'rb');

				if ($in) {
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}
				} else {
					throw new Exception('Failed to open input stream.', 101);
				}
				fclose($in);
				fclose($out);
			} else {
				throw new Exception('Failed to open output stream.', 102);
			}
		}

		// Check if file has been uploaded
		$saved = false;
		if (!$chunks || ($chunk == $chunks - 1)) {
			// Strip the temp .part suffix off
			rename("{$filePath}.part", $output);

			// File has been uploaded
			$saved = array(
				'name' => $this->request->query['name'],
				'basename' => $uniqid . '.' . $ext,
				'ext' => $ext,
				'type' => $fileType,
				'size' => (integer) $this->request->query['size']
			);
		}

		return $saved;
	}

/**
 * Get the name of a file, without the extension
 *
 * @param string $filename
 * @return string name
 */
	public function getNameWithoutExtension($filename) {
		$ext = strrpos($filename, '.');
		return substr($filename, 0, $ext);
	}

/**
 * Determine the extension of a filename (last few characters after final .)
 *
 * @param string $filename
 * @return string extension
 */
	public function getExtension($filename) {
		$ext = strrpos($filename, '.') + 1;
		return str_replace('jpeg', 'jpg', strtolower(substr($filename, $ext)));
	}

/**
 * Get the 3-character code for what type of file this is
 *
 * @param  string $ext Extensions
 * @return string|false File type, or false if not a valid extension
 */
	public function getFiletypeCode($ext) {
		foreach ($this->allowedTypes as $type => $extensions) {
			if (in_array($ext, $extensions)) {
				return $type;
			}
		}
		return false;
	}

}
