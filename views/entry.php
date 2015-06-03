<?php
$userlist[''] = '';
foreach ($users as $u) {
	if ($entry['user'] == $u['id']) {
		$user = $u;
	}

	if ($u['displayname']) {
		$desc = $u['displayname'];
	} else if ($u['fname'] && $u['lname']) {
		$desc = $u['fname'] . ' ' . $u['lname'];
	} else if ($u['default_extension'] && $u['default_extension'] != 'none') {
		$desc = 'Ext. ' . $u['default_extension'];
	} else if ($u['description']) {
		$desc = $u['description'];
	}

	$userlist[$u['id']] = ($desc ? $desc . ' ' : '') . '(' . $u['username'] . ')';
}
?>
<script language="javascript">var users = <?php echo json_encode($users)?>, numbertypes = <?php echo json_encode($numbertypes)?>;</script>
<div class="fpbx-container">
	<div class="display full-border">
		<form name="entry" class="fpbx-submit" method="post" action="config.php?display=contactmanager" <?php if(isset($entry['id'])) {?>data-fpbx-delete="config.php?display=contactmanager&amp;group=<?php echo $group['id']?>&amp;entry=<?php echo $entry['id']?>&amp;action=delentry<?php }?>">
			<input type="hidden" name="group" id="group" value="<?php echo $group['id']?>">
			<input type="hidden" name="grouptype" id="grouptype" value="<?php echo $group['id']?>">
			<?php if(!empty($entry)) {?>
				<input type="hidden" name="entry" id="entry" value="<?php echo $entry['id']?>">
				<h1><a href="config.php?display=contactmanager&amp;action=showgroup&amp;group=<?php echo $group['id']?>"><?php echo $group['name']?></a> - <?php echo _("Edit Entry")?></h1>
			<?php } else { ?>
				<input type="hidden" name="entry" id="entry" value="">
				<h1><a href="config.php?display=contactmanager&amp;action=showgroup&amp;group=<?php echo $group['id']?>"><?php echo $group['name']?></a> - <?php echo _("Add Entry")?></h1>
			<?php } ?>
			<?php if($group['type'] == "internal") {?>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="user"><?php echo _('User')?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="user"></i>
									</div>
									<div class="col-md-9">
										<select name="user" id="user" class="form-control">
											<?php foreach($userlist as $key => $val) {?>
												<option value="<?php echo $key?>" <?php echo (!empty($entry['user']) && $entry['user'] == $key) ? "selected" : ""?>><?php echo $val?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="user-help" class="help-block fpbx-help-block"><?php echo _('A user from the User Management module')?></span>
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
									<label class="control-label" for="displayname"><?php echo _('Display Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="displayname"></i>
								</div>
								<div class="col-md-9"><input class="form-control" id="displayname" name="displayname" value="<?php echo $entry['displayname']?>" <?php echo !empty($user) ? 'placeholder="' . $user['displayname'] . '"' : ''?>></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="displayname-help" class="help-block fpbx-help-block"><?php echo _('Display Name (overrides Display Name from User Manager)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="fname"><?php echo _('First Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="fname"></i>
								</div>
								<div class="col-md-9"><input class="form-control" id="fname" name="fname" value="<?php echo $entry['fname']?>" <?php echo !empty($user) ? 'placeholder="' . $user['fname'] . '"' : ''?>></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="fname-help" class="help-block fpbx-help-block"><?php echo _('First Name (overrides First Name from User Manager)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="lname"><?php echo _('Last Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="lname"></i>
								</div>
								<div class="col-md-9"><input class="form-control" id="lname" name="lname" value="<?php echo $entry['lname']?>" <?php echo !empty($user) ? 'placeholder="' . $user['lname'] . '"' : ''?>></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="lname-help" class="help-block fpbx-help-block"><?php echo _('Last Name (overrides Last Name from User Manager)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="title"><?php echo _('Title')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="title"></i>
								</div>
								<div class="col-md-9"><input class="form-control" id="title" name="title" value="<?php echo $entry['title']?>" <?php echo !empty($user) ? 'placeholder="' . $user['title'] . '"' : ''?>></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="title-help" class="help-block fpbx-help-block"><?php echo _('Title (overrides Title from User Manager)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="company"><?php echo _('Company')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="displayname"></i>
								</div>
								<div class="col-md-9"><input class="form-control" id="company" name="company" value="<?php echo $entry['company']?>" <?php echo !empty($user) ? 'placeholder="' . $user['company'] . '"' : ''?>></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="company-help" class="help-block fpbx-help-block"><?php echo _('Company (overrides Company from User Manager)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="address"><?php echo _('Address')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="address"></i>
								</div>
								<div class="col-md-9">
									<textarea name="address" class="form-control"><?php echo !empty($entry['address']) ? $entry['address'] : ""?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="address-help" class="help-block fpbx-help-block"><?php echo _('Address')?></span>
					</div>
				</div>
			</div>
			<?php switch ($group['type']) {
						case "external":?>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="numbers"><?php echo _('Numbers')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="numbers"></i>
											</div>
											<div class="col-md-9">
												<table id="numbers" class="items table table-striped">
													<?php $numcount = 0;
													$entry['numbers'] = !empty($entry['numbers']) ? $entry['numbers'] : array();
													foreach ($entry['numbers'] as $number) {?>
														<tr id="number_<?php echo $numcount?>">
															<td><a class="clickable" onclick="delNumber('<?php echo $numcount?>')"><i class="fa fa-ban fa-fw"></i></a></td>
															<td>
																<input type="text" class="form-control" name="number[<?php echo $numcount?>]" value="<?php echo $number['number']?>">Ext.<input type="text" class="form-control" name="extension[<?php echo $numcount?>]" value="<?php echo $number['extension']?>">
																<select class="form-control" name="numbertype[<?php echo $numcount?>]">
																	<?php foreach($numbertypes as $key => $val) {?>
																		<option value="<?php echo $key?>"><?php echo $val?></option>
																	<?php } ?>
																</select>
															</td>
															<td><input type="checkbox" name="sms[<?php echo $numcount?>]" value="1" <?php echo in_array('sms', $number['flags']) ? "checked" : ""?>>SMS<br><input type="checkbox" name="fax[<?php echo $numcount?>]" value="1" <?php echo in_array('fax', $number['flags']) ? "checked" : ""?>>FAX</td>
														</tr>
													<?php } ?>
												</table>
												<a class="clickable" onclick="addNumber()"><i class="fa fa-plus fa-fw"></i>Add Number</a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="numbers-help" class="help-block fpbx-help-block"><?php echo _('A list of numbers belonging to this entry')?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="xmpps"><?php echo _('XMPP')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="xmpps"></i>
											</div>
											<div class="col-md-9">
												<table id="xmpps" class="items table table-striped">
													<?php $numcount = 0;
													$entry['xmpps'] = !empty($entry['xmpps']) ? $entry['xmpps'] : array();
													foreach ($entry['xmpps'] as $number) {?>
														<tr id="xmpp_<?php echo $numcount?>">
															<td><a class="clickable" onclick="delXMPP(' . $numcount . ')"><i class="fa fa-ban fa-fw"></i></a></td>
															<td><input type="text" class="form-control" name="xmpp[<?php echo $numcount?>]" value="<?php echo $number['xmpp']?>"></td>
														</tr>
													<?php } ?>
												</table>
												<a class="clickable" onclick="addXMPP()"><i class="fa fa-plus fa-fw"></i><?php echo _('Add XMPP')?></a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="xmpps-help" class="help-block fpbx-help-block"><?php echo _('A list of XMPP addresses belonging to this entry')?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="emails"><?php echo _('Email')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="emails"></i>
											</div>
											<div class="col-md-9">
												<table id="emails" class="items table table-striped">
													<?php $numcount = 0;
													$entry['emails'] = !empty($entry['emails']) ? $entry['emails'] : array();
													foreach ($entry['emails'] as $number) {?>
														<tr id="email_<?php echo $numcount?>">
															<td><a class="clickable" onclick="delEmail(' . $numcount . ')"><i class="fa fa-ban fa-fw"></i></a></td>
															<td><input type="text" class="form-control" name="email[<?php echo $numcount?>]" value="<?php echo $number['email']?>"></td>
														</tr>
													<?php } ?>
												</table>
												<a class="clickable" onclick="addEmail()"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Email')?></a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="emails-help" class="help-block fpbx-help-block"><?php echo _('A list of E-Mail addresses belonging to this entry')?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="websites"><?php echo _('Website')?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="websites"></i>
											</div>
											<div class="col-md-9">
												<table id="websites" class="items table table-striped">
													<?php $numcount = 0;
													$entry['websites'] = !empty($entry['websites']) ? $entry['websites'] : array();
													foreach ($entry['websites'] as $number) {?>
														<tr id="website_<?php echo $numcount?>">
															<td><a class="clickable" onclick="delWebsite(' . $numcount . ')"><i class="fa fa-ban fa-fw"></i></a></td>
															<td><input type="text" class="form-control" name="website[<?php echo $numcount?>]" value="<?php echo $number['website']?>"></td>
														</tr>
													<?php } ?>
												</table>
												<a class="clickable" onclick="addWebsite()"><i class="fa fa-plus fa-fw"></i><?php echo _('Add Website')?></a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="websites-help" class="help-block fpbx-help-block"><?php echo _('A list of websites belonging to this entry')?></span>
								</div>
							</div>
						</div>
			<?php break;
			}?>
		</form>
	</div>
</div>
