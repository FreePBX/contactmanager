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
			<span id="contactmanager_groups-help" class="help-block fpbx-help-block"><?php echo _("These are the viewable contact manager groups which will show up for this user in UCP and Phone Apps.")?></span>
		</div>
	</div>
</div>
<?php if($enableFavoriteContacts) { ?>
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
							<?php if($mode == "user") { ?>
								<option value="inherit" <?php echo ("inherit" == $favoriteContactListId) ? 'selected' : ''?>><?php echo _('Inherit')?></option>
							<?php } else { ?>
								<option value="">Select</option>
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
			<span id="favorite_contact-help" class="help-block fpbx-help-block"><?php echo _("The contacts in the selected Favorite Contact List is used as the default favorite list for the userman users. If the user don't have a favorite contact list, this list will be shown as the default favorite list under the 'Favorites' tab of the 'Contacts' widget in UCP and under the 'My Favorites' drop down of the 'Contacts' tab in Sangoma Phone Desktop Client. If a contact is added or removed from the shown favorite list from UCP or Sangoma Phone Desktop Client, a new list will be created specific to the logged in user.")?></span>
		</div>
	</div>
</div>
<?php } ?>