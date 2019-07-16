// The attempt for swappable-element is to specify two elements that you want to swap on a certain condition.
//

Vue.component('self-moderation-list', {
	template: '<div class="self-moderation-list"><button type="button" class="add-url btn btn-primary" @click="addUrlEntry()">\
	            	<span class="glyph glyph-add"></span> {{addButtonText}}\
	            </button>\
				<ul class="list-items self-moderation">\
					<li class="list-items-row" v-for="(item, index) in value">\
                    	<div class="site-text">\
                        	<editable-span v-model="value[index]" placeholder="(click here to edit)">\
                        	</editable-span>\
                    	</div>\
                    	<div class="remove-button-container">\
                        	<button class="btn btn-danger" @click="removeUrl(index)"><span class="glyph glyph-remove"></span></button>\
                    	</div>\
	            	</li>\
	            </ul>\
	            </div>',

	props: ['value', 'addButtonText'],

	data: function() {
		return {

		};
	},

	watch: {
		value: function(newVal) {

		}
	},

	methods: {
		removeUrl: function(index) {
			this.value.splice(index, 1);
		},

		addUrlEntry: function() {
			this.value.push("");
		}
	}
});

Vue.component('editable-span', {
	template: "<div class='editable-span'>\
		<span name='viewer' v-if='!isEditing' @click='edit()' class='viewer' v-bind:class='{ placeholder: !value || value.length == 0 }'>{{ value || placeholder }}</span>\
		<input name='editor' v-if='isEditing' type='text' @blur='doneEditing()' @keydown='onKeyDown' @input='onInput()' style='margin-bottom: 0; margin-top: 0;'\
			class='form-control' v-model='innerValue' />\
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
				editor.value = this.value;

				editor.focus();
			});
		},

		doneEditing: function() {
			this.isEditing = false;
		},

		onInput: function(event) {
			this.$emit('input', this.innerValue);
		},

		onKeyDown: function($event) {
			var code = $event.charCode || $event.keyCode || $event.which;

			if(code == 13) {
				this.doneEditing();
				$event.preventDefault();
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