/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require(["jquery"], function ($) {
	$(".importexportcms-index-import #validate-button").on("click", function (event) {
		event.preventDefault();
		var valid = $(".importexportcms-index-import #edit_form").valid();
		if (valid) {
			$("#loader_import").show();
			$("#edit_form").addClass("non-clickable");
			var form = $(".importexportcms-index-import #edit_form")[0];
			var data = new FormData(form);
			var url = $(".validate_csv").val();
			$.ajax({
				type: "POST",
				enctype: "multipart/form-data",
				url: url,
				data: data,
				processData: false,
				contentType: false,
				cache: false,
				success: function (data) {
					$(".importexportcms-index-import #import-button").hide();
					$(".importexportcms-index-import #validate-button").show();
					$(".importexportcms-index-import #base_fieldset_validate_message .messages").text("").removeClass("success").removeClass("error");
					if (data == "success") {
						$(".importexportcms-index-import #base_fieldset_validate_message .messages").addClass("success").append("File is valid! To start import process press Import button");
						$(".importexportcms-index-import #import-button").show();
						$(".importexportcms-index-import #validate-button").hide();
					} else {
						$(".importexportcms-index-import #base_fieldset_validate_message .messages").addClass("error").append(data);
					}
					$("#edit_form").removeClass("non-clickable");
					$("#loader_import").hide();
				}
			});
		}
	});
	$(document).ready(function () {
		$(".importexportcms-index-import #browse-file").on('click', function () {
			$(".importexportcms-index-import #import_file").val("");
			$("#messages .message-success").remove();
			$(".importexportcms-index-import #import_file").trigger("click");
		});
		$(".importexportcms-index-import #import_file").on("change", function (e) {
			$(".importexportcms-index-import .upload-filename").text("");
			var fileName = e.target.files[0].name;
			$(".importexportcms-index-import .upload-filename").text(fileName);
		});
		$(".importexportcms-index-import #import_file").on("click", function (e) {
			$(".importexportcms-index-import .upload-filename").text("");
			$(".importexportcms-index-import #import-button").hide();
			$(".importexportcms-index-import #validate-button").show();
			$(".importexportcms-index-import #base_fieldset_validate_message .messages").text("");
			$(".importexportcms-index-import #base_fieldset_validate_message .messages").removeClass("success");
			$(".importexportcms-index-import #base_fieldset_validate_message .messages").removeClass("error")
		});
	});
});
