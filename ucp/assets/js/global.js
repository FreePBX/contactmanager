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
	poll: function(data) {

	},
	display: function(event) {

	},
	hide: function(event) {

	},
	lookup: function(search) {
		var contact = {},
				key = null,
				obj = null,
				prop = null,
				val = null,
				pattern = new RegExp(search);
		for (key in this.contacts) {
			obj = this.contacts[key];
			for (prop in obj) {
				if (obj.hasOwnProperty(prop)){
					if (val !== null) {
						val = obj[prop].trim();
						if (val !== "" && pattern.test(val)) {
							return obj;
						}
					}
				}
			}
		}
		return false;
	}
});
