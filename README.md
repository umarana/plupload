# Plupload Plugin for CakePHP 2.0

## Installation

### Add as a submodule:

	[submodule "app/Plugin/Plupload"]
		path = app/Plugin/Plupload
		url = https://thriveline@bitbucket.org/thriveline/plupload-plugin.git

### Load plugin in Config/bootstrap.php

	CakePlugin::load('Plupload');
	
### Include component in UploadsController

	public $components = array('Plupload.Plupload');
	
### Create action in UploadsController for handling upload

###### Example

	/**
	 * Upload file
	 *
	 * Uses plupload to upload file in chunks
	 *
	 * @return void (Outputs JSON)
	 */
		public function mgr_add() {
			try {
				$file = $this->Plupload->upload();
				if (!$file) {
					// Successfully uploaded a chunk,
					// but has not yet finished the full file
					$this->outputJSON();
				}
			} catch (Exception $e) {
				// Error
				$this->outputJSON(array(
					'code' => $e->getCode()
				), false, $e->getMessage());
			}
	
			// Save to database
			$this->Upload->create($file);
			$saved = $this->Upload->save($file);
			if ($saved) {
				$saved = $this->Upload->findById($this->Upload->id);
				$saved = $saved['Upload'];
	
				if (!empty($this->request->query['thumbnail'])) {
					$saved['thumbnail_url'] .= '?' . http_build_query($this->request->query['thumbnail']);
				}
			}
	
			$this->outputJSON($saved);
		}

### Include helper in views

###### In controller
	$this->set('acceptFileTypes', ClassRegistry::init('Upload')->allowedTypes['img']);
	$this->set('existingFiles', array());
	$this->helpers[] = 'Plupload.Plupload';

###### In view (example)
	echo $this->Plupload->uploader(array(
		'id' => 'upload-photo',
		'buttonName' => 'Attach image',
		'maxNumberOfFiles' => 1,
		'acceptFileTypes' => $acceptFileTypes,
		'onFileUploaded' => '$("#photo").val(response.data.basename);',
		'onFileDeleted' => '$("#photo").val("");',
		'thumb' => array(
			'width' => 130,
			'height' => 175,
			'exact' => false
		),
		'existingFiles' => ($existingPhoto) ? array($existingPhoto) : array()
	));