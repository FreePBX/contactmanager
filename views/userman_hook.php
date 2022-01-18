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
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="enable_favorite_contacts"><?php echo _("Enable Favorite Contacts")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_favorite_contacts"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" id="enable_favorite_contacts1" name="enable_favorite_contacts" value="true" <?php echo ($enableFavoriteContacts) ? 'checked' : '' ?>>
							<label for="enable_favorite_contacts1"><?php echo _('Yes') ?></label>
							<input type="radio" id="enable_favorite_contacts2" name="enable_favorite_contacts" value="false" <?php echo (!is_null($enableFavoriteContacts) && !$enableFavoriteContacts) ? 'checked' : '' ?>>
							<label for="enable_favorite_contacts2"><?php echo _('No') ?></label>
							<?php if($mode == "user") {?>
								<input type="radio" id="enable_favorite_contacts3" name="enable_favorite_contacts" value='inherit' <?php echo is_null($enableFavoriteContacts) ? 'checked' : ''?>>
								<label for="enable_favorite_contacts3"><?php echo _('Inherit')?></label>
							<?php } ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="enable_favorite_contacts-help" class="help-block fpbx-help-block"><?php echo _("This enables the user to use the Favorite Contacts feature in the Sangoma Phone desktop client.")?></span>
		</div>
	</div>
</div>
<div class="element-container favorite-section">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="favorite_contact"><?php echo _("Favorite Contact List")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="favorite_contact"></i>
					</div>
					<div class="col-md-9">
						<select id="favorite_contact" class="form-control" name="favorite_contact">
							<option value="">Select</option>
							<?php if($mode == "user") {?>
								<option value="inherit" <?php echo ("inherit" == $favoriteContactListId) ? 'selected' : ''?>><?php echo _('Inherit')?></option>
							<?php } ?>
							<?php foreach($favoriteList as $list) { ?>
								<option value="<?php echo $list['id'] ?>" <?php echo ($list['id'] == $favoriteContactListId) ? 'selected' : ''?>><?php echo $list['list_name']?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="favorite_contact-help" class="help-block fpbx-help-block"><?php echo _("The contacts in the selected Favorite Contact List will be shown under the Favorites section of the Contacts widget in UCP, and Sangoma Phone desktop clients.")?></span>
		</div>
	</div>
</div>
<div class="element-container favorite-section">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="favorite_contact_edit_enabled"><?php echo _("Enable Favorite Contact Edit")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="favorite_contact_edit_enabled"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" id="favorite_contact_edit_enabled1" name="favorite_contact_edit_enabled" value="true" <?php echo ($favoriteContactEditEnabled) ? 'checked' : '' ?>>
							<label for="favorite_contact_edit_enabled1"><?php echo _('Yes') ?></label>
							<input type="radio" id="favorite_contact_edit_enabled2" name="favorite_contact_edit_enabled" value="false" <?php echo (!$favoriteContactEditEnabled) ? 'checked' : '' ?>>
							<label for="favorite_contact_edit_enabled2"><?php echo _('No') ?></label>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="favorite_contact_edit_enabled-help" class="help-block fpbx-help-block"><?php echo _("If enabled, the user will be able to edit their list of Favorite contacts from the Contacts widget in UCP.")?></span>
		</div>
	</div>
</div>

<script>
	<?php if(!is_null($enableFavoriteContacts) && !$enableFavoriteContacts) { ?>
		$(".favorite-section").hide();
	<?php } ?>
	<?php if(is_null($enableFavoriteContacts)) { ?>
		$("#favorite_contact").prop("disabled",true);
		$("input[type='radio'][name='favorite_contact_edit_enabled']").prop("disabled",true);
	<?php } else { ?>
		$("#favorite_contact option[value='inherit']").hide();
	<?php } ?>
	$("input[type='radio'][name='enable_favorite_contacts']").on('click', function () {
		if ($(this).val() == 'true') {
			$(".favorite-section").show();
			$("#favorite_contact").val("");
			$("#favorite_contact option[value='inherit']").hide();
			$("#favorite_contact").prop("disabled",false);
			$("input[type='radio'][name='favorite_contact_edit_enabled']").prop("disabled",false);
		} else if ($(this).val() == 'false') {
			$(".favorite-section").hide();
			$("#favorite_contact").val("");
			$("#favorite_contact option[value='inherit']").hide();
			$("#favorite_contact").prop("disabled",false);
			$("input[type='radio'][name='favorite_contact_edit_enabled']").prop("disabled",false);
		} else {
			$("#favorite_contact option[value='inherit']").show();
			$("#favorite_contact").val("inherit");
			$(".favorite-section").show();
			$("#favorite_contact").prop("disabled",true);
			$("input[type='radio'][name='favorite_contact_edit_enabled']").prop("disabled",true);
		}
	})
</script>