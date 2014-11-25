<div class="col-md-10">
	<div class="row">
		<div class="col-sm-8">
			<?php echo $pagnation;?>
		</div>
		<div class="col-sm-4">
			<div class="input-group">
				<input type="text" class="form-control" id="search-text" placeholder="<?php echo _('Search')?>" value="<?php echo $search?>">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" id="search-btn">Go!</button>
				</span>
			</div>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-bordered cdr-table">
			<thead>
				<tr>
					<th><?php echo _('Display Name')?></th>
					<th><?php echo _('First Name')?></th>
					<th><?php echo _('Last Name')?></th>
				</tr>
			</thead>
			<?php foreach($contacts as $contact) {?>
				<tr>
					<td><?php echo $contact['displayname'];?></td>
					<td><?php echo $contact['fname'];?></td>
					<td><?php echo $contact['lname'];?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
	<?php echo $pagnation;?>
</div>
