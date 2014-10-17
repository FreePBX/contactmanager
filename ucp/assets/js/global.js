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
	/**
	 * Lookup a contact from the directory
	 * @param  {string} search The string to look for
	 * @param  {object} regExp The regular expression object (make sure /g is on the end)
	 * @return {string} replaced value
	 */
	lookup: function(search, regExp) {
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
					if (obj[prop] !== null) {
						val = obj[prop];
						if (typeof regExp !== "undefined") {
							val = val.replace(regExp, "");
						}
						val = val.trim();
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
