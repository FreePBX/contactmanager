<div class="col-md-2">
	<div class="contact-group-list">
		<div class="contact-group <?php echo ('mycontacts' == $activeList) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=faxpro&amp;view=send" class="contact-group-inner"><?php echo _('My Contacts')?><span class="badge"><?php echo $total?></span></a></div>
		<?php foreach($groups as $group) { ?>
			<div class="contact-group sub <?php echo ($group['name'] == $activeList) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=faxpro&amp;view=send" class="contact-group-inner"><?php echo $group['name']?><span class="badge"><?php echo $group['count']?></span></a></div>
		<?php } ?>
	</div>
</div>
