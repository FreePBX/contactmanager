<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_show"><?php echo _('Show In Contact Manager')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_show"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" id="contactmanager1" name="contactmanager_show" value="true" <?php echo ($enabled) ? 'checked' : ''?>><label for="contactmanager1"><?php echo _('Yes')?></label>
							<input type="radio" id="contactmanager2" name="contactmanager_show" value="false" <?php echo (!is_null($enabled) && !$enabled) ? 'checked' : ''?>><label for="contactmanager2"><?php echo _('No')?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="contactmanager3" name="contactmanager_show" value='inherit' <?php echo is_null($enabled) ? 'checked' : ''?>>
								<label for="contactmanager3"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="contactmanager_show-help" class="help-block fpbx-help-block"><?php echo _("Whether to show this contact in contact manager.")?></span>
		</div>
	</div>
</div>
<?php if($mode == "user") {?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="contactmanager_imageupload"><?php echo _('Image')?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_image"></i>
						</div>
						<div class="col-md-9">
							<div class="col-md-4">
								<div id="contactmanager_dropzone" class="image">
									<div class="message"><?php echo _("Drop a new image here");?></div>
									<img class="<?php echo (!empty($cmdata) && !empty($cmdata['image'])) ? '' : 'hidden'?>" src="<?php echo (!empty($cmdata) && !empty($cmdata['image'])) ? 'ajax.php?module=contactmanager&amp;command=limage&amp;entryid='.$cmdata['id'] : ''?>">
								</div>
								<button id="contactmanager_del-image" data-entryid="<?php echo !empty($cmdata) ? $cmdata['id'] : ''?>" class="btn btn-danger btn-sm <?php echo (!empty($cmdata) && !empty($cmdata['image'])) ? '' : 'hidden'?>"><?php echo _("Delete Image")?></button>
							</div>
							<div class="col-md-8">
								<input type="hidden" name="contactmanager_image" id="contactmanager_image">
								<span class="btn btn-default btn-file">
									<?php echo _("Browse")?>
									<input id="contactmanager_imageupload" type="file" class="form-control" name="files[]" data-url="ajax.php?module=contactmanager&amp;command=uploadimage" class="form-control" multiple>
								</span>
								<span class="filename"></span>
								<div id="contactmanager_upload-progress" class="progress">
									<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
								</div>
								<div class="radioset">
									<input name="contactmanager_gravatar" id="contactmanager_gravatar" data-entryid="<?php echo !empty($cmdata) ? $cmdata['id'] : ''?>" type="checkbox" value="on" <?php echo (!empty($cmdata) && !empty($cmdata['image'])) && !empty($cmdata['image']['gravatar']) ? 'checked' : ''?>>
									<label for="contactmanager_gravatar"><?php echo _("Use Gravatar")?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="contactmanager_image-help" class="help-block fpbx-help-block"><?php echo _('Contact Image for this user. Image will be deleted if "Show in Contact Manager" is set to no or inherits no')?></span>
			</div>
		</div>
	</div>
<?php } ?>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_groups"><?php echo _("Allowed Contact Manager Groups")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_groups"></i>
					</div>
					<div class="col-md-9">
						<select id="contactmanager_groups" class="form-control chosenmultiselect contactmanager-group" name="contactmanager_groups[]" multiple="multiple">
							<?php foreach($groups as $group) { ?>
								<option value="<?php echo $group['id']?>" <?php echo $group['selected'] ? 'selected' : '' ?>><?php echo $group['name']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="contactmanager_groups-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active contactmanager groups which will show up for this user in UCP and RestApps (If purchased)")?></span>
		</div>
	</div>
</div>
<style>
#contactmanager_dropzone {
	min-height: 75px;
	width: 90px;
	border: 3px dotted #ccc;
	background-color: #F5F5F5;
	border-radius: 10px;
	margin-top: 0px;
	margin-bottom: 3px;
}
#contactmanager_dropzone .message {
	text-align: center;
	font-weight: bold;
	font-size: 86%;
	margin-left: 2px;
}
#contactmanager_dropzone.image img{
	width: 100%;
}
#contactmanager_upload-progress {
	margin-top: 5px;
	margin-bottom: 0px;
}
#contactmanager_dropzone.activate {
	border-color: black;
}

#contactmanager_dropzone.activate .message {
	opacity: .5;
}
</style>
<script>
/**
 * Drag/Drop/Upload Files
 */
