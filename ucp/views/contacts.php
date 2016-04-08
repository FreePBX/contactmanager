<div class="col-md-10">
		<div id="contacts-toolbar">
			<?php if(!isset($_REQUEST['view']) || (isset($_REQUEST['view']) && $_REQUEST['view'] == "all")) { ?>
				<a cm-pjax="" href="?display=dashboard&amp;mod=contactmanager&amp;view=addgroup" class="btn btn-default"><i class="fa fa-plus"></i> Add Group</a>
			<?php } ?>
			<?php if(isset($readonly) && !$readonly && $_REQUEST['view'] == "group") { ?>
				<a cm-pjax="" href="?display=dashboard&amp;mod=contactmanager&amp;view=addcontact&amp;group=<?php echo $_REQUEST['id']?>" class="btn btn-default"><i class="fa fa-plus"></i> Add New Contact</a>
				<button id="deletegroup" class="btn btn-danger pull-right" data-id="<?php echo $_REQUEST['id']?>"><i class="fa fa-trash-o"></i> Delete This Group</button>
			<?php } ?>
		</div>
		<table id="contacts-grid"
					data-url="index.php?quietmode=1&amp;module=contactmanager&amp;command=grid&amp;id=<?php echo !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0?>"
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
					data-min-width="992"
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
