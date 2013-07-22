var numFilesUploading = 0,
	numFilesUploaded = 0,
	uploader = {};

var initializeUploader = function(id) {
	// Define some more constant settings
	fileUploadSettings[id].originalUrl = fileUploadSettings[id].url;
	fileUploadSettings[id].preinit = {
		UploadFile: function(up, file) {
			numFilesUploading++;
			up.settings.url = up.settings.originalUrl;
			up.settings.url += '?name=' + file.name;
			up.settings.url += '&size=' + file.size;

			up.settings.url += '&thumbnail[width]=' + up.settings.thumbnail.width;
			up.settings.url += '&thumbnail[height]=' + up.settings.thumbnail.height;
			up.settings.url += '&thumbnail[exact]=' + up.settings.thumbnail.exact;

			if (up.settings.urlParams) {
				for (key in up.settings.urlParams) {
					up.settings.url += '&' + key + '=' + up.settings.urlParams[key];
				}
			}
		}
	};
	fileUploadSettings[id].onFileUploaded = fileUploadSettings[id].onFileUploaded || function() {};
	fileUploadSettings[id].onFileDeleted = fileUploadSettings[id].onFileDeleted || function() {};

	// Add files to the ul list
	fileUploadSettings[id].addFilesToQueue = function(files) {
		for (var i in files) {
			$('#' + id + '-filelist').append('<div id="' + files[i].id + '">' + files[i].name + ' (' + plupload.formatSize(files[i].size) + ')<b></b></div>');
		}
	};

	// Initialize uploader with settings
	uploader[id] = new plupload.Uploader(fileUploadSettings[id]);

	// On initialization
	uploader[id].bind('Init', function(up, params) {
		$('#' + id + '-filelist').html('');

		if (up.settings.existingFiles) {
			for (var i in up.settings.existingFiles) {
				up.settings.existingFiles[i].thumbnail_url += '?width=' + up.settings.thumbnail.width;
				up.settings.existingFiles[i].thumbnail_url += '&height=' + up.settings.thumbnail.height;
				up.settings.existingFiles[i].thumbnail_url += '&exact=' + up.settings.thumbnail.exact;

				up.settings.addFilesToQueue([up.settings.existingFiles[i]]);

				$('#' + up.settings.existingFiles[i].id).prepend('<img src="' + up.settings.existingFiles[i].thumbnail_url + '">');

				up.settings.onFileUploaded(up, {}, {}, {
					status: 'ok',
					data: up.settings.existingFiles[i]
				});
			}
		}
	});

	// On files added
	uploader[id].bind('FilesAdded', function(up, files) {
		up.settings.addFilesToQueue(files);

		// Limit the number of files
		if (
			(typeof up.settings.maxNumberOfFiles != 'undefined') &&
			up.settings.maxNumberOfFiles &&
			(up.files.length > up.settings.maxNumberOfFiles)
		) {
			for (var j = up.files.length; j > up.settings.maxNumberOfFiles; j--) {
				up.removeFile(up.files[j - 1]);
			}
		}

		// Remove existing items from the list, if replaced by new files
		if (
			(typeof up.settings.maxNumberOfFiles != 'undefined') &&
			up.settings.maxNumberOfFiles
		) {
			var itemsInList = $('#' + id + '-filelist').children();
			while (itemsInList.length > up.settings.maxNumberOfFiles) {
				itemsInList.first().remove();
				itemsInList = $('#' + id + '-filelist').children();
			}
		}

		// Start uploading file
		setTimeout(function() { uploader[id].start(); }, 10);
	});


	// On files removed
	uploader[id].bind('FilesRemoved', function(up, files) {
		for (var i in files) {
			$('#' + files[i].id).remove();

			up.settings.onFileDeleted(up, files[i]);
		}
	});

	// On upload progress
	uploader[id].bind('UploadProgress', function(up, file) {
		// Display percentage uploaded
		$('#' + file.id).find('b').html('<span>' + file.percent + '%</span>');

		if (file.percent === 0) {
			// File started uploading
			$('#' + id + '-progress').removeClass('progress-success').addClass('progress-info active');
		}

		// Set progress bar width
		$('#' + id + '-progressbar').width(file.percent + '%');
	});

	// Error
	uploader[id].bind('Error', function(up, err) {
		alert(err.message + ' Please upload the correct file type for ' + err.file.name + '.');
		$('.uploader #' + err.file.id).remove();
	});

	// On file uploaded
	uploader[id].bind('FileUploaded', function(up, file, info) {
		var response = jQuery.parseJSON(info.response);

		$('#' + id + '-progress').removeClass('progress-info active');
		if (response.status == 'ok') {
			$('#' + id + '-progress').addClass('progress-success');
			$('.uploader #' + file.id).append(' [<a class="delete-additional" additional_id="' + response.data.id + '" href="javascript:void(0)">x</a>]');
			$('.' + id + '-thumb' + ' #' + file.id).prepend('<img src="' + response.data.thumbnail_url + '">');
			$('.delete-additional').bind('click', function() {
				var id = $(this).attr('additional_id');
				$('input.' + id).remove();
				$('.uploader #' + file.id).remove();
			});
		} else {
			$('#' + id + '-progress').addClass('progress-error');
		}

		numFilesUploading--;
		numFilesUploaded++;

		// Completion callback
		up.settings.onFileUploaded(up, file, info, response);
	});

	// Initialize uploader
	uploader[id].init();
};


$(document).ready(function() {
	// Initialize uploader
	for (var id in fileUploadSettings) {
		initializeUploader(id);
	}


	// Don't allow form submissions while upload is in progress
	$('form').on('submit', function() {
		if (numFilesUploading) {
			alert('Please wait until files finish uploading before saving.');
			return false;
		}
	});
});

