$(function() {
	$("form[name=entry]").submit(function(event) {
		if ($("select[name=user]").val() === "") {
			warnInvalid($("select[name=user]"),_("An entry must have a user"));
			event.preventDefault();
		}
		$numbers = $("#numbers input[name^='number[']");
		if ($numbers.length > 0 && $numbers.size() < 1) {
			alert("An entry must have a number.");
			event.preventDefault();
		} else {
			$numbers.each(function(index) {
				if ($(this).val() === "") {
					warnInvalid($(this), _("Number cannot be blank."));
					event.preventDefault();
					return false;
				}
			});
		}

		$xmpps = $("#xmpps input[name^='xmpp[']");
		if ($xmpps.length > 0 && $xmpps.size() > 0) {
			$xmpps.each(function(index) {
				if ($(this).val() == "") {
					warnInvalid($(this), _("XMPP address cannot be blank."));
					event.preventDefault();
					return false;
				}
			});
		}

		$emails = $("#emails input[name^='email[']");
		if ($emails.length > 0 && $emails.size() > 0) {
			$emails.each(function(index) {
				if ($(this).val() == "") {
					warnInvalid($(this), _("E-Mail address cannot be blank."));
					event.preventDefault();
					return false;
				}
			});
		}

		$websites = $("#websites input[name^='website[']");
		if ($websites.length > 0 && $websites.size() > 0) {
			$websites.each(function(index) {
				if ($(this).val() == "") {
					warnInvalid($(this), _("Website cannot be blank."));
					event.preventDefault();
					return false;
				}
			});
		}
	});
	$("select[name=user]").change(function(event) {
		/* Reset placeholders and values. */
		$("[name=displayname]").attr("placeholder", "").val("");
		$("[name=fname]").attr("placeholder", "").val("");
		$("[name=lname]").attr("placeholder", "").val("");
		$("[name=title]").attr("placeholder", "").val("");
		$("[name=company]").attr("placeholder", "").val("");
		$("[name=address]").attr("placeholder", "").val("");

		users.forEach(function(user) {
			if (user.id == $(event.target).val()) {
				$("[name=displayname]").attr("placeholder", user.displayname);
				$("[name=fname]").attr("placeholder", user.fname);
				$("[name=lname]").attr("placeholder", user.lname);
				$("[name=title]").attr("placeholder", user.title);
				$("[name=company]").attr("placeholder", user.company);
			}
		});

	});
});

function addNumber() {
	lastid = $("#numbers tr[id^=number_]:last-child").attr("id");
	if (lastid) {
		index = lastid.substr(7); // Everything after "number_"
		index++;
	} else {
		index = 0;
	}

	row = "<tr id=\"number_" + index + "\">";
	row+= "<td>";
	row+= "<a class=\"clickable\" onclick=\"delNumber(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
	row+= "</td>";
	row+= "<td>";
	row+= "<input class=\"form-control\" type=\"text\" name=\"number[" + index + "]\" value=\"\"/>";
	row+= "<br>"+_('Ext.')+"<input class=\"form-control\" type=\"text\" name=\"extension[" + index + "]\" value=\"\"/>";
	row+= "<select class=\"form-control\" name=\"numbertype[" + index + "]\">";
	$.each(numbertypes, function(k,v) {
		row+= "<option value=\"" + k + "\">" + v + "</option>";
	});
	row+= "</select>";
	row+= "</td>";
	row+= "<td>";
	row+= "<input type=\"checkbox\" name=\"sms[" + index + "]\" value=\"1\"/>" + _('SMS');
	row+= "<br>";
	row+= "<input type=\"checkbox\" name=\"fax[" + index + "]\" value=\"1\"/>" + _('FAX');
	row+= "</td>";

	$("#numbers").append(row);
}

function delNumber(index) {
	$("#number_" + index).remove();
}

function addXMPP() {
	lastid = $("#xmpps tr[id^=\"xmpp_\"]:last-child").attr("id");
	if (lastid) {
		index = lastid.substr(5); // Everything after "xmpp_"
		index++;
	} else {
		index = 0;
	}

	row = "<tr id=\"xmpp_" + index + "\">";
	row+= "<td>";
	row+= "<a class=\"clickable\" onclick=\"delXMPP(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
	row+= "</td>";
	row+= "<td>";
	row+= "<input class=\"form-control\" type=\"text\" name=\"xmpp[" + index + "]\" value=\"\"/>";
	row+= "</td>";

	$("#xmpps").append(row);
}

function delXMPP(index) {
	$("#xmpp_" + index).remove();
}

function addEmail() {
	lastid = $("#emails tr[id^=\"email_\"]:last-child").attr("id");
	if (lastid) {
		index = lastid.substr(6); // Everything after "email_"
		index++;
	} else {
		index = 0;
	}

	row = "<tr id=\"email_" + index + "\">";
	row+= "<td>";
	row+= "<a class=\"clickable\" onclick=\"delEmail(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
	row+= "</td>";
	row+= "<td>";
	row+= "<input class=\"form-control\" type=\"text\" name=\"email[" + index + "]\" value=\"\"/>";
	row+= "</td>";

	$("#emails").append(row);
}

function delEmail(index) {
	$("#email_" + index).remove();
}

function addWebsite() {
	lastid = $("#websites tr[id^=\"website_\"]:last-child").attr("id");
	if (lastid) {
		index = lastid.substr(8); // Everything after "website_"
		index++;
	} else {
		index = 0;
	}

	row = "<tr id=\"website_" + index + "\">";
	row+= "<td>";
	row+= "<a class=\"clickable\" onclick=\"delWebsite(" + index + ")\"><i class=\"fa fa-ban fa-fw\"></i></a>";
	row+= "</td>";
	row+= "<td>";
	row+= "<input class=\"form-control\" type=\"text\" name=\"website[" + index + "]\" value=\"\"/>";
	row+= "</td>";

	$("#websites").append(row);
}

function delWebsite(index) {
	$("#website_" + index).remove();
}
