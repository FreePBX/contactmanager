<div class="widget">
	<div class="row">
		<div class="col-md-3">
			<div class="group-list">
				<input type="hidden" id="group"/>
				<div class="group active" data-name="" data-group=""><a href="#" class="group-inner"><?php echo _('My Contacts')?><span class="badge"><?php echo isset($total) ? $total : 0?></span></a></div>
				<?php foreach($groups as $g) {?>
					<div class="group" data-name="<?php echo $g['name']?>" data-group="<?php echo $g['id']?>"><a href="#" class="group-inner"><?php echo $g['name']?><span class="badge"><?php echo isset($g['count']) ? $g['count'] : 0?></span></a></div>
				<?php }?>
			</div>
			<div class="group-actions">
				<div class="group-action addgroup"><a href="#" class="group-inner"><?php echo _('Add Group')?><span class="badges"><i class="fa fa-plus"></i></span></a></div>
			</div>
		</div>
		<div class="col-md-9">
			<div id="contacts-toolbar">
				<?php if(!isset($readonly) || !$readonly) { ?>
					<button class="btn btn-danger deletegroup" disabled>
						<i class="fa fa-trash"></i> <span><?php echo _('Delete Group')?></span>
					</button>
					<button class="btn btn-default addcontact" disabled>
						<i class="fa fa-plus"></i> <span><?php echo _('Add Contact')?></span>
					</button>
				<?php } ?>
			</div>
			<table class="contacts-grid"
				data-url="ajax.php?module=contactmanager&amp;command=grid"
				data-cache="false"
				data-toolbar="#contacts-toolbar"
				data-cookie="true"
				data-cookie-id-table="ucp-contacts-table"
				data-maintain-selected="true"
				data-show-columns="true"
				data-show-toggle="true"
				data-toggle="table"
				data-pagination="true"
				data-search="true"
				data-sort-order="asc"
				data-sort-name="displayname"
				data-show-refresh="true"
				data-silent-sort="false"
				data-mobile-responsive="true"
				data-check-on-init="true"
				class="table table-hover">
				<thead>
					<tr>
						<th data-field="displayname" data-sortable="true"><?php echo _("Display Name")?></th>
						<th data-field="fname" data-sortable="true"><?php echo _("First Name")?></th>
						<th data-field="lname" data-sortable="true"><?php echo _("Last Name")?></th>
						<th data-field="title" data-sortable="true"><?php echo _("Title")?></th>
						<th data-field="company" data-sortable="true"><?php echo _("Company")?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
