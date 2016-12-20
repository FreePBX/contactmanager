var ContactmanagerC = UCPMC.extend({
	init: function(UCP) {
		var cm = this;
		this.contacts = {};
		$(document).bind("staticSettingsFinished", function( event ) {
			if (cm.staticsettings.enabled) {
				cm.contacts = cm.staticsettings.contacts;
			}
		});
	},
	resize: function(widget_id) {
		$(".grid-stack-item[data-id='"+widget_id+"'] .contacts-grid").bootstrapTable('resetView',{height: $(".grid-stack-item[data-id='"+widget_id+"'] .widget-content").height()});
	},
	groupClick: function() {
		$(".group").removeClass("active");
		$(this).addClass("active");
		var group = $(this).data("group");

		if (group.length === 0) {
			$("#deletegroup").addClass("disabled");
			$("#addcontact").addClass("disabled");
		} else {
			$("#deletegroup").removeClass("disabled");
			$("#addcontact").removeClass("disabled");
		}
		$("#group").val(group);
		$('#contacts-grid').bootstrapTable('refresh', {url: 'index.php?quietmode=1&module=contactmanager&command=grid&group=' + group});
	},
	displayWidget: function(widget_id, dashboard_id) {
		var self = this;

		$(".grid-stack-item[data-id='"+widget_id+"'] .contacts-grid").one("post-body.bs.table", function() {
			setTimeout(function() {
				self.resize(widget_id);
			},250);
		});

		$(".group").click(self.groupClick);

		$(".grid-stack-item[data-id='"+widget_id+"'] .addgroup").click(function() {
			$.getJSON('index.php?quietmode=1&module=contactmanager&command=addgroupmodal', function(data){
				if (data.status === true){
					UCP.showDialog(_("Add Group"),data.message);
					$('#globalModal .modal-footer').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button type="button" class="btn btn-primary save">'+ _("Save changes")+'</button>');
				} else {
					UCP.showDialog(_("Add Group"),_("Error getting form"));
					$('#globalModal .modal-footer').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close"));
				}
			});
			$('#globalModal .save').one('click',function() {
				$.ajax({
					type: 'POST',
					url: 'index.php?quietmode=1&module=contactmanager&command=addgroup',
					data: $('#contactmanager-addgroup').serialize(),
					success: function (data) {
						$(".group-list").append('<div class="group" data-name="' + $("#groupname").val() + '" data-group="' + data.id + '"><a href="#" class="group-inner">' + $("#groupname").val() + '<span class="badge">0</span></a></div>');
						$(".group[data-group=" + data.id + "]").click(self.groupClick);
						UCP.closeDialog();
					}
				});
			});
		});

		$("#deletegroup").click(function(e) {
			e.preventDefault();
			if (confirm(_("Are you sure you want to delete this group and all of it's contacts?"))) {
				var group = $("#group").val();

				$.post( "?quietmode=1&module=contactmanager&command=deletegroup", { id: group }, function( data ) {
					if (data.status) {
						$(".group[data-group='" + group + "'").remove();

						$(".group[data-group='']").trigger("click");
					}
				});
			}
		});

		$("#addcontact").click(function(e) {
			e.preventDefault();

			var group = $("#group").val();
			var contact = { numbers: [] }, $this = this;

			$.getJSON('index.php?quietmode=1&module=contactmanager&command=addcontactmodal', function(data){
				if (data.status === true){
					$('#globalModalBody').html(data.message);
				} else {
					$('#globalModalBody').html('<h2>'+_("Error getting form")+'</h2>');
				}
			});
			$('#globalModalLabel').html('<h3>'+_("Add Contact")+'</h3>');
			$('#globalModalFooter').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button id="save" type="button" class="btn btn-primary">'+ _("Save changes")+'</button>');
			$("#globalModal").modal('show');
			$('#save').on('click',function() {
				$.ajax({
					type: 'POST',
					url: 'index.php?quietmode=1&module=contactmanager&command=addcontact',
					data: $('#contactmanager-addcontact').serialize(),
					success: function (data) {
						var group = $("#group").val();
						$('#contacts-grid').bootstrapTable('refresh', {url: 'index.php?quietmode=1&module=contactmanager&command=grid&group=' + group});

						$("#globalModal").modal('hide');
					}
				});
			});

/*
			$("form input").not(".special").each(function(i, v) {
				var item = $(v);
				contact[item.prop("id")] = item.val();
			});
			$(".numbers tr").filter(":visible").each(function(i, v) {
				var obj = {};
				obj.number = $(this).find("input[data-name='number']").val();
				obj.type = $(this).find("select[data-name='type']").val();
				contact.numbers.push(obj);
			});
			$("form input").filter(":visible").filter(".special").each(function(i, v) {
				var table = $(this).parents("table"), type = table.data("type"), data = [];
				table.find("tr").not(".template").find("input").each(function(i, v) {
					var obj = {};
					obj[$(this).data("name")] = $(this).val();
					data.push(obj);
				});
				contact[type] = data;
			});
			contact.image = $("#contactmanager_image").val();

			$("form input").prop("disabled", true);
			$(this).text(_("Adding..."));

			$(this).prop("disabled", true);
			$.post( "?quietmode=1&module=contactmanager&command=addcontact", { id: id, contact: contact }, function( data ) {
				if (data.status) {
						$.pjax({
							url: "?display=dashboard&mod=contactmanager&view=group&id=" + id,
							container: "#dashboard-content"
						});
				} else {
					$($this).prop("disabled", false);
				}
			});
*/
		});
	},
	poll: function(data) {
		var cm = this;
		if (data.enabled) {
			cm.contacts = data.contacts;
		}
	},
	contactClickInitiateCallTo: function(did) {
		window.location.replace("tel:" + did);
	},
	contactClickInitiateFacetime: function(did) {
		window.location.replace("facetime:" + did);
	},
	contactClickOptions: function(type) {
		if (type != "number" || false) {
			return false;
		}
		var options = [ { text: _("Call To"), function: "contactClickInitiateCallTo", type: "phone" }];
		if (navigator.appVersion.indexOf("Mac")!=-1) {
			options.push({ text: _("Facetime"), function: "contactClickInitiateFacetime", type: "phone" });
		}
		return options;
	},
	showActionDialog: function(type, text, p) {
		var options = "", count = 0, operation = [], primary = "";
		if (typeof type === "undefined" || typeof text === "undefined" ) {
			return;
		}

		primary = (typeof p !== "undefined") ? p : "";
		if(primary.indexOf(",") !=-1) {
			var primaries = primary.split(",");
		}
		if (type == "number") {
			text = text.replace(/\D/g, "");
		}
		$.each(modules, function( index, module ) {
			if (UCP.validMethod(module, "contactClickOptions")) {
				var o = UCP.Modules[module].contactClickOptions(type), selected = "";
				if (o !== false && Array.isArray(o)) {
					$.each(o, function(k, v) {
						if(typeof primaries !== "undefined") {
							if (primaries.indexOf(v.type) !=-1) {
								if(primaries.indexOf(v.type) === 0) {
									options = "<option data-function='" + v.function + "' data-module='" + module + "' " + selected + ">" + v.text + "</option>" + options;
								} else {
									options = options + "<option data-function='" + v.function + "' data-module='" + module + "' " + selected + ">" + v.text + "</option>";
								}
								v.module = module;
								operation = v;
								count++;
							}
						} else {
							if ((typeof v.type !== "undefined") && (v.type == primary)) {
								options = "<option data-function='" + v.function + "' data-module='" + module + "' " + selected + ">" + v.text + "</option>" + options;
								v.module = module;
								operation = v;
								count++;
							}
						}
					});
				}
			}
		});

		if (count === 0) {
			alert(_("There are no actions for this type"));
		} else if (count === 1) {
			if (UCP.validMethod(operation.module, operation.function)) {
				UCP.Modules[operation.module][operation.function](text);
			}
		} else if (count > 1) {
			UCP.showDialog(_("Select an Action"),
				"<select id=\"contactmanageraction\" class=\"form-control\">" + options + "</select><button class=\"btn btn-default\" id=\"initiateaction\" style=\"margin-left: 72px;\">Initiate</button>",
				115,
				250,
				function() {
					$("#initiateaction").click(function() {
						var func = $("#contactmanageraction option:selected").data("function"),
						mod = $("#contactmanageraction option:selected").data("module");
						if (UCP.validMethod(mod, func)) {
							UCP.closeDialog(function() {
								UCP.Modules[mod][func](text);
							});
						} else {
							alert(_("Function call does not exist!"));
						}
					});
				}
			);
		}
	},
	display: function(event) {
		var $this = this;
		$(".clickable").click(function(e) {
			var type = $(this).data("type"),
					text = $(this).text(),
					primary = $(this).data("primary");
			$this.showActionDialog(type, text, primary);
		});
		$("#contacts-grid").on("click-row.bs.table", function(row, element) {
			$.pjax({
				url: "?display=dashboard&mod=contactmanager&view=contact&group=" + element.groupid + "&id=" + element.uid,
				container: "#dashboard-content"
			});
		});
		$(".add-additional").click(function(e) {
			e.preventDefault();
			var type = $(this).data("type");
			$("." + type + " table").append("<tr>" + $("." + type + " .template").html() + "</tr>");
			var tr = $(".numbers tr").not(".template");
			tr.find(".smsenable-template").each(function() {
				var input = $(this).find("input"),
						label = $(this).find("label"),
						id = Date.now();

				input.prop("id","smsenable"+id);
				label.prop("for","smsenable"+id);
				$(this).removeClass("smsenable-template");
			});
			tr.find(".faxenable-template").each(function() {
				var input = $(this).find("input"),
						label = $(this).find("label"),
						id = Date.now();

				input.prop("id","faxenable"+id);
				label.prop("for","faxenable"+id);
				$(this).removeClass("faxenable-template");
			});
		});
		$("#addContact .additional").on("click", ".delete", function() {
			$(this).parents("tr").remove();
		});
		$("#editContact .additional").on("click", ".delete", function() {
			var table = $(this).parents("table"), count = 0, type = table.data("type"), data = [],  id = $("#id").val();
			$(this).parents("tr").remove();
			table.find("tr").not(".template").find("input[type!=checkbox]").each(function(i, v) {
				var obj = {};
				obj[$(this).data("name")] = $(this).val();
				data.push(obj);
			});
			$.post( "?quietmode=1&module=contactmanager&command=updatecontact", { id: id, key: type, value: data }, function( data ) {
				if (data.status) {
					$(".alert").text(data.message).addClass("alert-success").fadeIn("fast", function() {
						$(this).delay(2000).fadeOut("fast");
					});
				}
			});
		});
		$("#editcontactpage").click(function(e) {
			e.preventDefault();
			var id = $("#id").val(), groupid = $.url().param("group");
			$.pjax({
				url: "?display=dashboard&mod=contactmanager&view=contact&group=" + groupid + "&id=" + id + "&mode=edit",
				container: "#dashboard-content"
			});
		});
		$("#deletecontact").click(function(e) {
			e.preventDefault();
			var id = $("#id").val(), groupid = $.url().param("group");
			if (confirm(_("Are you sure you want to delete this contact?"))) {
				$("form input").prop("disabled", true);
				$("#deletecontact").text(_("Deleting..."));
				$("#deletecontact").prop("disabled", true);
				$.post( "?quietmode=1&module=contactmanager&command=deletecontact", { id: id }, function( data ) {
					if (data.status) {
						$.pjax({
							url: "?display=dashboard&mod=contactmanager&view=group&id=" + groupid,
							container: "#dashboard-content"
						});
					}
				});
			}
		});
		$("#editContact input").not(".special").not(".specialn").not(".skip").blur(function(e) {
			var key = $(this).prop("id"), value = $(this).val(), id = $("#id").val();
			$.post( "?quietmode=1&module=contactmanager&command=updatecontact", { id: id, key: key, value: value }, function( data ) {
				if (data.status) {
					$(".alert").text(data.message).addClass("alert-success").fadeIn("fast", function() {
						$(this).delay(2000).fadeOut("fast");
					});
				}
			});
		});
		$("#editContact .numbers").on("blur", "input", function(e) {
			var table = $(this).parents("table"), count = 0, type = table.data("type"), data = [],  id = $("#id").val();
			$(".numbers tr").filter(":visible").each(function(i, v) {
				var obj = {};
				obj.number = $(this).find("input[data-name='number']").val();
				obj.type = $(this).find("select[data-name='type']").val();
				data.push(obj);
			});
			$.post( "?quietmode=1&module=contactmanager&command=updatecontact", { id: id, key: type, value: data }, function( data ) {
				if (data.status) {
					$(".alert").text(data.message).addClass("alert-success").fadeIn("fast", function() {
						$(this).delay(2000).fadeOut("fast");
					});
				}
			});
		});
		$("#editContact .numbers").on("change", "select, input[type=checkbox]", function(e) {
			var table = $(this).parents("table"), count = 0, type = table.data("type"), data = [],  id = $("#id").val();
			$(".numbers tr").filter(":visible").each(function(i, v) {
				var obj = {};
				obj.number = $(this).find("input[data-name='number']").val();
				obj.type = $(this).find("select[data-name='type']").val();
				obj.flags = [];
				if($(this).find("input[type=checkbox].smsenable").is(":checked")) {
					obj.flags.push("sms");
				}
				if($(this).find("input[type=checkbox].faxenable").is(":checked")) {
					obj.flags.push("fax");
				}
				data.push(obj);
			});
			$.post( "?quietmode=1&module=contactmanager&command=updatecontact", { id: id, key: type, value: data }, function( data ) {
				if (data.status) {
					$(".alert").text(data.message).addClass("alert-success").fadeIn("fast", function() {
						$(this).delay(2000).fadeOut("fast");
					});
				}
			});
		});
		$("#editContact").on("blur", "input[class*='special']", function(e) {
			console.log("4");
			var table = $(this).parents("table"), count = 0, type = table.data("type"), data = [],  id = $("#id").val();
			table.find("tr").not(".template").find("input").each(function(i, v) {
				var obj = {};
				obj[$(this).data("name")] = $(this).val();
				data.push(obj);
			});
			$.post( "?quietmode=1&module=contactmanager&command=updatecontact", { id: id, key: type, value: data }, function( data ) {
				if (data.status) {
					$(".alert").text(data.message).addClass("alert-success").fadeIn("fast", function() {
						$(this).delay(2000).fadeOut("fast");
					});
				}
			});
		});
		$("#addgroup").click(function(e) {
			var groupid = 1, groupname = $("#name").val();
			e.preventDefault();
			if ($(".contact-group[data-name='" + groupname.replace(/[\\"']/g, '\\$&') + "']").length) {
				alert(_("Group Already Exists"));
				$("#name").focus();
				return false;
			}
			if (groupname === "") {
				alert(_("Group Name Can Not Be Blank"));
				$("#name").focus();
				return false;
			}
			$.post( "?quietmode=1&module=contactmanager&command=addgroup", { name: groupname }, function( data ) {
				if (data.status) {
					$(".contact-group:last").after('<div class="contact-group sub" data-name="' + groupname + '"><a cm-pjax href="?display=dashboard&amp;mod=contactmanager&amp;view=group&amp;id=' + data.id + '" class="contact-group-inner">' + groupname + '<span class="badge">0</span></a></div>');
					$("#name").val("");
					$.pjax({
						url: "?display=dashboard&mod=contactmanager&view=group&id=" + data.id,
						container: "#dashboard-content"
					});
				}
			});
		});
		//clear old binds
		$(document).off("click", "[cm-pjax] a, a[cm-pjax], [vm-pjax] a, a[vm-pjax]");
		//then rebind!
		if ($.support.pjax) {
			$(document).on("click", "[cm-pjax] a, a[cm-pjax], [vm-pjax] a, a[vm-pjax]", function(event) {
				var container = $("#dashboard-content");
				$.pjax.click(event, { container: container });
			});
		}

		/**
		 * Drag/Drop/Upload Files
		 */
		$('#contactmanager_dropzone').on('drop dragover', function (e) {
			e.preventDefault();
		});
		$('#contactmanager_dropzone').on('dragleave drop', function (e) {
			$(this).removeClass("activate");
		});
		$('#contactmanager_dropzone').on('dragover', function (e) {
			$(this).addClass("activate");
		});
		var supportedRegExp = "png|jpg|jpeg";
		$( document ).ready(function() {
			$('#contactmanager_imageupload').fileupload({
				dataType: 'json',
				dropZone: $("#contactmanager_dropzone"),
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
					});
					if(submit) {
						$("#contactmanager_upload-progress .progress-bar").addClass("progress-bar-striped active");
						data.submit();
					}
				},
				drop: function () {
					$("#contactmanager_upload-progress .progress-bar").css("width", "0%");
				},
				dragover: function (e, data) {
				},
				change: function (e, data) {
				},
				done: function (e, data) {
					$("#contactmanager_upload-progress .progress-bar").removeClass("progress-bar-striped active");
					$("#contactmanager_upload-progress .progress-bar").css("width", "0%");

					if(data.result.status) {
						$("#contactmanager_dropzone img").attr("src",data.result.url);
						$("#contactmanager_image").val(data.result.filename);
						$("#contactmanager_dropzone img").removeClass("hidden");
						$("#contactmanager_del-image").removeClass("hidden");
						$("#contactmanager_gravatar").prop('checked', false);
					} else {
						alert(data.result.message);
					}
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					$("#contactmanager_upload-progress .progress-bar").css("width", progress+"%");
				},
				fail: function (e, data) {
				},
				always: function (e, data) {
				}
			});

			$("#contactmanager_del-image").click(function(e) {
				e.preventDefault();
				e.stopPropagation();
				var grouptype = 'external';
				$.post( "?quietmode=1&module=Contactmanager&type=contact&command=delimage", {id: $("#id").val(), grouptype: grouptype, img: $("#contactmanager_image").val()}, function( data ) {
					if(data.status) {
						$("#contactmanager_image").val("");
						$("#contactmanager_dropzone img").addClass("hidden");
						$("#contactmanager_dropzone img").attr("src","");
						$("#contactmanager_del-image").addClass("hidden");
						$("#contactmanager_gravatar").prop('checked', false);
					}
				});
			});

			$("#contactmanager_gravatar").change(function() {
				if($(this).is(":checked")) {
					var grouptype = 'external';
					if($("#email").val() === "") {
						alert(_("No email defined"));
						$("#contactmanager_gravatar").prop('checked', false);
						return;
					}
					var t = $("label[for=contactmanager_gravatar]").text();
					$("label[for=contactmanager_gravatar]").text(_("Loading..."));
					$.post( "?quietmode=1&module=Contactmanager&type=contact&command=getgravatar", {id: $("#id").val(), grouptype: grouptype, email: $("input[data-name=email]:visible").one().val()}, function( data ) {
						$("label[for=contactmanager_gravatar]").text(t);
						if(data.status) {
							$("#contactmanager_dropzone img").data("oldsrc",$("#dropzone img").attr("src"));
							$("#contactmanager_dropzone img").attr("src",data.url);
							$("#contactmanager_image").data("old",$("#image").val());
							$("#contactmanager_image").val(data.filename);
							$("#contactmanager_dropzone img").removeClass("hidden");
							$("#contactmanager_del-image").removeClass("hidden");
						} else {
							alert(data.message);
							$("#contactmanager_gravatar").prop('checked', false);
						}
					});
				} else {
					var oldsrc = $("#contactmanager_dropzone img").data("oldsrc");
					if(typeof oldsrc !== "undefined" && oldsrc !== "") {
						$("#contactmanager_dropzone img").attr("src",oldsrc);
						$("#contactmanager_image").val($("#image").data("old"));
					} else {
						$("#contactmanager_image").val("");
						$("#contactmanager_dropzone img").addClass("hidden");
						$("#contactmanager_dropzone img").attr("src","");
						$("#contactmanager_del-image").addClass("hidden");
					}
				}
			});
		});
	},
	search: function(text) {
		var view = (typeof $.url().param("view") !== "undefined") ? "&view=" + $.url().param("view") : "",
				id = (typeof $.url().param("id") !== "undefined") ? "&id=" + $.url().param("id") : "";
		if (text !== "") {
			$.pjax({
				url: "?display=dashboard&mod=contactmanager&search=" + encodeURIComponent(text) + view + id,
				container: "#dashboard-content"
			});
		} else {
			$.pjax({
				url: "?display=dashboard&mod=contactmanager" + view + id,
				container: "#dashboard-content"
			});
		}
	},
	hide: function(event) {
		$(".clickable").off("click");
		$(".contact-item").off("click");
		$("#addgroup").off("click");
		$("#deletegroup").off("click");
		$("#deletecontact").off("click");
		$("#editContact input").off("blur");
		$("#addcontact, #updatecontact").off("click");
		$("#editcontact");
	},
	/**
	 * Lookup a contact from the directory
	 * @param  {string} search The string to look for
	 * @param  {object} regExp The regular expression object (make sure /g is on the end)
	 * @return {string} replaced value
	 */
	lookup: function(search, regExp) {
		var o = this.recursiveObjectSearch(search, this.contacts), contact;
		if (o !== false) {
			contact = this.contacts[o[0]];
			if (contact !== false) {
				contact.ignore = o[0];
				contact.key = o[o.length - 1];
			}
			return contact;
		}
		return false;
	},
	recursiveObjectSearch: function(search, haystack, key, strict, stack) {
		var k, o, pattern = new RegExp(search);
		for (k in haystack) {
			if (haystack.hasOwnProperty(k) && haystack[k] !== null) {
				if (typeof stack === "undefined") {
					stack = [];
				}
				if (typeof haystack[k] === "object") {
					stack.push(k);
					o = this.recursiveObjectSearch(search, haystack[k], key, strict, stack);
					if (o !== false) {
						return stack;
					} else {
						stack = [];
					}
				} else if (pattern.test(haystack[k])) {
					stack.push(k);
					return stack;
				}
			}
		}
		return false;
	}
});
