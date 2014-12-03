<style>
.indent-div {
	margin-left: 15px;
}
</style>
<table>
	<tr class="guielToggle" data-toggle_class="contactmanager">
		<td colspan="2" ><h4><span class="guielToggleBut">-  </span><?php echo _("Contact Manager")?></h4><hr></td>
	</tr>
	<tr>
		<td colspan="2">
			<div class="indent-div">
				<table>
					<tr class="contactmanager">
						<td><?php echo _('Show contact in Contact Manager')?></td>
						<td>
							<span class="radioset">
								<input type="radio" id="contactmanager1" name="contactmanager_show" value="true" <?php echo ($enabled) ? 'checked' : ''?>><label for="contactmanager1"><?php echo _('Yes')?></label>
								<input type="radio" id="contactmanager2" name="contactmanager_show" value="false" <?php echo (!$enabled) ? 'checked' : ''?>><label for="contactmanager2"><?php echo _('No')?></label>
							</span>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
