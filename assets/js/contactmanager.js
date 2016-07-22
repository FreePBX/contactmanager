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


/**
 * Drag/Drop/Upload Files
 */
$('#dropzone').on('drop dragover', function (e) {
	e.preventDefault();
});
$('#dropzone').on('dragleave drop', function (e) {
	$(this).removeClass("activate");
});
$('#dropzone').on('dragover', function (e) {
	$(this).addClass("activate");
});
var supportedRegExp = "png|jpg|jpeg";
$('#imageupload').fileupload({
	dataType: 'json',
	dropZone: $("#dropzone"),
	add: function (e, data) {
		//TODO: Need to check all supported formats
		var sup = "\.("+supportedRegExp+")$",
				patt = new RegExp(sup),
				submit = true;
		$.each(data.files, function(k, v) {
			if(!patt.test(v.name.toLowerCase())) {
				submit = false;
				alert(_("Unsupported file type"));
				return false;
			}
			var s = v.name.replace(/\.[^/.]+$/, "").replace(/\s+|'+|\"+|\?+|\*+/g, '-').toLowerCase();
			/*
			if(mohConflict(s)) {
				alert(sprintf(_("File '%s' will overwrite a file (%s) that already exists in this category"),v.name, s));
				submit = false;
				return false;
			}
			*/
		});
		if(submit) {
			$("#upload-progress .progress-bar").addClass("progress-bar-striped active");
			data.submit();
		}
	},
	drop: function () {
		$("#upload-progress .progress-bar").css("width", "0%");
	},
	dragover: function (e, data) {
	},
	change: function (e, data) {
	},
	done: function (e, data) {
		$("#upload-progress .progress-bar").removeClass("progress-bar-striped active");
		$("#upload-progress .progress-bar").css("width", "0%");

		if(data.result.status) {
			$("#dropzone img").attr("src","ajax.php?module=contactmanager&command=limage&temporary=1&name="+data.result.filename);
			$("#image").val(data.result.filename);
			$("#dropzone img").removeClass("hidden");
			$("#del-image").removeClass("hidden");
			$("#gravatar").prop('checked', false);
		} else {
			alert(data.result.message);
		}
	},
	progressall: function (e, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		$("#upload-progress .progress-bar").css("width", progress+"%");
	},
	fail: function (e, data) {
	},
	always: function (e, data) {
	}
});

$("#del-image").click(function(e) {
	e.preventDefault();
	e.stopPropagation();
	var img = $("#image").val(),
			data = {},
			id = $(this).data("entryid");
	if(id !== "") {
		data.id = id;
	} else if (img !== "") {
		data.img = img;
	} else {
		return;
	}
	$.post( "ajax.php?module=contactmanager&command=delimage", data, function( data ) {
		if(data.status) {
			$("#image").val("");
			$("#dropzone img").addClass("hidden");
			$("#dropzone img").attr("src","");
			$("#del-image").addClass("hidden");
			$("#gravatar").prop('checked', false);
		}
	});
});

$("#gravatar").change(function() {
	if($(this).is(":checked")) {
		var id = null,
				grouptype = $("#grouptype").val();
		switch(grouptype) {
			case "internal":
				id = $("#user").val();
			break;
		}
		$.post( "ajax.php?module=contactmanager&command=getgravatar", {id: id, grouptype: grouptype}, function( data ) {
			if(data.status) {
				$("#dropzone img").data("oldsrc",$("#dropzone img").attr("src"));
				$("#dropzone img").attr("src","ajax.php?module=contactmanager&command=limage&temporary=1&name="+data.filename);
				$("#image").data("old",$("#image").val());
				$("#image").val(data.filename);
				$("#dropzone img").removeClass("hidden");
				$("#del-image").removeClass("hidden");
			} else {
				alert(data.message);
				$("#gravatar").prop('checked', false);
			}
		});
	} else {
		var oldsrc = $("#dropzone img").data("oldsrc");
		if(typeof oldsrc !== "undefined" && oldsrc !== "") {
			$("#dropzone img").attr("src",oldsrc);
			$("#image").val($("#image").data("old"));
		} else {
			$("#image").val("");
			$("#dropzone img").addClass("hidden");
			$("#dropzone img").attr("src","");
			$("#del-image").addClass("hidden");
		}
	}
});
