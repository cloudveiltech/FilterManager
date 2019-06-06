var app = null;

var user = null;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },

    contentType: "application/json; charset=utf-8"
});

$.ajax('/api/user/me', {
	method: "GET"
}).done(function(data, textStatus, xhr) {
	console.log(data);
	user = data;

	if(user && user.roles) {
		user.isBusinessOwner = false;

		for(var i in user.roles) {
			var role = user.roles[i];

			if(role.name == "business-owner" || role.name == "admin") {
				user.isBusinessOwner = true;
				break;
			}
		}
	}

	if(app) {
		app.isBusinessOwner = user.isBusinessOwner;
	}

	if(window.onUserLoad) {
		Vue.nextTick(function() {
			window.onUserLoad(user);
		});
	}
}).fail(function(xhr, textStatus, errorThrown) {

})

$(document).ready(function() {
	

	$("#logout_button").click(function() {
		$.post('logout', function (data, textStatus, jqXHR) {
            location.reload();
        });
	});

	var vueOptions = {
		el: '#app',
		data: {
			isBusinessOwner: false
		},
		computed: {},
		watch: {},

		destroyed: function() {
			if(vueOptions.$unwatchers) {
				for(var i in vueOptions.$unwatchers) {
					if(typeof vueOptions.$unwatchers[i] == 'function') {
						vueOptions.$unwatchers[i]();
					}
				}
			}
		}
	};

	function applyModel(options, modelKey, model) {
		if(typeof model == 'object' && 'data' in model && 'computed' in model) {
			vueOptions.data[modelKey] = model.data;
			vueOptions.computed[modelKey] = model.computed;
		} else {
			vueOptions.data[modelKey] = model;
		}

		if(typeof model == 'object' && 'watch' in model) {
			for(var i in model.watch) {
				vueOptions.watch[i] = model.watch[i];
			}
		}
	}

	// A short explanation of the purpose of this function
	// Unfortunately, since vue does not allow us to create computed properties on the fly,
	// we have to set our viewmodel in 
	function applyVmRef(data, vm) {
		data.$vm = vm;
		vueOptions.$unwatchers = vueOptions.$unwatchers || [];

		data.$watch = function(fn, callback) {
			var unwatch = vm.$watch(fn, callback);
			vueOptions.$unwatchers.push(unwatch);

			return function() {
				unwatch();

				for(var i = 0; i < vueOptions.$unwatchers.length; i++) {
					if(vueOptions.$unwatchers[i] == unwatch) {
						vueOptions.$unwatchers.splice(i, 1);
						break;
					}
				}
			};
		};

		if(data.$onApplyRef) {
			data.$onApplyRef();
		}
	}

	applyModel(vueOptions, 'timeRestrictions', timeRestrictionsModel());
	applyModel(vueOptions, 'selfModeration', selfModerationModel());
	applyModel(vueOptions, 'relaxedPolicy', relaxedPolicyModel());
	applyModel(vueOptions, 'deactivationRequests', deactivationRequestsModel());
	applyModel(vueOptions, 'activations', activationsModel());

	app = new Vue(vueOptions);

	for(var i in vueOptions.data) {
		applyVmRef(vueOptions.data[i], app);
	}

	if(user != null) {
		app.isBusinessOwner = user.isBusinessOwner;
	}
});

function timeRestrictionsModel() {
	var that = {};

	that.days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
	that.captionDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

	that.data = {};

	for(var i in that.days) {
		that.data[that.days[i]] = { EnabledThrough: [0, 24] };
	}

	that.sliderConfig = {
		start: [0, 24],
		connect: true,
		range: {
			'min': 0,
			'max': 24
		},

		step: 0.25
	};

	that.$onApplyRef = function() {
		
	}

	that.applySetting = function(entry, enabled, starting, ending) {
		entry.RestrictionsEnabled = enabled;

		Vue.set(entry.EnabledThrough, 0, starting);
		Vue.set(entry.EnabledThrough, 1, ending);
	}

	that.presets = {
		evening: function() {
			that.savedCustom = JSON.parse(JSON.stringify(that.data));

			for(var i in that.data) {
				that.applySetting(that.data[i], true, 5, 20);
			}
		},

		office: function() {
			that.savedCustom = JSON.parse(JSON.stringify(that.data));

			for(var i in that.data) {
				that.applySetting(that.data[i], true, 7, 18);
			}

			that.applySetting(that.data.saturday, true, 10, 15);
			that.applySetting(that.data.sunday, true, 0, 0);
		},

		none: function() {
			that.savedCustom = JSON.parse(JSON.stringify(that.data));

			for(var i in that.data) {
				that.applySetting(that.data[i], false, 0, 24);
			}
		}
	};
	
	that.setTimes = function(day, event) {
		that.data[day] = that.data[day] || {};
		that.data[day].EnabledThrough = that.data[day].EnabledThrough || [0, 24];
		that.data[day].EnabledThrough[event.handle] = event.value;
	};

	that.fetch = function() {
		$.get('/api/user/time_restrictions', "", null, "json").done(function(data) {
			for(var i in that.data) {
				that.data[i] = data[i] || { EnabledThrough: [0, 24] };
			}
		});
	};

	that.save = function() {
		$.ajax('/api/user/time_restrictions', {
			data: JSON.stringify({
				time_restrictions: that.data
			}),
			dataType: "json",
			method: "POST"
		}).done(function(data) {
			toastr.info("Changes saved");
		}).fail(function() {
			toastr.error("Failed to save your changes. Please contact support.");
		});
	};

	that.fetch();
	return that;
}

