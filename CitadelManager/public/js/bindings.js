var Citadel;
(function (Citadel) {
    var BindingInstance = (function () {
        function BindingInstance(element, model) {
            this.VALUE_BIND = 'value-bind';
            this.ELEM_BIND = 'elem-bind';
            this.TEXT_BIND = 'text-bind';
            this.eventTypes = ['click', 'submit'];
            this.eventListeners = [];
            this.bindingTypes = {
                'value-bind': this.bindValueBinding,
                'elem-bind': this.bindElementBinding,
                'text-bind': this.bindTextBinding
            };
            this.element = element;
            this.model = model;
        }
        BindingInstance.prototype.Bind = function () {
            this.valueBoundElements = this.element.querySelectorAll("[value-bind]");
            this.elementBoundElements = this.element.querySelectorAll("[elem-bind]");
            this.textBoundElements = this.element.querySelectorAll("[text-bind]");
            this.eventElements = {};
            for (var _i = 0, _a = this.eventTypes; _i < _a.length; _i++) {
                var eventType = _a[_i];
                this.eventElements[eventType] = this.element.querySelectorAll("[event-" + eventType + "]");
            }
            this.bindings = [];
            this.buildBindings(this.valueBoundElements, this.bindings, this.VALUE_BIND);
            this.buildBindings(this.elementBoundElements, this.bindings, this.ELEM_BIND);
            this.buildBindings(this.textBoundElements, this.bindings, this.TEXT_BIND);
            this.triggerBindings(this.bindings);
            this.buildEventBindings();
        };
        BindingInstance.prototype.Unbind = function () {
            if (this.eventListeners) {
                for (var _i = 0, _a = this.eventListeners; _i < _a.length; _i++) {
                    var listenerObj = _a[_i];
                    listenerObj.elem.removeEventListener(listenerObj.type, listenerObj.fn);
                }
                this.eventListeners = null;
            }
        };
        BindingInstance.prototype.Refresh = function () {
            for (var _i = 0, _a = this.bindings; _i < _a.length; _i++) {
                var binding = _a[_i];
                if (binding.calls && binding.calls.onmodelupdate) {
                    binding.calls.onmodelupdate();
                }
            }
        };
        BindingInstance.prototype.buildEventBindings = function () {
            function generateEventBinding(model, elem, eventType) {
                var attr = elem.attributes["event-" + eventType];
                var prop = attr.value;
                var fn = new Function('_', 'e', '_.' + prop + '(e)');
                return function (e) {
                    fn(model, e);
                };
            }
            this.eventListeners = [];
            for (var eventType in this.eventElements) {
                for (var _i = 0, _a = this.eventElements[eventType]; _i < _a.length; _i++) {
                    var elem = _a[_i];
                    var eventFn = generateEventBinding(this.model, elem, eventType);
                    elem.addEventListener(eventType, eventFn);
                    this.eventListeners.push({
                        elem: elem,
                        type: eventType,
                        fn: eventFn
                    });
                }
            }
        };
        BindingInstance.prototype.triggerBindings = function (bindings) {
            for (var _i = 0, bindings_1 = bindings; _i < bindings_1.length; _i++) {
                var binding = bindings_1[_i];
                switch (binding.bindingType) {
                    case this.VALUE_BIND:
                        this.bindValueBinding(binding);
                        break;
                    case this.ELEM_BIND:
                        this.bindElementBinding(binding);
                        break;
                    case this.TEXT_BIND:
                        this.bindTextBinding(binding);
                        break;
                }
            }
        };
        BindingInstance.prototype.bindValueBinding = function (binding) {
            var that = this;
            binding.calls = {
                onmodelupdate: function () {
                    var newValue = binding.get(that.model);
                    binding.target.value = newValue;
                },
                onviewupdate: function () {
                    var viewValue = binding.target.value;
                    binding.set(that.model, viewValue);
                }
            };
            binding.target.addEventListener('input', function () {
                binding.calls.onviewupdate();
            });
        };
        BindingInstance.prototype.bindElementBinding = function (binding) {
            var that = this;
            binding.calls = {
                onmodelupdate: function () {
                    binding.set(that.model, binding.target);
                },
                onviewupdate: function () {
                }
            };
        };
        BindingInstance.prototype.bindTextBinding = function (binding) {
            var that = this;
            binding.calls = {
                onmodelupdate: function () {
                    binding.target.innerText = binding.get(that.model);
                },
                onviewupdate: function () {
                }
            };
        };
        BindingInstance.prototype.buildBindings = function (elements, bindings, bindingAttr) {
            for (var _i = 0, elements_1 = elements; _i < elements_1.length; _i++) {
                var elem = elements_1[_i];
                var propBindings = this.getPropManipulators(elem, elem.attributes[bindingAttr].value, bindingAttr);
                bindings.push(propBindings);
            }
        };
        BindingInstance.prototype.getPropManipulators = function (target, prop, bindingAttr) {
            var propParts = prop.split('.');
            propParts = propParts || [];
            return {
                target: target,
                bindingType: bindingAttr,
                get: function (obj) {
                    var o = obj;
                    if (propParts.length == 0) {
                        throw new Error("Invalid property binding");
                    }
                    for (var i = 0; i < propParts.length - 1; i++) {
                        if (!(propParts[i] in o)) {
                            return null;
                        }
                        o = o[propParts[i]];
                    }
                    if (propParts[i] in o) {
                        return o[propParts[i]];
                    }
                    else {
                        return null;
                    }
                },
                set: function (obj, value) {
                    var o = obj;
                    if (propParts.length == 0) {
                        throw new Error("Invalid property binding");
                    }
                    for (var i = 0; i < propParts.length - 1; i++) {
                        if (!(propParts[i] in o)) {
                            o[propParts[i]] = {};
                        }
                        o = o[propParts[i]];
                    }
                    o[propParts[propParts.length - 1]] = value;
                }
            };
        };
        return BindingInstance;
    }());
    Citadel.BindingInstance = BindingInstance;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=bindings.js.map