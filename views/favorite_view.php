<link rel="stylesheet" href="/admin/modules/contactmanager/assets/css/contactmanager.css" type="text/css">
<?php 
	function showMiddle() {
		$ret = "<span class='col-sm-2 middle'>\n";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='" . _("Move selected items to the left") . "' data-cmd='allleft'> <i class='fa fa-arrow-left' ></i></button><br/>";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='" . _("Swap items") . "' data-cmd='swap'> <i class='fa fa-arrows-h'></i></button><br/>";
		$ret .= "<button class='btn toggle' data-toggle='tooltip' title='" . _("Move selected items to the right") . "' data-cmd='allright'><i class='fa fa-arrow-right'></i></button><br/>";
		$ret .= "</span>\n";
		return $ret;
	}
?>
<?php if (isset($isUCP)) { ?>
	<input type="hidden" id="widget_content_height" value="0"/>
<?php } ?>
<div class="tab-content display fav-tab">
    <div id='users' class='tab-pane active'>
        <div class="row">
            <fieldset class='contact_list ui-sortable left col-sm-5' id='included_contacts' data-otherid='excluded_contacts'>
				<legend> <?php echo _("Include") ?> </legend>
                <?php
                foreach ($includedContacts as $contact) {
                    echo "<span class='dragitem' data-contactId='" . $contact['uid'] . "'>" . $contact['displayname'] . " (" . reset($contact['numbers'])['number'] . ")" . "</span>\n";
                }
                ?>
            </fieldset>
			<?php echo showMiddle(); ?>
            <fieldset class='contact_list ui-sortable right col-sm-5' id='excluded_contacts' data-otherid='included_contacts'>
				<legend> <?php echo _("Exclude") ?> </legend>
				<?php
                foreach ($excludedContacts as $contact) {
					echo "<span class='dragitem' data-contactId='" . $contact['uid'] . "'>" . $contact['displayname'] . " (" . reset($contact['numbers'])['number'] . ")" . "</span>\n";
                }
                ?>
            </fieldset>
        </div>
    </div>
	<div class="fav-save-bar">
		<button type="button" class="btn btn-primary fav-save" id="save_favorites">Save changes</button>
	</div>
</div>
<script type='text/javascript'>

$(document).ready(function() {
	// Make everything draggable.
	<?php if (isset($isUCP)) { ?>		
		var elem = $(".favorite-div");
		var padding1 = parseInt(elem.outerHeight(true) - elem.height());
		var padding2 = parseInt(elem.find(".fav-tab").outerHeight(true) - elem.find(".fav-tab").height());
		var padding3 = parseInt(elem.find(".fav-tab #users").outerHeight(true) - elem.find(".fav-tab #users").height());
		var buttonHeight = parseInt(elem.find(".fav-save-bar").outerHeight(true));
		var rowHeight = parseInt($("#widget_content_height").val());
		var h = rowHeight - (padding1+padding2+padding3+buttonHeight);

		var h1 = 0;
		elem.find("#included_contacts > span").each(function(){
			h1 += $(this).outerHeight(true);
		});
		var h2 = 0;
		elem.find("#excluded_contacts > span").each(function(){
			h2 += $(this).outerHeight(true);
		});
		var legendHeigh = parseInt(elem.find(".fav-tab legend").outerHeight(true));
		var contactListHeight = (parseInt(h1) > parseInt(h2) ? parseInt(h1) : parseInt(h2)) + legendHeigh;

		h = h > contactListHeight ? contactListHeight : h;
		elem.find(".contact_list").height(parseInt(h));
	<?php } else { ?>
		var elem = $(".fav-tab");
		var h = parseInt($( window ).height()) - (parseInt(elem.find(".contact_list").offset().top) + parseInt(elem.find(".contact_list legend").outerHeight(true)));
		elem.find(".contact_list").height(parseInt(h));
	<?php } ?>
	Sortable.create(included_contacts, {
		group: 'usr',
		multiDrag: true,
		selectedClass: "selected",
		avoidImplicitDeselect: true
	});

	Sortable.create(excluded_contacts, {
		group: 'usr',
		multiDrag: true,
		selectedClass: "selected",
		avoidImplicitDeselect: true
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
			right.children('span.selected').each(function() { $(this).appendTo(left); });
		} else if (cmd == 'allright') {
			left.children('span.selected').each(function() { $(this).appendTo(right); });
		} else {
			oldleft = left.children('span');
			right.children('span').each(function() { $(this).appendTo(left); });
			oldleft.each(function() { $(this).appendTo(right); });
		}
	});
});

</script>