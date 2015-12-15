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
<?php if(!$cos) {?>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_groups"><?php echo _('Allowed Contact Manager Groups')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_groups"></i>
					</div>
					<div class="col-md-9">
						<select id="contactmanager_groups" class="bsmultiselect " name="contactmanager_groups[]" multiple="multiple">
							<?php foreach($groups as $group) {?>
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
			<span id="contactmanager_groups-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active contactmanager groups which will show up for this user in UCP")?></span>
		</div>
	</div>
</div>
<?php } else { ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="contactmanager_groups"><?php echo _('Allowed Contact Manager Groups')?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_groups"></i>
						</div>
						<div class="col-md-9">
							<strong><?php echo _("This is managed through the Class of Service module")?></strong>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="contactmanager_groups-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active contactmanager groups which will show up for this user in UCP")?></span>
			</div>
		</div>
	</div>
<?php } ?>
