<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="contactmanager_show"><?php echo _('Contact Manager')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="contactmanager_show"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" id="contactmanager1" name="contactmanager_show" value="true" <?php echo ($enabled) ? 'checked' : ''?>><label for="contactmanager1"><?php echo _('Yes')?></label>
							<input type="radio" id="contactmanager2" name="contactmanager_show" value="false" <?php echo (!$enabled) ? 'checked' : ''?>><label for="contactmanager2"><?php echo _('No')?></label>
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
