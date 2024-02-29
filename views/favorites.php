<div class="fpbx-container">
	<div class="display full-border">
		<form name="list" class="fpbx-submit" method="post" action="config.php?display=contactmanager" <?php if(isset($list['id'])) {?>data-fpbx-delete="config.php?display=contactmanager&amp;list_id=<?php echo $list['id']?>&amp;action=dellist<?php }?>">
			<?php if(!empty($list)) {?>
				<input type="hidden" name="list_id" id="list_id" value="<?php echo $list['id']?>">
				<h1><?php echo _("Edit List")?></h1>
			<?php } else { ?>
				<h1><?php echo _("Add List")?></h1>
			<?php }?>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="list_name"><?php echo _('Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="list_name"></i>
								</div>
								<div class="col-md-9"><input id="list_name" name="list_name" class="form-control" data-invalid="<?php echo _("List Name is required.") ?>" value="<?php echo (isset($list['list_name']) ? htmlentities($list['list_name']) : '')?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="list_name-help" class="help-block fpbx-help-block"><?php echo _('Name of the favorite contact list')?></span>
					</div>
				</div>
			</div>
			<div>
				<?php echo $subContent; ?>
			</div>
		</form>
	</div>
</div>

<script type='text/javascript'>

// When 'Submit' is clicked, return a useful list of things.
$('form').submit(function() {
	var form=$(this);
	$('#included_contacts>span').each(function() {
		form.append('<input type="hidden" name="included_contacts[]" value="'+$(this).attr('data-contactId')+'">');
	});
});

$(document).on('click', 'input[name="reset"]', function() {
	location.reload();
});

</script>
