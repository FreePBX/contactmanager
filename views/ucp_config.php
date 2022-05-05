<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="">
				<div class="row form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanagerspeeddialenable"><?php echo _("Allow Speed Dial Modifications") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanagerspeeddialenable"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" name="contactmanager_speeddial_enable" id="contactmanager_speeddial_enable_yes" value="yes" <?php echo $speeddial ? 'checked' : ''?>>
						<label for="contactmanager_speeddial_enable_yes"><?php echo _("Yes")?></label>
						<input type="radio" name="contactmanager_speeddial_enable" id="contactmanager_speeddial_enable_no" value="no" <?php echo (!is_null($speeddial) && !$speeddial) ? 'checked' : ''?>>
						<label for="contactmanager_speeddial_enable_no"><?php echo _("No")?></label>
						<?php if($mode == "user") {?>
							<input type="radio" id="contactmanager_speeddial_enable_inherit" name="contactmanager_speeddial_enable" value='inherit' <?php echo is_null($speeddial) ? 'checked' : ''?>>
							<label for="contactmanager_speeddial_enable_inherit"><?php echo _('Inherit')?></label>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="contactmanagerspeeddialenable-help" class="help-block fpbx-help-block"><?php echo _("Allow the user to modify and change speeddials for contacts they can edit")?></span>
		</div>
	</div>
</div>