$('#contactmanager_dropzone').on('drop dragover', function (e) {
	e.preventDefault();
});
$('#contactmanager_dropzone').on('dragleave drop', function (e) {
	$(this).removeClass("activate");
});
$('#contactmanager_dropzone').on('dragover', function (e) {
	$(this).addClass("activate");
});
var supportedRegExp = "png|jpg|jpeg";
$( document ).ready(function() {
	$('#contactmanager_imageupload').fileupload({
		dataType: 'json',
		dropZone: $("#contactmanager_dropzone"),
		add: function (e, data) {
			//TODO: Need to check all supported formats
			var sup = "\.("+supportedRegExp+")$",
					patt = new RegExp(sup),
					submit = true;
			$.each(data.files, function(k, v) {
				if(!patt.test(v.name.toLowerCase())) {
					submit = false;
					alert(_("Unsupported file type"));
					return false;
				}
			});
			if(submit) {
				$("#contactmanager_upload-progress .progress-bar").addClass("progress-bar-striped active");
				data.submit();
			}
		},
		drop: function () {
			$("#contactmanager_upload-progress .progress-bar").css("width", "0%");
		},
		dragover: function (e, data) {
		},
		change: function (e, data) {
		},
		done: function (e, data) {
			$("#contactmanager_upload-progress .progress-bar").removeClass("progress-bar-striped active");
			$("#contactmanager_upload-progress .progress-bar").css("width", "0%");

			if(data.result.status) {
				$("#contactmanager_dropzone img").attr("src","ajax.php?module=contactmanager&command=limage&temporary=1&name="+data.result.filename);
				$("#contactmanager_image").val(data.result.filename);
				$("#contactmanager_dropzone img").removeClass("hidden");
				$("#contactmanager_del-image").removeClass("hidden");
				$("#contactmanager_gravatar").prop('checked', false);
			} else {
				alert(data.result.message);
			}
		},
		progressall: function (e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$("#contactmanager_upload-progress .progress-bar").css("width", progress+"%");
		},
		fail: function (e, data) {
		},
		always: function (e, data) {
		}
	});

	$("#contactmanager_del-image").click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		var id = $("input[name=user]").val(),
				grouptype = 'userman';
		$.post( "ajax.php?module=contactmanager&command=delimage", {id: id, img: $("#contactmanager_image").val()}, function( data ) {
			if(data.status) {
				$("#contactmanager_image").val("");
				$("#contactmanager_dropzone img").addClass("hidden");
				$("#contactmanager_dropzone img").attr("src","");
				$("#contactmanager_del-image").addClass("hidden");
				$("#contactmanager_gravatar").prop('checked', false);
			}
		});
	});

	$("#contactmanager_gravatar").change(function() {
		if($(this).is(":checked")) {
			var id = $("input[name=user]").val(),
					grouptype = 'userman';
			if($("#email").val() === "") {
				alert(_("No email defined"));
				$("#contactmanager_gravatar").prop('checked', false);
				return;
			}
			var t = $("label[for=contactmanager_gravatar]").text();
			$("label[for=contactmanager_gravatar]").text(_("Loading..."));
			$.post( "ajax.php?module=contactmanager&command=getgravatar", {id: id, grouptype: grouptype, email: $("#email").val()}, function( data ) {
				$("label[for=contactmanager_gravatar]").text(t);
				if(data.status) {
					$("#contactmanager_dropzone img").data("oldsrc",$("#dropzone img").attr("src"));
					$("#contactmanager_dropzone img").attr("src","ajax.php?module=contactmanager&command=limage&temporary=1&name="+data.filename);
					$("#contactmanager_image").data("old",$("#image").val());
					$("#contactmanager_image").val(data.filename);
					$("#contactmanager_dropzone img").removeClass("hidden");
					$("#contactmanager_del-image").removeClass("hidden");
				} else {
					alert(data.message);
					$("#contactmanager_gravatar").prop('checked', false);
				}
			});
		} else {
			var oldsrc = $("#contactmanager_dropzone img").data("oldsrc");
			if(typeof oldsrc !== "undefined" && oldsrc !== "") {
				$("#contactmanager_dropzone img").attr("src",oldsrc);
				$("#contactmanager_image").val($("#image").data("old"));
			} else {
				$("#contactmanager_image").val("");
				$("#contactmanager_dropzone img").addClass("hidden");
				$("#contactmanager_dropzone img").attr("src","");
				$("#contactmanager_del-image").addClass("hidden");
			}
		}
	});
});
</script>
