
Vue.component('day-restrictions', {
	template: '<div class="form-group restrictions">\
		<div class="row">\
			<div class="checkbox col-md-4">\
				<label>\
					<input type="checkbox" @click="onInput()" v-model="sliderValue.RestrictionsEnabled">\
					<span>{{ caption }}</span>\
				</label>\
			</div>\
			<div class="slider inverse col-md-20">\
				<no-ui-slider v-bind:slider-config="sliderConfig" :slider-value="sliderValue.EnabledThrough" @input="onSliderInput($event)"></no-ui-slider>\
			</div>\
			<div class="row">\
				<div class="internet-label">{{ internetLabel }}</div>\
			</div>\
		</div>\
	</div>',

	props: ['value', 'sliderConfig', 'caption'],

	data: function() {
		return {
			sliderValue: {
				EnabledThrough: [0, 24],
				RestrictionsEnabled: false
			},

			internetLabel: ""
		};
	},

	methods: {
		onInput: function() {
			this.$emit('input', this.sliderValue);
		},

		onSliderInput($event) {
			!this.sliderValue.EnabledThrough && Vue.set(this.sliderValue, 'EnabledThrough', [0, 24]);
			Vue.set(this.sliderValue.EnabledThrough, $event.handle, $event.value[$event.handle]);

			this.onInput();
		},

		generateInternetLabel: function() {
			var entry = this.sliderValue;
			var enabledTimes = entry.EnabledThrough || [0,24];

			if(!entry.RestrictionsEnabled) {
				return "No restrictions for " + this.caption;
			} else {
				if(enabledTimes[0] == 0 && enabledTimes[1] == 24) {
					return "No restrictions for " + this.caption;
				} else if(enabledTimes[0] == enabledTimes[1]) {
					return "Internet restricted all day";
				} else {
					// enabledTimes[0] 
					return "Internet allowed between " + this.timeOfDay(enabledTimes[0]) + " and " + this.timeOfDay(enabledTimes[1]);
				}
			}
		},

		timeOfDay: function(n) {
			var minutes = Math.round((n % 1) * 60);
			var hours = Math.floor(n);

			var ampm = (hours % 24) >= 12 ? "PM" : "AM";

			hours %= 12;
			if(hours == 0) {
				hours = 12;
			}

			if(minutes < 10) {
				minutes = "0" + minutes;
			}

			return hours + ":" + minutes + ampm;
		}
	},

	computed: {
		
	},

	watch: {
		sliderValue: {
			handler: function(val, oldVal) {
				this.internetLabel = this.generateInternetLabel();
			},

			deep: true
		},


		value: {
			handler: function(val, oldVal) {
				for(var i in val) {
					Vue.set(this.sliderValue, i, val[i]);
				}
			},

			deep: true
		}
	},

	mounted: function() {
		this.$nextTick(function() {
			this.sliderValue = this.value;
		});
	}

})