<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_showingroups"><?php echo _('Show In Contact Manager Groups')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_showingroups"></i>
					</div>
					<div class="col-md-9">
						<select id="contactmanager_showingroups" class="form-control chosenmultiselect contactmanager-group" name="contactmanager_showingroups[]" multiple="multiple">
							<?php foreach($visiblegroups as $group) { ?>
								<option value="<?php echo $group['id']?>" <?php echo in_array($group['id'],$showingroups) ? 'selected' : '' ?>><?php echo $group['name']?></option>
							<?php } ?>
							<option value=''>None</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="contactmanager_showingroups-help" class="help-block fpbx-help-block"><?php echo _("What internal groups to show this user in")?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_groups"><?php echo _("Viewable Contact Manager Groups")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_groups"></i>
					</div>
					<div class="col-md-9">
						<select id="contactmanager_groups" class="form-control chosenmultiselect contactmanager-group" name="contactmanager_groups[]" multiple="multiple">
							<?php foreach($groups as $group) { ?>
								<option value="<?php echo $group['id']?>" <?php echo $group['selected'] ? 'selected' : '' ?>><?php echo $group['name']?></option>
							<?php } ?>
							 <option value=''>None</option>

						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="contactmanager_groups-help" class="help-block fpbx-help-block"><?php echo _("These are the viewable contact manager groups which will show up for this user in UCP and RestApps (If purchased)")?></span>
		</div>
	</div>
</div>
