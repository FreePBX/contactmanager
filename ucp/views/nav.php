<div class="col-md-2">
	<div class="contact-group-list">
		<div class="contact-group <?php echo ('mycontacts' == $activeList) ? 'active' : ''?>"><a cm-pjax href="?display=dashboard&amp;mod=contactmanager&amp;view=all" class="contact-group-inner"><?php echo _('My Contacts')?><span class="badge"><?php echo $total?></span></a></div>
		<div class="contact-group sub <?php echo ('addgroup' == $activeList) ? 'active' : ''?>"><a cm-pjax href="?display=dashboard&amp;mod=contactmanager&amp;view=addgroup" class="contact-group-inner"><?php echo _('Add Group')?><i class="fa fa-plus"></i></a></div>
		<?php foreach($groups as $group) { ?>
			<div class="contact-group sub <?php echo ($group['name'] == $activeList) ? 'active' : ''?>" data-name="<?php echo $group['name']?>"><a cm-pjax href="?display=dashboard&amp;mod=contactmanager&amp;view=group&amp;id=<?php echo $group['id']?>" class="contact-group-inner"><?php echo $group['name']?><span class="badge"><?php echo $group['count']?></span></a></div>
		<?php } ?>
	</div>
	<br/>
</div>
