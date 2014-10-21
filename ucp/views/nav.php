<div class="col-md-2">
	<div class="contact-group-list">
		<div class="contact-group <?php echo ('mycontacts' == $activeList) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=faxpro&amp;view=send" class="contact-group-inner"><?php echo _('My Contacts')?><span class="badge">42</span></a></div>
		<div class="contact-group sub <?php echo ('mycontacts1' == $activeList) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=faxpro&amp;view=send" class="contact-group-inner"><?php echo _('Group 1')?><span class="badge">21</span></a></div>
		<div class="contact-group sub <?php echo ('mycontacts1' == $activeList) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=faxpro&amp;view=send" class="contact-group-inner"><?php echo _('Group 2')?><span class="badge">21</span></a></div>
	</div>
</div>
