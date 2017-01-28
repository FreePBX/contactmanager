<?php if (!empty($message)) { ?>
	<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
<?php } ?>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<?php echo $content?>
		</div>
	</div>
</div>
