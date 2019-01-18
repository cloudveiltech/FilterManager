// The attempt for swappable-element is to specify two elements that you want to swap on a certain condition.
//
Vue.component('editable-span', {
	template: "<div>\
		<span name='viewer' v-if='!isEditing' @click='edit()' v-bind:class='{ placeholder: value && value.length > 0 }'>{{ value || placeholder }}</span>\
		<input name='editor' v-if='isEditing' type='text' @blur='doneEditing()' @keyup='onKeyUp' @input='onInput()' class='form-control' v-model='innerValue' />\
	</div>",

	props: ['value', 'placeholder'],

	data: function() {
		return {
			elements: {},
			isEditing: false,
			innerValue: ""
		};
	},

	watch: {
		value: function(newVal) {
			this.innerValue = newVal;
		}
	},

	methods: {
		edit: function() {
			this.isEditing = true;

			this.$nextTick(function() {
				var editor = this.$el.querySelector("[name='editor']");
				editor.focus();
			});
		},

		doneEditing: function() {
			this.isEditing = false;
		},

		onInput: function(event) {
			this.$emit('input', this.innerValue);
		},

		onKeyUp: function($event) {
			if(event.code == "Enter") {
				this.doneEditing();
			}
		}
	},

	mounted: function() {
		var that = this;
	}
});

Vue.component('swappable-password-box', {
	template: "<div class='swappable-password-box'>\
		<input v-model='innerValue' @input='onInput' type='text' class='form-control' v-bind:class='{ password: !showPassword }' />\
		<button class='btn btn-secondary theme-light no-outline' @mousedown='showPassword=true'><span class='glyph glyph-view'></span></button>\
	</div>",

	props: ['value'],

	data: function() {
		return { showPassword: false, innerValue: "" };
	},

	watch: {
		value: function(val) {
			this.innerValue = val;
		}
	},

	methods: {
		onInput: function(event) {
			this.$emit('input', this.innerValue);
		},

		windowOnMouseUp: function() {
			this.showPassword = false;
		}
	},

	mounted: function() {
		window.addEventListener('mouseup', this.windowOnMouseUp);
	},

	destroyed: function() {
		window.removeEventListener('mouseup', this.windowOnMouseUp);
	}
});

Vue.component('self-moderation-entry', {

});