<div class="fpbx-container">
	<ul class="nav nav-tabs" role="tablist">
		<?php foreach($types as $type => $data) {?>
			<li data-name="<?php echo $type?>" class="change-tab <?php echo $type == "internal" ? "active" : ""?>"><a href="#<?php echo $type?>" aria-controls="<?php echo $type?>" role="tab" data-toggle="tab"><?php echo $data['name']?></a></li>
		<?php } ?>
	</ul>
	<div class="tab-content display">
		<?php foreach($types as $type => $data) {?>
			<div id="<?php echo $type?>" class="tab-pane <?php echo $type == "internal" ? "active" : ""?>">
				<?php if(!empty($groups[$type])) { ?>
					<div class="nav-container">
						<div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
						<div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
						<div class="wrapper">
							<ul class="nav nav-tabs list" role="tablist">
							<?php foreach($groups[$type] as $k => $group) { ?>
								<li data-name="<?php echo $type?>-<?php echo $group['id']?>" class="change-tab <?php echo $k== 0 ? "active" : ""?>"><a href="#<?php echo $type?>-<?php echo $group['id']?>" aria-controls="<?php echo $type?>-<?php echo $group['id']?>" role="tab" data-toggle="tab"><?php echo $group['name']?></a></li>
							<?php } ?>
							</ul>
						</div>
					</div>
					<div class="tab-content display">
						<?php foreach($groups[$type] as $k => $group) {?>
							<div id="<?php echo $type?>-<?php echo $group['id']?>" class="tab-pane <?php echo $k== 0 ? "active" : ""?>">
								<div id="toolbar-<?php echo $type?>-<?php echo $group['id']?>">
									<?php if($type != "userman") {?>
										<a class="btn btn-primary" href="?display=contactmanager&amp;action=addentry&amp;group=<?php echo $group['id']?>"><i class="fa fa-plus"></i> <?php echo _("Add User")?></a>
										<a class="btn btn-primary" href="?type=tool&amp;display=contactmanager&amp;action=export&amp;group=<?php echo $group['id']?>&amp;quietmode=1"><i class="fa fa-upload"></i> <?php echo _('Export CSV')?></a>
										<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#upload-<?php echo $group['id']?>"><i class="fa fa-download"></i> <?php echo _('Import CSV')?></button>
									<?php } ?>
									<a class="btn btn-primary" href="?display=contactmanager&amp;action=showgroup&amp;group=<?php echo $group['id']?>"><i class="fa fa-pencil"></i> <?php echo _('Edit Group')?></a>
									<a class="btn btn-primary" href="?display=contactmanager&amp;action=delgroup&amp;group=<?php echo $group['id']?>"><i class="glyphicon glyphicon-remove"></i> <?php echo _('Delete Group')?></a>
								</div>
								<table id="<?php echo $type?>-<?php echo $group['id']?>-grid" data-url="ajax.php?module=contactmanager&amp;command=grid&amp;group=<?php echo $group['id']?>" data-cache="false" data-toolbar="#toolbar-<?php echo $type?>-<?php echo $group['id']?>" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped">
									<thead>
										<tr>
											<?php foreach($data['fields'] as $id => $name) {?>
												<th data-field="<?php echo $id?>" <?php if($id != "actions") {?>data-sortable="true"<?php } ?>><?php echo $name?></th>
											<?php } ?>
										</tr>
									</thead>
								</table>
							</div>
						<?php } ?>
						<div class="modal fade" id="upload-<?php echo $group['id']?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
										<h4 class="modal-title" id="myModalLabel">Import CSV</h4>
									</div>
									<div class="modal-body">
										<form id="form-<?php echo $group['id']?>" action="/admin/config.php?display=contactmanager&amp;action=showgroup&amp;group=<?php echo $group['id']?>" method="post" accept-charset="" enctype="multipart/form-data">
											<input type="hidden" name="group" value="<?php echo $group['id']?>">
											<input type="hidden" name="action" value="import">
											<span class="btn btn-default btn-file">
												<?php echo _('Browse')?> <input type="file" class="form-control" name="csv">
											</span>
											<span class="filename"></span><br><br>
											<?php echo sprintf(_("Note: Max file size is %s"),$file['upload'])?>
										</form>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
										<button type="button" class="btn btn-primary" onclick="document.getElementById('form-<?php echo $group['id']?>').submit();">Upload</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php } else { ?>
					<?php echo _('No Groups for this type')?>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</div>
