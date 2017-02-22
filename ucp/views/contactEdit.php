<div class="col-md-12">
	<div class="contact-container">
		<form role="form" id="contact-form">
			<input type="hidden" id="id" name="id" value="<?php echo $contact['id']?>">
			<div class="form-group">
				<label for="displayname"><?php echo _('Display Name')?></label>
				<input type="text" class="form-control" id="displayname" placeholder="Display Name" name="displayname" value="<?php echo $contact['displayname']?>">
			</div>
			<div class="form-group">
				<label for="fname"><?php echo _('First Name')?></label>
				<input type="text" class="form-control" id="fname" placeholder="First Name" name="fname" value="<?php echo $contact['fname']?>">
			</div>
			<div class="form-group">
				<label for="lastname"><?php echo _('Last Name')?></label>
				<input type="text" class="form-control" id="lname" placeholder="Last Name" name="lname" value="<?php echo $contact['lname']?>">
			</div>
			<div class="form-group">
				<label for="title"><?php echo _('Title')?></label>
				<input type="text" class="form-control" id="title" placeholder="Title" name="title" value="<?php echo $contact['title']?>">
			</div>
			<div class="form-group">
				<label for="company"><?php echo _('Company')?></label>
				<input type="text" class="form-control" id="company" placeholder="Company" name="company" value="<?php echo $contact['company']?>">
			</div>
			<div class="row">
				<div class="col-md-3">
					<div id="contactmanager_dropzone" class="image">
						<div class="message"><?php echo _("Drop a new image here");?></div>
						<img class="<?php echo (!empty($contact) && !empty($contact['image'])) ? '' : 'hidden'?>" src="<?php echo (!empty($contact) && !empty($contact['image'])) ? '?quietmode=1&module=Contactmanager&command=limage&entryid='.$contact['id'].'&time='.time() : ''?>">
					</div>
					<button id="contactmanager_del-image" data-entryid="<?php echo !empty($contact) ? $contact['id'] : ''?>" class="btn btn-danger btn-sm <?php echo (!empty($contact) && !empty($contact['image'])) ? '' : 'hidden'?>"><?php echo _("Delete Image")?></button>
				</div>
				<div class="col-md-9">
					<input type="hidden" class="special" name="contactmanager_image" id="contactmanager_image">
					<span class="btn btn-default btn-file">
						<?php echo _("Browse")?>
						<input id="contactmanager_imageupload" type="file" class="skip form-control" name="files[]" data-url="ajax.php?&amp;module=Contactmanager&amp;command=uploadimage&amp;type=contact&amp;id=<?php echo $contact['id']?>" class="form-control" multiple>
					</span>
					<span class="filename"></span>
					<div id="contactmanager_upload-progress" class="progress">
						<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
					</div>
					<div class="radioset">
						<input name="contactmanager_gravatar" id="contactmanager_gravatar" data-toggle="toggle" data-on="Enable" data-off="Disable" class="skip" data-entryid="<?php echo !empty($contact) ? $contact['id'] : ''?>" type="checkbox" value="on" <?php echo (!empty($contact) && !empty($contact['image'])) && !empty($contact['image']['gravatar']) ? 'checked' : ''?>>
						<label for="contactmanager_gravatar"><?php echo _("Use Gravatar")?></label>
					</div>
				</div>
			</div>
			<label><?php echo _('Numbers')?></label>
			<?php $contact['numbers'] = is_array($contact['numbers']) ? $contact['numbers'] : array(); ?>
			<?php foreach($contact['numbers'] as $c => $number) {?>
				<div class="form-inline item-container" >
					<div class="pull-left">
						<i class="fa fa-ban fa-fw delete" data-type="number"></i>
						<input type="text" class="form-control number" data-name="number" value="<?php echo $number['number']?>">
						<select class="form-control number" data-name="type">
							<option value="work" <?php echo ($number['type'] == "work") ? 'selected' : ''?>><?php echo _('Work')?></option>
							<option value="home" <?php echo ($number['type'] == "home") ? 'selected' : ''?>><?php echo _('Home')?></option>
							<option value="cell" <?php echo ($number['type'] == "cell") ? 'selected' : ''?>><?php echo _('Cell')?></option>
							<option value="other" <?php echo ($number['type'] == "other") ? 'selected' : ''?>><?php echo _('Other')?></option>
						</select>
					</div>
					<div class="pull-right">
						<?php if($featurecode['enabled']) { ?>
							<label><?php echo _('Speed Dial')?>:</label>
							<div class="input-group">
								<span class="input-group-addon" style="padding: 4px;"><?php echo $featurecode['code']?></span>
								<input type="number" class="form-control number-sd skip" data-orig="<?php echo $number['speeddial']?>" value="<?php echo $number['speeddial']?>" style="width: 50px;padding: 3px;" min="0" data-name="numbersd" <?php echo trim($number['speeddial']) != "" ? '' : 'disabled' ?>>
								<span class="input-group-addon">
									<input type="checkbox" style="margin-bottom: 0px;" class="enable-sd skip" data-name="numbersde" <?php echo trim($number['speeddial']) != "" ? 'checked' : '' ?>><?php echo _("Enable")?></label>
								</span>
							</div>
						<?php } ?>
						<?php echo _('SMS')?>: <input type="checkbox" data-name="smsflag" data-toggle="toggle" data-size="small" data-on="<?php echo  _('Yes')?>" data-off="<?php echo  _('No')?>" <?php echo in_array("sms",$number['flags']) ? 'checked' : ''?>>
						<?php echo _('Fax')?>: <input type="checkbox" data-name="faxflag" data-toggle="toggle" data-size="small" data-on="<?php echo  _('Yes')?>" data-off="<?php echo  _('No')?>" <?php echo in_array("fax",$number['flags']) ? 'checked' : ''?>>
					</div>
				</div>
			<?php } ?>
			<div class="form-inline item-container" >
				<div class="pull-left">
					<i class="fa fa-ban fa-fw delete" data-type="number"></i>
					<input type="text" class="form-control number" data-name="number" value="">
					<select class="form-control number" data-name="type">
						<option value="work" selected="selected"><?php echo _('Work')?></option>
						<option value="home"><?php echo _('Home')?></option>
						<option value="cell"><?php echo _('Cell')?></option>
						<option value="other"><?php echo _('Other')?></option>
					</select>
				</div>
				<div class="pull-right">
					<?php if($featurecode['enabled']) { ?>
						<label><?php echo _('Speed Dial')?>:</label>
						<div class="input-group">
							<span class="input-group-addon" style="padding: 4px;"><?php echo $featurecode['code']?></span>
							<input type="number" class="form-control number-sd skip" value="" style="width: 50px;padding: 3px;" min="0" data-name="numbersd" disabled>
							<span class="input-group-addon">
								<input type="checkbox" style="margin-bottom: 0px;" class="enable-sd skip" data-name="numbersde"><?php echo _("Enable")?></label>
							</span>
						</div>
					<?php } ?>
					<?php echo _('SMS')?>: <input type="checkbox" data-name="smsflag" data-toggle="toggle" data-size="small" data-on="Yes" data-off="No">
					<?php echo _('Fax')?>: <input type="checkbox" data-name="faxflag" data-toggle="toggle" data-size="small" data-on="Yes" data-off="No">
				</div>
			</div>
			<button class="btn btn-default btn-xs add-additional" data-type="number"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Number')?></button>
			</br>
			</br>
			<label>XMPP</label>
			<?php $contact['xmpps'] = !empty($contact['xmpps']) ? $contact['xmpps'] : array(); ?>
			<?php foreach($contact['xmpps'] as $xmpp) {?>
				<div class="form-inline item-container" >
					<i class="fa fa-ban fa-fw delete" data-type="xmpp"></i>
					<input type="text" class="form-control special" data-name="xmpp" style="width: 96%;" value="<?php echo $xmpp['xmpp']?>">
				</div>
			<?php } ?>
			<div class="form-inline item-container" >
				<i class="fa fa-ban fa-fw delete" data-type="xmpp"></i>
				<input type="text" class="form-control special" data-name="xmpp" style="width: 96%;">
			</div>
			<button class="btn btn-default btn-xs add-additional" data-type="xmpp"><i class="fa fa-plus fa-fw"></i><?php echo _('Add XMPP')?></button>
			</br>
			</br>
			<label><?php echo _('Email')?></label>
			<?php $contact['emails'] = !empty($contact['emails']) ? $contact['emails'] : array(); ?>
			<?php foreach($contact['emails'] as $email) {?>
				<div class="form-inline item-container" >
					<i class="fa fa-ban fa-fw delete" data-type="email"></i>
					<input type="text" class="form-control special" data-name="email" style="width: 96%;" value="<?php echo $email['email']?>">
				</div>
			<?php } ?>
			<div class="form-inline item-container" >
				<i class="fa fa-ban fa-fw delete" data-type="email"></i>
				<input type="text" class="form-control special" data-name="email" style="width: 96%;">
			</div>
			<button class="btn btn-default btn-xs add-additional" data-type="email"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Email')?></button>
		</br>
		</br>
		<label><?php echo _('Website')?></label>
		<?php $contact['websites'] = !empty($contact['websites']) ? $contact['websites'] : array(); ?>
		<?php foreach($contact['websites'] as $website) {?>
			<div class="form-inline item-container" >
				<i class="fa fa-ban fa-fw delete" data-type="website"></i>
				<input type="text" class="form-control special" data-name="email" style="width: 96%;" value="<?php echo $website['website']?>">
			</div>
		<?php } ?>
		<div class="form-inline item-container" >
			<i class="fa fa-ban fa-fw delete" data-type="website"></i>
			<input type="text" class="form-control special" data-name="website" style="width: 96%;">
		</div>
		<button class="btn btn-default btn-xs add-additional" data-type="website"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Website')?></button>
			<!--

			<div class="form-group">
				<label><?php echo _('Website')?></label>
				<div class="websites additional">
					<table data-type="websites">
						<tr class="template">
							<td>
								<i class="fa fa-ban fa-fw delete"></i>
							</td>
							<td>
								<input type="text" class="form-control special" data-name="website">
							</td>
						</tr>
						<?php $contact['websites'] = !empty($contact['websites']) ? $contact['websites'] : array(); ?>
						<?php foreach($contact['websites'] as $website) {?>
							<tr>
								<td>
									<i class="fa fa-ban fa-fw delete"></i>
								</td>
								<td>
									<input type="text" class="form-control special" data-name="website" value="<?php echo $website['website']?>">
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
				<button class="btn btn-default btn-xs add-additional" data-type="websites"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Website')?></button>
			</div>
			<input type="hidden" id="mode" name="mode" value="<?php echo isset($add) && ($add) ? 'add' : 'edit'?>">
			<?php if(isset($add) && $add) {?>
			<?php } else { ?>
				<input type="hidden" id="id" name="id" value="<?php echo $contact['id']?>">

			<?php } ?>
		-->
		</form>
	</div>
</div>
