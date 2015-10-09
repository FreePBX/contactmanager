<div class="col-md-10">
	<div class="contact-container">
		<div class="alert" role="alert" style="display:none"></div>
		<form role="form" id="<?php echo ($add) ? 'add' : 'edit'?>Contact">
			<div class="form-group">
				<label for="displayname"><?php echo _('Display Name')?></label>
				<input type="text" class="form-control" id="displayname" placeholder="Display Name" value="<?php echo $contact['displayname']?>">
			</div>
			<div class="form-group">
				<label for="fname"><?php echo _('First Name')?></label>
				<input type="text" class="form-control" id="fname" placeholder="First Name" value="<?php echo $contact['fname']?>">
			</div>
			<div class="form-group">
				<label for="lastname"><?php echo _('Last Name')?></label>
				<input type="text" class="form-control" id="lname" placeholder="Last Name" value="<?php echo $contact['lname']?>">
			</div>
			<div class="form-group">
				<label for="title"><?php echo _('Title')?></label>
				<input type="text" class="form-control" id="title" placeholder="Title" value="<?php echo $contact['title']?>">
			</div>
			<div class="form-group">
				<label for="company"><?php echo _('Company')?></label>
				<input type="text" class="form-control" id="company" placeholder="Company" value="<?php echo $contact['company']?>">
			</div>
			<div class="form-group">
				<label><?php echo _('Numbers')?></label>
				<div class="numbers additional">
					<table data-type="numbers">
						<tr class="template">
							<td>
								<a><i class="fa fa-ban fa-fw delete"></i></a>
							</td>
							<td>
								<input type="text" class="form-control number" data-name="number" value="">
							</td>
							<td>
								<select class="form-control number" data-name="type">
									<option value="work" selected="selected"><?php echo _('Work')?></option>
									<option value="home"><?php echo _('Home')?></option>
									<option value="cell"><?php echo _('Cell')?></option>
									<option value="other"><?php echo _('Other')?></option>
								</select>
							</td>
							<!--<td style="text-align: right;">
								<label for="smsenable1"><?php echo _('SMS')?>:</label>
							</td>
							<td>
								<div class="onoffswitch">
									<input type="checkbox" name="smsenable1" data-name="flag" class="onoffswitch-checkbox number" id="smsenable" <?php echo ($enabled) ? 'checked' : ''?>>
									<label class="onoffswitch-label" for="smsenable">
										<div class="onoffswitch-inner"></div>
										<div class="onoffswitch-switch"></div>
									</label>
								</div>
							</td>
							<td style="text-align: right;">
								<label for="faxenable1"><?php echo _('Fax')?>:</label>
							</td>
							<td>
								<div class="onoffswitch">
									<input type="checkbox" name="faxenable1" data-name="flag" class="onoffswitch-checkbox number" id="faxenable" <?php echo ($enabled) ? 'checked' : ''?>>
									<label class="onoffswitch-label" for="faxenable">
										<div class="onoffswitch-inner"></div>
										<div class="onoffswitch-switch"></div>
									</label>
								</div>
							</td>-->
						</tr>
						<?php $contact['numbers'] = is_array($contact['numbers']) ? $contact['numbers'] : array(); ?>
						<?php foreach($contact['numbers'] as $number) {?>
							<tr>
								<td>
									<a><i class="fa fa-ban fa-fw delete"></i></a>
								</td>
								<td>
									<input type="text" class="form-control number" data-name="number" value="<?php echo $number['number']?>">
								</td>
								<td>
									<select class="form-control number" data-name="type">
										<option value="work" <?php echo ($number['type'] == "work") ? 'selected' : ''?>><?php echo _('Work')?></option>
										<option value="home" <?php echo ($number['type'] == "home") ? 'selected' : ''?>><?php echo _('Home')?></option>
										<option value="cell" <?php echo ($number['type'] == "cell") ? 'selected' : ''?>><?php echo _('Cell')?></option>
										<option value="other" <?php echo ($number['type'] == "other") ? 'selected' : ''?>><?php echo _('Other')?></option>
									</select>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
				<button class="btn btn-default btn-xs add-additional" data-type="numbers"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Number')?></button>
			</div>
			<div class="form-group">
				<label>XMPP</label>
				<div class="xmpps additional">
					<table data-type="xmpps">
						<tr class="template">
							<td>
								<i class="fa fa-ban fa-fw delete"></i>
							</td>
							<td>
								<input type="text" class="form-control special" data-name="xmpp">
							</td>
						</tr>
						<?php $contact['xmpps'] = is_array($contact['xmpps']) ? $contact['xmpps'] : array(); ?>
						<?php foreach($contact['xmpps'] as $xmpp) {?>
							<tr>
								<td>
									<i class="fa fa-ban fa-fw delete"></i>
								</td>
								<td>
									<input type="text" class="form-control special" data-name="xmpp" value="<?php echo $xmpp['xmpp']?>">
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
				<button class="btn btn-default btn-xs add-additional" data-type="xmpps"><i class="fa fa-plus fa-fw"></i><?php echo _('Add XMPP')?></button>
			</div>
			<div class="form-group">
				<label><?php echo _('Email')?></label>
				<div class="emails additional">
					<table data-type="emails">
						<tr class="template">
							<td>
								<i class="fa fa-ban fa-fw delete"></i>
							</td>
							<td>
								<input type="text" class="form-control special" data-name="email">
							</td>
						</tr>
						<?php $contact['emails'] = is_array($contact['emails']) ? $contact['emails'] : array(); ?>
						<?php foreach($contact['emails'] as $email) {?>
							<tr>
								<td>
									<i class="fa fa-ban fa-fw delete"></i>
								</td>
								<td>
									<input type="text" class="form-control special" data-name="email" value="<?php echo $email['email']?>">
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
				<button class="btn btn-default btn-xs add-additional" data-type="emails"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Email')?></button>
			</div>
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
						<?php $contact['websites'] = is_array($contact['websites']) ? $contact['websites'] : array(); ?>
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
			<input type="hidden" id="mode" name="mode" value="<?php echo ($add) ? 'add' : 'edit'?>">
			<?php if($add) {?>
				<button id="addcontact" class="btn btn-default"><?php echo _('Add Contact')?></button>
			<?php } else { ?>
				<input type="hidden" id="id" name="id" value="<?php echo $contact['id']?>">
				<button id="deletecontact" class="btn btn-default"><i class="fa fa-trash-o"></i> <?php echo _('Delete Contact')?></button>
			<?php } ?>
		</form>
	</div>
</div>
