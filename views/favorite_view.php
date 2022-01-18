<link rel="stylesheet" href="/admin/modules/contactmanager/assets/css/contactmanager.css" type="text/css">
<?php 
	function showMiddle() {
		$ret = "<span class='col-sm-2 middle'>\n";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='All Left' data-cmd='allleft'> <i class='fa fa-arrow-left' ></i></button><br/>";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='Swap' data-cmd='swap'> <i class='fa fa-arrows-h'></i></button><br/>";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='All Right' data-cmd='allright'><i class='fa fa-arrow-right'></i></button><br/>";
		$ret .= "</span>\n";
		return $ret;
	}
?>
<input type="hidden" id="favorite_contact_edit_enabled" value="<?php echo isset($favoriteContactEditEnabled) ? $favoriteContactEditEnabled : false; ?>" />
<div class="tab-content display fav-tab">
    <div id='users' class='tab-pane active'>
        <div class="row">
			<?php $contactClass = (!isset($favoriteContactEditEnabled) || (isset($favoriteContactEditEnabled) && $favoriteContactEditEnabled)) ? 'col-sm-5' : 'col-sm-6' ?>
            <fieldset class='contact_list ui-sortable left <?php echo $contactClass; ?>' id='included_contacts' data-otherid='excluded_contacts'>
                <legend> <?php echo _("Include") ?> </legend>
                <?php
                foreach ($includedContacts as $contact) {
                    echo "<span class='dragitem' data-contactId='" . $contact['uid'] . "'>" . $contact['displayname'] . " (" . reset($contact['numbers'])['number'] . ")" . "</span>\n";
                }
                ?>
            </fieldset>
			<?php if (!isset($favoriteContactEditEnabled) || (isset($favoriteContactEditEnabled) && $favoriteContactEditEnabled)) { ?>
            	<?php echo showMiddle(); ?>
			<?php } ?>
            <fieldset class='contact_list ui-sortable right <?php echo $contactClass; ?>' id='excluded_contacts' data-otherid='included_contacts'>
                <legend> <?php echo _("Exclude") ?> </legend>
                <?php
                foreach ($excludedContacts as $contact) {
                    echo "<span class='dragitem' data-contactId='" . $contact['uid'] . "'>" . $contact['displayname'] . " (" . reset($contact['numbers'])['number'] . ")" . "</span>\n";
                }
                ?>
            </fieldset>
        </div>
    </div>
	<?php if (isset($favoriteContactEditEnabled) && $favoriteContactEditEnabled) { ?>
		<div class="fav-save-bar">
			<button type="button" class="btn btn-primary fav-save" id="save_favorites">Save changes</button>
		</div>
	<?php } ?>
</div>
<script type='text/javascript'>

$(document).ready(function() {
	// Make everything draggable.
	<?php if (isset($favoriteContactEditEnabled)) { ?>
		var elem = $(".favorite-div");
		var h = parseInt( elem.parents(".widget-content").outerHeight()) - (parseInt(elem.find(".contact_list").offset().top));
		elem.find(".contact_list").height(parseInt(h));
	<?php } else { ?>
		var elem = $(".fav-tab");
		var h = parseInt($( window ).height()) - (parseInt(elem.find(".contact_list").offset().top) + parseInt(elem.find(".contact_list legend").outerHeight(true)));
		elem.find(".contact_list").height(parseInt(h));
	<?php } ?>
	<?php if (!isset($favoriteContactEditEnabled) || (isset($favoriteContactEditEnabled) && $favoriteContactEditEnabled)) { ?>
		Sortable.create(included_contacts, {
			group: 'usr',
			multiDrag: true,
			selectedClass: "selected",
		});

		Sortable.create(excluded_contacts, {
			group: 'usr',
			multiDrag: true,
			selectedClass: "selected",
		});

		$(window).resize(function() { set_height(); });
		function set_height() {
			var height = 0;
			$("#tabs>.tab:visible").height('auto').each(function(){
				height = $(this).height() > height ? $(this).height() : height;
			}).height(height);
		}
		
		// Enable 'Move All' buttons
		$('.toggle').click(function(e) {
			e.preventDefault();
			var cmd=$(this).data('cmd');
			var thistab = $('#users').children();
			var left = thistab.children('.left');
			var right = thistab.children('.right');
			if (cmd == 'allleft') {
				right.children('span').each(function() { $(this).appendTo(left); });
			} else if (cmd == 'allright') {
				left.children('span').each(function() { $(this).appendTo(right); });
			} else {
				oldleft = left.children('span');
				right.children('span').each(function() { $(this).appendTo(left); });
				oldleft.each(function() { $(this).appendTo(right); });
			}
		});
	<?php } ?>
});

</script>