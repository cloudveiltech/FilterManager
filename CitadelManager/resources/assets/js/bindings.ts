/**
 * Created by Kent on 1/28/2019.
 */

namespace Citadel {
    export class BindingInstance {
        VALUE_BIND = 'value-bind';
        ELEM_BIND = 'elem-bind';
        TEXT_BIND = 'text-bind';

        /*
         * The binding instance has to synchronize two different ways:
         * View <- Model
         * View -> Model
         *
         * View -> Model
         * This direction is accomplished by element event listeners updating the model.
         *
         * View <- Model
         * This direction is accomplished by calling Refresh() manually after a change to the model properties.
         */
        private element: any;
        private model: any;

        private eventTypes: Array<string> = ['click', 'submit', 'change'];
        private eventListeners: Array<any> = [];

        private boundElements: any;

        private eventElements: any;

        private bindingTypes: object = {
            'value-bind': this.bindValueBinding,
            'num-value-bind': this.bindNumValueBinding,
            'elem-bind': this.bindElementBinding,
            'text-bind': this.bindTextBinding
        };

        private bindings: Array<any>;

        constructor(element: any, model: any) {
            this.element = element;
            this.model = model;
        }

        public Bind(): void {
            this.boundElements = {};
            this.eventElements = {};
            this.bindings = [];

            for(var bindingType in this.bindingTypes) {
                this.boundElements[bindingType] = this.element.querySelectorAll("[" + bindingType + "]");
                this.buildBindings(this.boundElements[bindingType], this.bindings, bindingType);
            }

            for(var eventType of this.eventTypes) {
                this.eventElements[eventType] = this.element.querySelectorAll("[event-" + eventType + "]");
            }

            this.triggerBindings(this.bindings);

            this.buildEventBindings();
        }

        public Unbind(): void {
            if(this.eventListeners) {
                for(var listenerObj of this.eventListeners) {
                    listenerObj.elem.removeEventListener(listenerObj.type, listenerObj.fn);
                }

                this.eventListeners = null;
            }
        }

        public Refresh(): void {
            for(var binding of this.bindings) {
                if(binding.calls && binding.calls.onmodelupdate) {
                    binding.calls.onmodelupdate();
                }
            }
        }

        private buildEventBindings(): void {
            function generateEventBinding(model, elem, eventType) {
                var attr = elem.attributes["event-" + eventType];
                var prop = attr.value;

                var fn = new Function('_', 'e', '_.' + prop + '(e)'); // equivalent to function(_, event) { _[prop](event); }
                return function(e) {
                    var r = fn(model, e);
                    return r === undefined ? false : r;
                }
            }

            for(var eventType in this.eventElements) {
                for(var elem of this.eventElements[eventType]) {
                    var eventFn = generateEventBinding(this.model, elem, eventType);

                    this.addListenerTo(elem, eventType, eventFn);
                }
            }
        }

        private triggerBindings(bindings: Array<any>): void {
            for(var binding of bindings) {
                if(binding.bindingType in this.bindingTypes) {
                    this.bindingTypes[binding.bindingType].call(this, binding);
                }
            }
        }

        private addListenerTo(elem: any, eventType: string, fn: any) {
            this.eventListeners = this.eventListeners || [];

            elem.addEventListener(eventType, fn);

            this.eventListeners.push({
                elem: elem,
                type: eventType,
                fn: fn
            });
        }

        private bindValueBinding(binding: any): void {
            let that = this;

            binding.isCheckbox = binding.target.attributes.type && binding.target.attributes.type.value == "checkbox";

            binding.calls = {
                onmodelupdate: function() {
                    let newValue = binding.get(that.model);
                    let prop = binding.isCheckbox ? 'checked' : 'value';
                    binding.target[prop] = newValue;
                },

                onviewupdate: function() {
                    let viewValue = (binding.isCheckbox) ? binding.target.checked : binding.target.value;
                    binding.set(that.model, viewValue);
                }
            };

            var eventType = binding.isCheckbox ? 'change' : 'input';
            that.addListenerTo(binding.target, eventType, function() {
                binding.calls.onviewupdate();
            });
        }

        private bindNumValueBinding(binding: any): void {
            let that = this;

            binding.calls = {
                onmodelupdate: function() {
                    let newValue = binding.get(that.model);

                    binding.target.value = newValue;
                },

                onviewupdate: function() {
                    let viewValue = binding.target.valueAsNumber;
                    binding.set(that.model, viewValue);
                }
            };

            that.addListenerTo(binding.target, 'input', function() {
                binding.calls.onviewupdate();
            });
        }

        private bindElementBinding(binding: any): void {
            let that = this;
            binding.calls = {
                onmodelupdate: function() {
                    // This one's backwards from all the others we want to bind on refresh.
                    binding.set(that.model, binding.target);
                },

                onviewupdate: function() {
                    // Do nothing
                }
            }
        }

        private bindTextBinding(binding: any): void {
            let that = this;

            binding.calls = {
                onmodelupdate: function() {
                    binding.target.innerText = binding.get(that.model);
                },

                onviewupdate: function() {
                    // Do nothing, this is a one-way binding.
                }
            }
        }

        private buildBindings(elements: Array<any>, bindings: Array<any>, bindingAttr: string): void {
            for(let elem of elements) {
                let propBindings = this.getPropManipulators(elem, elem.attributes[bindingAttr].value, bindingAttr);
                bindings.push(propBindings);
            }
        }

        /**
         * This function returns an object which allows us to get a property path from a particular object specified in either .get() or .set()
         *
         * @param target
         * @param prop
         * @param bindingAttr
         * @returns {{target: Element, bindingType: string, get: ((obj:any)=>(null|any)), set: ((obj:any, value:any)=>undefined)}}
         */
        private getPropManipulators(target: Element, prop: string, bindingAttr: string): any {
            var propParts = prop.split('.');
            propParts = propParts || [];

            return {
                target: target,
                bindingType: bindingAttr,

                get: function(obj) {
                    let o = obj;

                    if(propParts.length == 0) {
                        throw new Error("Invalid property binding");
                    }

                    for(var i = 0; i < propParts.length - 1; i++) {
                        if(!(propParts[i] in o)) {
                            return null;
                        }

                        o = o[propParts[i]];
                    }

                    if(propParts[i] in o) {
                        return o[propParts[i]];
                    } else {
                        return null;
                    }
                },

                set: function(obj, value) {
                    let o = obj;

                    if(propParts.length == 0) {
                        throw new Error("Invalid property binding");
                    }

                    // If we have more than one property part (e.g. objProp.innerProp), navigate to the value held by 'objProp'
                    for(var i = 0; i < propParts.length - 1; i++) {
                        if(!(propParts[i] in o)) {
                            o[propParts[i]] = {};
                        }

                        o = o[propParts[i]];
                    }

                    o[propParts[propParts.length - 1]] = value;
                }
            };
        }

    }
}