var Citadel;
(function (Citadel) {
    class BindingInstance {
        constructor(element, model) {
            this.VALUE_BIND = 'value-bind';
            this.ELEM_BIND = 'elem-bind';
            this.TEXT_BIND = 'text-bind';
            this.eventTypes = ['click', 'submit', 'change'];
            this.eventListeners = [];
            this.bindingTypes = {
                'value-bind': this.bindValueBinding,
                'num-value-bind': this.bindNumValueBinding,
                'elem-bind': this.bindElementBinding,
                'text-bind': this.bindTextBinding
            };
            this.element = element;
            this.model = model;
        }
        Bind() {
            this.boundElements = {};
            this.eventElements = {};
            this.bindings = [];
            for (var bindingType in this.bindingTypes) {
                this.boundElements[bindingType] = this.element.querySelectorAll("[" + bindingType + "]");
                this.buildBindings(this.boundElements[bindingType], this.bindings, bindingType);
            }
            for (var eventType of this.eventTypes) {
                this.eventElements[eventType] = this.element.querySelectorAll("[event-" + eventType + "]");
            }
            this.triggerBindings(this.bindings);
            this.buildEventBindings();
        }
        Unbind() {
            if (this.eventListeners) {
                for (var listenerObj of this.eventListeners) {
                    listenerObj.elem.removeEventListener(listenerObj.type, listenerObj.fn);
                }
                this.eventListeners = null;
            }
        }
        Refresh() {
            for (var binding of this.bindings) {
                if (binding.calls && binding.calls.onmodelupdate) {
                    binding.calls.onmodelupdate();
                }
            }
        }
        buildEventBindings() {
            function generateEventBinding(model, elem, eventType) {
                var attr = elem.attributes["event-" + eventType];
                var prop = attr.value;
                var fn = new Function('_', 'e', '_.' + prop + '(e)');
                return function (e) {
                    var r = fn(model, e);
                    return r === undefined ? false : r;
                };
            }
            for (var eventType in this.eventElements) {
                for (var elem of this.eventElements[eventType]) {
                    var eventFn = generateEventBinding(this.model, elem, eventType);
                    this.addListenerTo(elem, eventType, eventFn);
                }
            }
        }
        triggerBindings(bindings) {
            for (var binding of bindings) {
                if (binding.bindingType in this.bindingTypes) {
                    this.bindingTypes[binding.bindingType].call(this, binding);
                }
            }
        }
        addListenerTo(elem, eventType, fn) {
            this.eventListeners = this.eventListeners || [];
            elem.addEventListener(eventType, fn);
            this.eventListeners.push({
                elem: elem,
                type: eventType,
                fn: fn
            });
        }
        bindValueBinding(binding) {
            let that = this;
            binding.isCheckbox = binding.target.attributes.type && binding.target.attributes.type.value == "checkbox";
            binding.calls = {
                onmodelupdate: function () {
                    let newValue = binding.get(that.model);
                    let prop = binding.isCheckbox ? 'checked' : 'value';
                    binding.target[prop] = newValue;
                },
                onviewupdate: function () {
                    let viewValue = (binding.isCheckbox) ? binding.target.checked : binding.target.value;
                    binding.set(that.model, viewValue);
                }
            };
            var eventType = binding.isCheckbox ? 'change' : 'input';
            that.addListenerTo(binding.target, eventType, function () {
                binding.calls.onviewupdate();
            });
        }
        bindNumValueBinding(binding) {
            let that = this;
            binding.calls = {
                onmodelupdate: function () {
                    let newValue = binding.get(that.model);
                    binding.target.value = newValue;
                },
                onviewupdate: function () {
                    let viewValue = binding.target.valueAsNumber;
                    binding.set(that.model, viewValue);
                }
            };
            that.addListenerTo(binding.target, 'input', function () {
                binding.calls.onviewupdate();
            });
        }
        bindElementBinding(binding) {
            let that = this;
            binding.calls = {
                onmodelupdate: function () {
                    binding.set(that.model, binding.target);
                },
                onviewupdate: function () {
                }
            };
        }
        bindTextBinding(binding) {
            let that = this;
            binding.calls = {
                onmodelupdate: function () {
                    binding.target.innerText = binding.get(that.model);
                },
                onviewupdate: function () {
                }
            };
        }
        buildBindings(elements, bindings, bindingAttr) {
            for (let elem of elements) {
                let propBindings = this.getPropManipulators(elem, elem.attributes[bindingAttr].value, bindingAttr);
                bindings.push(propBindings);
            }
        }
        getPropManipulators(target, prop, bindingAttr) {
            var propParts = prop.split('.');
            propParts = propParts || [];
            return {
                target: target,
                bindingType: bindingAttr,
                get: function (obj) {
                    let o = obj;
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
                    let o = obj;
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
        }
    }
    Citadel.BindingInstance = BindingInstance;
})(Citadel || (Citadel = {}));
//# sourceMappingURL=bindings.js.map