<?php
App::uses('AppHelper', 'View/Helper');
class PluploadHelper extends AppHelper {

/**
 * Other helpers used
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * Add an uploader to the page
 *
 * @param opts array Options
 *     - id string CSS id (optional, DEFAULT: fileupload)
 *                 If there are multiple uploaders on one page,
 *                 define a unique id for each one
 *     - buttonName string Button text (optional, DEFAULT: Upload file)
 *     - maxFileSize integer Max file size (in MB) (optional, DEFAULT: 2)
 *     - maxNumberOfFiles integer Max number of files allowed
 *     - acceptFileTypes array List of allowed file extensions
 *     - onFileUploaded string Javascript for what to do on completion
 *                             This string is put inside a callback function
 *                             with access to the following attributes:
 *                             - up: The uploader object
 *                             - file: The file object
 *                             - response: The JSON server response
 *     - onFileDeleted string Javascript for what to do on delete
 *                            This string is put inside a callback function
 *                            with access to the following attributes:
 *                            - up: The uploader object
 *                            - file: The file object
 *     - thumb array Thumbnail dimensions
 *                   - width integer (optional, DEFAULT: 36)
 *                   - height integer (optional, DEFAULT: 36)
 *                   - exact boolean
 * @return string HTML for uploader
 */
	public function uploader($opts = array()) {
		// Css
		$this->Html->css('/plupload/css/uploader.css', null, array('inline' => false));

		// Javascript libs
		$this->Html->script(array(
			// Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now)
			'http://bp.yahooapis.com/2.4.21/browserplus-min.js',

			// Plupload
			'/plupload/js/libs/plupload/plupload.full.js',

			'/plupload/js/uploader.js'
		), array('inline' => false));


		// id
		$id = (empty($opts['id'])) ? 'fileupload' : $opts['id'];


		$fileUploadSettings = array(
			'browse_button' => $id . '-pickfiles',
			'chunk_size' => '2mb',
			'container' => $id,
			'existingFiles' => false,
			'flash_swf_url' => '/js/libs/plupload/plupload.flash.swf',
			'runtimes' => implode(',', array('gears', 'html5', 'flash', 'silverlight', 'browserplus')),
			'silverlight_xap_url' => '/js/libs/plupload/plupload.silverlight.xap',
			'url' => $this->Html->url(array(
				'controller' => 'uploads',
				'action' => 'add',
				'mgr' => true
			)),
			'urlParams' => false
		);

		// URL GET parameters
		if (!empty($opts['urlParams'])) {
			$fileUploadSettings['urlParams'] = $opts['urlParams'];
		}

		// Max number of files
		if (isset($opts['maxNumberOfFiles'])) {
			$fileUploadSettings['maxNumberOfFiles'] = (integer) $opts['maxNumberOfFiles'];
		}

		// Allowed file types (filters)
		if (isset($opts['acceptFileTypes'])) {
			$fileUploadSettings['filters'] = array(
				array('title' => 'Files', 'extensions' => implode(',', $opts['acceptFileTypes']))
			);
		}

		// Thumbnail
		if (isset($opts['thumb']) && (isset($opts['thumb']['width']))) {
			$thumbWidth = (integer) $opts['thumb']['width'];
		} else {
			$thumbWidth = 36;
		}

		if (isset($opts['thumb']) && (isset($opts['thumb']['height']))) {
			$thumbHeight = (integer) $opts['thumb']['height'];
		} else {
			$thumbHeight = 36;
		}

		$fileUploadSettings['thumbnail'] = array(
			'width' => $thumbWidth,
			'height' => $thumbHeight,
			'exact' => (isset($opts['thumb']) && isset($opts['thumb']['exact'])) ? $opts['thumb']['exact'] : true
		);

		// Existing files
		if (!empty($opts['existingFiles'])) {
			// Remove 'Upload' key from array
			foreach ($opts['existingFiles'] as $i => $existingFile) {
				if (array_key_exists('Upload', $existingFile)) {
					$opts['existingFiles'][$i] = $existingFile['Upload'];
				}
			}
			$fileUploadSettings['existingFiles'] = $opts['existingFiles'];
		}

		// Max file size
		$maxFileSize = (empty($opts['maxFileSize'])) ? '7mb' : ((integer) $opts['maxFileSize'] . 'mb');
		$fileUploadSettings['max_file_size'] = $maxFileSize;

		// Output uploader settings as javascript
		$this->Html->scriptStart(array('inline' => false));
		echo 'var fileUploadSettings = fileUploadSettings || {};';
		echo 'fileUploadSettings["' . $id . '"] = ' . json_encode($fileUploadSettings) . ';';

		// On file uploaded (callback function)
		if (!empty($opts['onFileUploaded'])) {
			echo 'fileUploadSettings["' . $id . '"].onFileUploaded = function(up, file, info, response) {';
			echo $opts['onFileUploaded'];
			echo '};';
		}

		// On file deleted (callback function)
		if (!empty($opts['onFileDeleted'])) {
			echo 'fileUploadSettings["' . $id . '"].onFileDeleted = function(up, file) {';
			echo $opts['onFileDeleted'];
			echo '};';
		}

		$this->Html->scriptEnd();

		return <<<END
		<div id="{$id}" class="uploader">
			<div class="row-fluid">
				<div class="span7">
					<a id="{$id}-pickfiles" class="btn btn-success"><i class="icon-plus icon-white"></i> <span>{$opts['buttonName']}&hellip;</span></a>
				</div>
				<div class="span5">
					<div id="{$id}-progress" class="progress progress-info progress-striped active">
						<div id="<?php echo $id; ?>-progressbar" class="bar" style="width: 0%;"></div>
					</div>
				</div>
			</div>
			<div id="{$id}-filelist">Initializing...</div>
		</div>
END;
	}

}
