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
		if ($(".favorite-div .fav-tab").length) {
			var elem = $(".favorite-div");
			var h = parseInt( elem.parents(".widget-content").outerHeight()) - (parseInt(elem.find(".contact_list").offset().top));
			elem.find(".contact_list").height(parseInt(h));
		}
	},
	groupClick: function(el, widget_id) {
		$(".contacts-div").show();
		$(".favorite-div").hide();
		$(".show-favorites").removeClass("active");
		$(".grid-stack-item[data-id="+widget_id+"] .group").removeClass("active");
		$(el).addClass("active");
		var group = $(el).data("group");

		if ($(el).data('readonly') || group.length === 0) {
			$(".grid-stack-item[data-id="+widget_id+"] .deletegroup").prop("disabled",true);
			$(".grid-stack-item[data-id="+widget_id+"] .addcontact").prop("disabled",true);
		} else {
			$(".grid-stack-item[data-id="+widget_id+"] .deletegroup").prop("disabled",false);
			$(".grid-stack-item[data-id="+widget_id+"] .addcontact").prop("disabled",false);
		}
      
      	$.ajax({
			url: UCP.ajaxUrl+'?module=contactmanager&command=grid&group=' + group,
			type: "POST",
			async: false,
			success: function(data){
				$('.grid-stack-item[data-id='+widget_id+'] .contacts-grid').bootstrapTable("refreshOptions", {url: UCP.ajaxUrl+'?module=contactmanager&command=grid&group=' + group});
			}
		});
	},
	displayWidget: function(widget_id, dashboard_id) {
		var self = this;

		$(".grid-stack-item[data-id='"+widget_id+"'] .contacts-grid").one("post-body.bs.table", function() {
			setTimeout(function() {
				self.resize(widget_id);
			},250);
		});

		$(".grid-stack-item[data-id='"+widget_id+"'] .group").click(function() {
			self.groupClick(this, widget_id);
		});

		$('.grid-stack-item[data-id='+widget_id+'] .contacts-grid').on('click-row.bs.table', function (e, row, $element, field) {
			$.post(UCP.ajaxUrl, {
				module: "contactmanager",
				command: "showcontact",
				group: row.groupid,
				id: row.uid
			}, function(data) {
				if(data.status) {
					UCP.showDialog(data.title,
						data.body,
						data.footer,
						function() {
							$("#globalModal .clickable").click(function(e) {
								var type = $(this).data("type"),
										text = $(this).text(),
										primary = $(this).data("primary");
								self.showActionDialog(type, text, primary);
							});
							$("#deletecontact").click(function() {
								$("#deletecontact").prop("disabled",true);
								UCP.showConfirm(_("Are you sure you wish to delete this contact?"), 'info', function() {
									$.post( UCP.ajaxUrl, {
										module: "contactmanager",
										command: "deletecontact",
										id: row.uid
									}, function( data ) {
										if (data.status) {
											$('.grid-stack-item[data-id='+widget_id+'] .contacts-grid').bootstrapTable("refreshOptions", {url: UCP.ajaxUrl+'?module=contactmanager&command=grid&group=' + group});
											UCP.closeDialog();
										} else {
											UCP.showAlert(_("Error deleting user"),'danger');
										}
									});
								});
								$("#deletecontact").prop("disabled",false);
							});
							$("#editcontact").click(function() {
								$.getJSON(UCP.ajaxUrl, {
									module: "contactmanager",
									command: "editcontactmodal",
									group: row.groupid,
									id: row.uid
								}, function(data){
									if (data.status === true){
										UCP.showDialog(_("Edit Contact"),
											data.message,
											'<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button id="save" type="button" class="btn btn-primary">'+ _("Save changes")+'</button>',
											function() {
												self.displayEditContact(widget_id);
											}
										);
									} else {
										UCP.showAlert(_("Error getting form"),'danger');
									}
								}).always(function() {
								}).fail(function() {
									UCP.showAlert(_("Error getting form"),'danger');
								});
							});
						}
					);
				}
			});
		});

		$(".grid-stack-item[data-id='"+widget_id+"'] .addgroup").click(function() {
			$.getJSON(UCP.ajaxUrl+'?module=contactmanager&command=addgroupmodal', function(data){
				if (data.status === true){
					UCP.showDialog(_("Add Group"),
						data.message,
						'<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button type="button" class="btn btn-primary" id="save">'+ _("Save changes")+'</button>',
						function() {
							$("#groupname").focus();
							$('#contactmanager-addgroup').submit(function() {
								$('#save').click();
								return false;
							});
							$('#save').one('click',function() {
								$.ajax({
									type: 'POST',
									url: UCP.ajaxUrl+'?module=contactmanager&command=addgroup',
									data: $('#contactmanager-addgroup').serialize(),
									success: function (data) {
										if (data.status === true) {
											$(".grid-stack-item[data-id='"+widget_id+"'] .group-list").append('<div class="group" data-name="' + $("#groupname").val() + '" data-group="' + data.id + '" data-readonly="false"><a href="#" class="group-inner">' + $("#groupname").val() + '<span class="badge">0</span></a></div>');
											$(".grid-stack-item[data-id='"+widget_id+"'] .group[data-group=" + data.id + "]").click(function() {
												self.groupClick(this, widget_id);
											});
											UCP.closeDialog();
										} else {
											UCP.showAlert(data.message,'danger');
										}
									}
								});
							});
						});
				} else {
					UCP.showDialog(_("Add Group"),_("Error getting form"),'<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close"));
				}
			});
		});

		$(".show-favorites").click(function() {
			$.getJSON(UCP.ajaxUrl+'?module=contactmanager&command=favorite_contacts', function(data) {
				if (data.status === true) {
					$(".favorite-div").html(data.body);
					$("#fav_contact_count").text(data.favoriteContactsCount);
					$(".grid-stack-item .group").removeClass("active");
					$(".show-favorites").addClass("active");
					$(".contacts-div").hide();
					$(".favorite-div").show();
				} else {
					UCP.showAlert(_("There was an error loading favorite contacts"),"danger");
				}
			});
		});

		$(".grid-stack-item[data-id="+widget_id+"] .deletegroup").click(function(e) {
			e.preventDefault();
			UCP.showConfirm(_("Are you sure you want to delete this group and all of it's contacts?"), 'info', function() {
				var group = $(".grid-stack-item[data-id='"+widget_id+"'] .group-list .group.active").data("group");

				$.post( UCP.ajaxUrl+"?module=contactmanager&command=deletegroup", { id: group }, function( data ) {
					if (data.status) {
						$(".group[data-group='']").trigger("click");
						$(".grid-stack-item[data-id='"+widget_id+"'] .group-list .group[data-group='" + group + "']").remove();
					}
				}).fail(function() {
					UCP.showAlert(_("There was an error removing this group"),"danger");
				});
			});
		});

		$(".grid-stack-item[data-id="+widget_id+"] .addcontact").click(function(e) {
			e.preventDefault();

			var $this = this;

			$($this).prop("disabled",true);

			$.getJSON(UCP.ajaxUrl+'?module=contactmanager&command=addcontactmodal', function(data){
				if (data.status === true){
					UCP.showDialog(_("Add Contact"),
						data.message,
						'<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button id="save" type="button" class="btn btn-primary">'+ _("Save changes")+'</button>',
						function() {
							self.displayEditContact(widget_id);
						}
					);
				} else {
					UCP.showAlert(_("Error getting form"),'danger');
				}
			}).always(function() {
				$($this).prop("disabled",false);
			}).fail(function() {
				UCP.showAlert(_("Error getting form"),'danger');
			});
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
				"<select id=\"contactmanageraction\" class=\"form-control\">" + options + "</select>",
				"<button class=\"btn btn-default\" id=\"initiateaction\" style=\"margin-left: 72px;\">"+_("Initiate")+"</button>",
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
	displayEditContact: function(widget_id) {
		$('#globalModal input[type=checkbox][data-toggle="toggle"]:visible').bootstrapToggle();
		$("#globalModal").on("blur", "input.number-sd", function(e) {
			var orig = $(this).data("orig"),
				val = $(this).val(),
				$this = $(this),
				entry = null;

			orig = (typeof orig !== "undefined") ? orig : "";

			if(val !== "") {
				var indexes = [];
				var stop = false;
				$(".number-sd").each(function() {
					if($(this).val() === "") {
						return true;
					}
					if($.inArray(val, indexes) > -1) {
						UCP.showAlert(_("This speed dial id conflicts with another speed dial on this page"),'warning');
						$this.val(orig);
						stop = true;
						return false;
					}
					indexes.push($(this).val());
				});
				if(stop) {
					return false;
				}
				$.post( UCP.ajaxUrl + "?module=contactmanager&command=checksd", {id: val, entryid: entry}, function( data ) {
					if(!data.status) {
						UCP.showAlert(_("This speed dial id conflicts with another contact"),'warning');
						$this.val(orig);
					} else {
						$this.data("value",val);
					}
				});
			} else {
				$this.data("value",val);
			}
		});
		$('#save').on('click',function() {
			var data = {
				id: $("#id").val(),
				displayname: $("#displayname").val(),
				fname: $("#fname").val(),
				lname: $("#lname").val(),
				title: $("#title").val(),
				company: $("#company").val(),
				numbers: [],
				xmpps: [],
				emails: [],
				websites: [],
				image:$("#contactmanager_image").val()
			};
			$("input[data-name=number]").each(function() {
				var val = $(this).val(),
						parent = $(this).parents(".form-inline"),
						type = parent.find("select[data-name=type]").val(),
						sms = parent.find("input[data-name=smsflag]").is(":checked"),
						fax = parent.find("input[data-name=faxflag]").is(":checked"),
						locale = parent.find("select[data-name=locale]").val(),
						flags = [],
						speeddial = '';
				if(val === "") {
					return true;
				}
				if(parent.find("input[data-name=numbersd]:enabled").length) {
					speeddial = parent.find("input[data-name=numbersd]:enabled").val();
				}

				if(sms) {
					flags.push('sms')
				}

				if(fax) {
					flags.push('fax')
				}

				data.numbers.push({
					number: val,
					type: type,
					flags: flags,
					speeddial: speeddial,
					locale: locale
				});
			});
			$("input[data-name=websites], input[data-name=emails], input[data-name=xmpps]").each(function() {
				var val = $(this).val(),
						name = $(this).data("name"),
						type = $(this).data("type");
				if(val === "") {
					return true;
				}
				var obj = {};
				obj[type] = val;
				data[name].push(obj);
			});

			var group = $(".grid-stack-item[data-id='"+widget_id+"'] .group-list .group.active").data("group");

			var params = {
				module: "contactmanager",
				command: (data.id === "" ? "addcontact" : "updatecontact"),
				group: group,
				contact: data
			};

			$.post({
				url: UCP.ajaxUrl,
				data: params,
				success: function (data) {
					if(data.status) {
						$(".grid-stack-item[data-id='"+widget_id+"'] .contacts-grid").bootstrapTable("refreshOptions", {url: UCP.ajaxUrl+'?module=contactmanager&command=grid&group=' + group});
						UCP.closeDialog();
					} else {
						UCP.showAlert(data.message, 'danger');
					}
				}
			}).fail(function() {
				UCP.showAlert(_("There was an error"), 'danger');
			});
		});
		var changeSpeedDial = function() {
			var el = $(this).parents(".input-group").find(".number-sd");
			el.prop("disabled",!$(this).is(":checked"));
			if(!$(this).is(":checked")) {
				el.val("");
			} else {
				if(typeof el.data("value") !== "undefined") {
					el.val(el.data("value"));
				}
			}
		};
		$(".enable-sd").change(changeSpeedDial);
		$(".add-additional").click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			var name = $(this).data("type"),
					container = $("input[data-name="+name+"]").one().parents(".item-container").first();

			if(name === "number") {
				$('#globalModal input[data-name=smsflag], #globalModal input[data-name=faxflag]').bootstrapToggle('destroy');
			}
			var html = container.clone();
			html.find("input").val("");
			var cmlocale = navigator.language.split('-')[1];
			cmlocale = cmlocale ? cmlocale : navigator.language.split('-')[0]
			html.find("select[data-name=locale]").val(cmlocale)
			container.after(html);
			if(name === "number") {
				$('#globalModal input[data-name=smsflag], #globalModal input[data-name=faxflag]').bootstrapToggle();
			}
			$(".enable-sd").off("change");
			$(".enable-sd").change(changeSpeedDial);

		});
		$(document).on("click",".item-container .delete",function() {
			var name = $(this).data("type");
			if($("input[data-name="+name+"]").length === 1) {
				$("input[data-name="+name+"]").val("");
				if(name == "number") {
					$("input[data-name=smsflag]").bootstrapToggle("off");
					$("input[data-name=faxflag]").bootstrapToggle("off");
				}
			} else {
				$(this).parents(".item-container").remove();
			}

		});
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
					$.post( "?quietmode=1&module=Contactmanager&type=contact&command=getgravatar", {id: $("#id").val(), grouptype: grouptype, email: $("input[data-name=emails]:visible").one().val()}, function( data ) {
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

var obj = new UCPC();
$(document).on("click", "#save_favorites", function () {
	var included_contacts = [];
	$('#included_contacts>span').each(function() {
		included_contacts.push($(this).attr('data-contactId'));
	});
	$.ajax({
		url: 'ajax.php?module=contactmanager&command=update_favorite_contacts',
		type: "POST",
		data: {'included_contacts': included_contacts},
		success: function(data){
			$("#fav_contact_count").text(data.favoriteContactsCount);
			obj.showAlert(data.message, 'success');
		}
	});
})