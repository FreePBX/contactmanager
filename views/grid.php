<h1><?php echo _("Contact Manager")?></h1>
<div class="fpbx-container">
	<ul class="nav nav-tabs" role="tablist">
		<?php foreach($types as $type => $data) { ?>
			<li data-name="<?php echo $type?>" class="change-tab <?php echo $type == "internal" ? "active" : ""?>"><a href="#<?php echo $type?>" aria-controls="<?php echo $type?>" role="tab" data-toggle="tab"><?php echo $data['name']?></a></li>
		<?php } ?>
	</ul>
	<div class="tab-content display">
		<?php foreach($types as $type => $data) {?>
			<div id="<?php echo $type?>" class="tab-pane <?php echo $type == "internal" ? "active" : ""?>">
				<a href="?display=contactmanager&amp;action=addgroup" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _("Add New Group")?></a>
				<?php if(!empty($groups[$type])) { ?>
					<div class="nav-container">
						<div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
						<div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
						<div class="wrapper">
							<ul class="nav nav-tabs list" role="tablist" style="min-width: 10000px;">
							<?php foreach($groups[$type] as $k => $group) { 
									$_Owner 	= FreePBX::Userman()->getUserByID($group["owner"]);
									$owner 		= '<i class="fa fa-users" ></i>';
									if(!empty($_Owner)){
										$owner 	= '<i class="fa fa-user" ></i>'; 
									}
							?>
								
								<li data-name="<?php echo $type?>-<?php echo $group['id']?>" class="change-tab <?php echo $k== 0 ? "active" : ""?>"><a href="#<?php echo $type?>-<?php echo $group['id']?>" aria-controls="<?php echo $type?>-<?php echo $group['id']?>" role="tab" data-toggle="tab"><?php echo htmlentities($group['name'])?> <?php echo $owner?></a></li>
							<?php } ?>
							</ul>
						</div>
					</div>
					<div class="tab-content display">
						<?php foreach($groups[$type] as $k => $group) { 
									$_Owner 	= FreePBX::Userman()->getUserByID($group["owner"]);
									$_Group 	= FreePBX::Userman()->getGroupsByID($group["owner"]);
									$_Gowner 	= FreePBX::Userman()->getGroupByGID($_Group[0]);
									$owner 		= "";
									if(!empty($_Owner)){
										$owner 	=	'<div class="alert alert-info" role="alert"><strong>'._("Group").'</strong> > '.$_Gowner["groupname"]."<br><strong>"._("Owner").'</strong> > '.$_Owner["username"].' - '.$_Owner["default_extension"].'</div>';
									}						
						?>
							<div id="<?php echo $type?>-<?php echo $group['id']?>" class="tab-pane <?php echo $k== 0 ? "active" : ""?>">
							
								<?php echo $owner?>
								<div id="toolbar-<?php echo $type?>-<?php echo $group['id']?>">
									<button id="remove-<?php echo $type?>" class="btn btn-danger btn-remove" data-type="<?php echo $type?>" disabled data-section="<?php echo $type?>-<?php echo $group['id']?>-grid">
                                        <i class="fa fa-user-times"></i> <span><?php echo _('Delete')?></span>
                                    </button>
									<?php if($type != "internal") {?>
										<a class="btn btn-primary" href="?display=contactmanager&amp;action=addentry&amp;group=<?php echo $group['id']?>"><i class="fa fa-plus"></i> <?php echo _("Add Contact")?></a>
									<?php } ?>
									<a class="btn btn-primary" href="?display=contactmanager&amp;action=showgroup&amp;group=<?php echo $group['id']?>"><i class="fa fa-pencil"></i> <?php echo _('Edit Group')?></a>
									<a class="btn btn-primary" href="?display=contactmanager&amp;action=delgroup&amp;group=<?php echo $group['id']?>"><i class="glyphicon glyphicon-remove"></i> <?php echo _('Delete Group')?></a>
								</div>
								<table id="<?php echo $type?>-<?php echo $group['id']?>-grid" data-url="ajax.php?module=contactmanager&amp;command=grid&amp;group=<?php echo $group['id']?>" data-cache="false" data-toolbar="#toolbar-<?php echo $type?>-<?php echo $group['id']?>" data-type="<?php echo $type?>" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped table-<?php echo $type?>">
									<thead>
										<tr>
											<th data-checkbox="true"></th>
											<?php foreach($data['fields'] as $id => $name) {?>
												<th data-field="<?php echo $id?>" <?php if($id != "actions") {?>data-sortable="true"<?php } ?>><?php echo $name?></th>
											<?php } ?>
										</tr>
									</thead>
								</table>
							</div>
						<?php } ?>
					</div>
				<?php } else { ?>
					<?php echo _('No Groups for this type')?>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</div>
