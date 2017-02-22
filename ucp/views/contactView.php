<div class="col-md-12">
	<div class="contact-container">
		<?php if(!empty($contact['image'])) { ?>
			<div class="contact-image pull-right">
				<img class="" src="?quietmode=1&amp;module=Contactmanager&amp;command=limage&amp;entryid=<?php echo $contact['id']?>&amp;time=<?php echo time()?>">
			</div>
		<?php } ?>
		<form role="form">
			<?php if(!empty($contact['displayname'])) {?>
				<div class="form-group">
					<label><?php echo ('Display Name')?></label><br/>
					<?php echo $contact['displayname'];?>
				</div>
			<?php } ?>
			<?php if(!empty($contact['fname'])) {?>
				<div class="form-group">
					<label><?php echo ('First Name')?></label><br/>
					<?php echo $contact['fname']?>
				</div>
			<?php } ?>
			<?php if(!empty($contact['lname'])) {?>
				<div class="form-group">
					<label><?php echo ('Last Name')?></label><br/>
					<?php echo $contact['lname']?>
				</div>
			<?php } ?>
			<?php if(!empty($contact['title'])) {?>
				<div class="form-group">
					<label><?php echo ('Title')?></label><br/>
					<?php echo $contact['title']?>
				</div>
			<?php } ?>
			<?php if(!empty($contact['company'])) {?>
				<div class="form-group">
					<label><?php echo ('Company')?></label><br/>
					<?php echo $contact['company']?>
				</div>
			<?php } ?>
			<?php if(!empty($contact['numbers'])) {?>
				<div class="form-group">
					<label><?php echo ('Numbers')?></label><br/>
					<ul>
					<?php foreach($contact['numbers'] as $number) { if(trim($number['number']) == "" || $number['number'] == "none") {continue;}?>
						<li data-flag='<?php echo json_encode($number['flags'])?>'><strong><?php echo $number['type']?>:</strong> <span class="clickable" data-type="number" data-primary="<?php echo $number['primary']?>"><?php echo $number['number']?></span>
						<?php foreach($number['flags'] as $flag) {?>
							(<?php echo $flag?>)
						<?php } ?>
						<?php if($featurecode['enabled'] && trim($number['speeddial']) != "") { ?>
							<b><?php echo ('Speed Dial')?>:</b> <?php echo $featurecode['code'].$number['speeddial']?>
						<?php } ?>
						</li>
					<?php } ?>
					</ul>
				</div>
			<?php } ?>
			<?php if(!empty($contact['xmpps'])) {?>
				<div class="form-group">
					<label>XMPP</label><br/>
					<ul>
					<?php foreach($contact['xmpps'] as $number) {?>
						<li><span class="clickable" data-type="xmpp" data-primary="xmpp"><?php echo $number['xmpp']?></span></li>
					<?php } ?>
					</ul>
				</div>
			<?php } ?>
			<?php if(!empty($contact['emails']) && !empty($contact['emails'][0]['email'])) { ?>
				<div class="form-group">
					<label><?php echo ('Emails')?></label><br/>
					<ul>
					<?php foreach($contact['emails'] as $number) {?>
						<li data-type="email"><a href="mailto:<?php echo $number['email']?>" target="_blank"><?php echo $number['email']?></a></li>
					<?php } ?>
					</ul>
				</div>
			<?php } ?>
			<?php if(!empty($contact['websites'])) {?>
				<div class="form-group">
					<label><?php echo ('Website')?></label><br/>
					<ul>
					<?php foreach($contact['websites'] as $number) {?>
						<li data-type="website"><a href="<?php echo preg_match("/^http/i",$number['website']) ? $number['website'] : "http://".$number['website']?>" target="_blank"><?php echo $number['website']?></a></li>
					<?php } ?>
					</ul>
				</div>
			<?php } ?>
		</form>
	</div>
</div>
