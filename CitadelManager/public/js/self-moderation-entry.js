// The attempt for swappable-element is to specify two elements that you want to swap on a certain condition.
//

Vue.component("self-moderation-list", {
    template: /*html*/ `<div class="self-moderation-list">
                            <button type="button" class="add-url btn btn-primary" @click="addUrlEntry()">\
                                <span class="glyph glyph-add"></span> {{addButtonText}}\
                            </button>\
                            <ul class="list-items self-moderation">\
                                <li class="list-items-row" v-for="(item, index) in value">\
                                    <div class="site-text">\
                                        <editable-span :isurl="isurl" v-model="value[index]" :activations="activations" placeholder="(click here to edit)">\
                                            <select class='form-select width-25-percent' style='display: inline-block;' v-if='!activationEdit' v-model='value[index].activation'>\
                                                <option :value='globalId'>{{ globalId }}</option>\
                                                <option v-for='(item, index) in activations' :value='activations[index].identifier'>\
                                                {{ activations[index].device_id }}\
                                                </option>\
                                            </select>\
                                        </editable-span>\
                                    </div>\
                                    <div class="remove-button-container">\
                                        <button class="btn btn-danger" @click.prevent="removeUrl(index)">
                                            <span class="glyph glyph-remove"></span>
                                        </button>\
                                    </div>\
                                </li>\
                            </ul>\
                        </div>`,

    props: {
        value: {},
        addButtonText: {},
        activations: {},
        isurl: {},
        activationEdit: {
            default: false
        }
    },

  data: function () {
      return {
        globalId: ACTIVATIONS_ALL_ID,
      };
  },

  watch: {
    value: function (newVal) {},
  },

  methods: {
    removeUrl: function (index) {
      this.value.splice(index, 1);
    },

    addUrlEntry: function () {
      this.value.push({
        value: "",
        activation: ACTIVATIONS_ALL_ID,
      });
    },
  },
});

Vue.component("editable-span", {
    template: /*html*/
  `<div class='editable-span'>
		<span name='viewer' v-if='!isEditing' @click='edit()' class='viewer width-70-percent' style='display: inline-block;' v-bind:class='{ placeholder: !value || value.value.length == 0 }'>{{ value.value || placeholder }}</span>
		<input name='editor' v-if='isEditing' type='text' @blur='doneEditing()' @keydown='onKeyDown' @input='onInput()' style='display: inline-block; margin-bottom: 0; margin-top: 0;' class='form-control width-70-percent' v-model='innerValue' />
            <slot></slot>
		<div class='alert-danger' v-if='error.length > 0'>{{ error }}</div>
	</div>`,

  props: ["value", "placeholder", "activations", "isurl"],

  data: function () {
    return {
      elements: {},
      isEditing: false,
      innerValue: "",
      globalId: ACTIVATIONS_ALL_ID,
      error: "",
    };
  },

  watch: {
    innerValue: function (newVal) {
      if (this.isValidUrl(newVal)) {
        this.value.value = newVal;
      }
    },
  },

  methods: {
    edit: function () {
      this.isEditing = true;

      this.$nextTick(function () {
        var editor = this.$el.querySelector("[name='editor']");
        editor.value = this.value.value || "";

        editor.focus();
      });
    },

    isValidUrl: function (value) {
      if (!this.isurl) {
        return true;
      }
      return /^[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)?$/.test(
        value
      );
    },

    doneEditing: function () {
      if (this.innerValue.length == 0) {
        this.error = "The value is empty.";
        return;
      }
      if (this.isValidUrl(this.innerValue)) {
        this.isEditing = false;
        this.error = "";
      } else {
        this.error = "Enter a valid domain name";
      }
    },

    onInput: function (event) {
      this.$emit("input", this.value);
    },

    onKeyDown: function ($event) {
      var code = $event.charCode || $event.keyCode || $event.which;

      if (code == 13) {
        this.doneEditing();
        $event.preventDefault();
      }
    },
  },

  mounted: function () {
    var that = this;
  },
});

Vue.component('swappable-password-box', {
    template: "<div class='swappable-password-box'>\
		<input v-model='innerValue' @input='onInput' type='text' class='form-control' v-bind:class='{ password: !showPassword }' />\
		<button class='btn btn-secondary theme-light no-outline' @mousedown='showPassword=true'><span class='glyph glyph-view'></span></button>\
	</div>",

    props: ['value'],

    data: function () {
        return {showPassword: false, innerValue: ""};
    },

    watch: {
        value: function (val) {
            this.innerValue = val;
        }
    },

    methods: {
        onInput: function (event) {
            this.$emit('input', this.innerValue);
        },

        windowOnMouseUp: function () {
            this.showPassword = false;
        }
    },

    mounted: function () {
        window.addEventListener('mouseup', this.windowOnMouseUp);
    },

    destroyed: function () {
        window.removeEventListener('mouseup', this.windowOnMouseUp);
    }
});

Vue.component('self-moderation-entry', {});
