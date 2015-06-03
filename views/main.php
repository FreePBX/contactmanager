<?php if (!empty($message)) { ?>
	<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
<?php } ?>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<?php echo $content?>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<?php echo $rnav?>
		</div>
	</div>
</div>