function selfModerationModel() {
	var that = {};

	that.whitelist = selfModerationListModel();
	that.blacklist = selfModerationListModel();

	that.fetch = function() {
		$.get('/api/user/self_moderation').done(function(data) {
			if(data.whitelist) {
				that.whitelist.data = data.whitelist;
			}

			if(data.blacklist) {
				that.blacklist.data = data.blacklist;
			}
		});
	};

	that.save = function() {
		$.ajax('/api/user/self_moderation', {
			data: JSON.stringify({
				blacklist: that.blacklist.data,
				whitelist: that.whitelist.data
			}),
			dataType: "json",
			method: "POST"
		}).done(function(data) {
			toastr.info("Changes saved");
		}).fail(function() {
			toastr.error("Failed to save your changes. Please contact support.");
		});
	};

	that.fetch();
	return that;
}

function selfModerationListModel() {
	var that = {};

	that.data = [];

	that.removeUrl = function(url) {
		for(var i in that.data) {
			if(that.data[i] == url) {
				that.data.splice(i, 1);
				break;
			}
		}
	};

	that.addUrlEntry = function() {
		!that.data && Vue.set(that, 'data', []);
		that.data.push("");
	};

	return that;
}

function relaxedPolicyModel() {
	var that = {};

	that.data = {
		enable_relaxed_policy_passcode: false,
		relaxed_policy_passcode: ""
	};

	that.$onApplyRef = function() {
		that.$watch(function() {
			return that.data.relaxed_policy_passcode;
		}, function(value) {
			if(value && value.length > 0) {
				Vue.set(that.data, 'enable_relaxed_policy_passcode', true);
			} else {
				Vue.set(that.data, 'enable_relaxed_policy_passcode', false);
			}
		});
	};

	that.fetch = function() {
		$.get('/api/user/relaxed_policy/passcode_info').done(function(data) {
			Vue.set(that, 'data', data);
		});
	};

	that.save = function() {
		$.ajax('/api/user/relaxed_policy/passcode_info', {
			data: JSON.stringify(that.data),
			dataType: "json",
			method: "POST"
		}).done(function(data) {
			toastr.info("Changes saved");
		}).fail(function() {
			toastr.error("Failed to save your changes. Please contact support.");
		});
	};

	that.fetch();
	return that;
}

function deactivationRequestsModel() {
	var that = {};

	that.data = [];

	that.fetch = function() {
		$.get('/api/business/deactivations').done(function(data) {
			Vue.set(that, 'data', data);
		});
	};

	that.grant = function(request) {
		$.ajax('/api/business/deactivations/' + request.id + '/grant', {
			method: "POST"
		}).done(function() {
			request.granted = 1;
			toastr.info("Granted deactivation request.");
		}).fail(function() {
			toastr.error("Was not able to grant deactivation request.");
		})
	};

	that.deny = function(request) {
		$.ajax('/api/business/deactivations/' + request.id, {
			method: "DELETE"
		}).done(function() {
			that.fetch();
		});
	};

	that.fetch();

	return that;
}

function activationsModel() {
	var that = {};

	that.data = [];

	that.fetch = function() {
		$.get('/api/business/activations').done(function(data) {
			Vue.set(that, 'data', data);
		});
	};

	that.blockActivation = function(activation) {
		$.ajax('/api/business/activations/' + activation.id + '/block', {
			method: "DELETE"
		}).done(function() {
			that.fetch();
		}).fail(function() {
			toastr.error("Failed to block activation.");
		});
	};

	that.deleteActivation = function(activation) {
		$.ajax('/api/business/activations/' + activation.id + '/delete', {
			method: "DELETE"
		}).done(function() {
			that.fetch();
		}).fail(function() {
			toastr.error("Failed to delete activation.");
		});
	};

	that.fetch();

	return that;
}