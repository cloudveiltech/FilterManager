# Custom Form Binding Documentation

The goal of this binding framework is to cut down on "code duplication" between HTML and Typescript.

## Binding types

### elem-bind

This binding type allows us to set a property on an object to the element reference on which this binding is declared.

```html
<h1 elem-bind="titleHeader"></h1>
```

### text-bind

This binding type allows us to set `innerText` on an element to a specified property.

### value-bind

This binding type allows us to set a property to the value of an input. This binding value
updates on 'oninput' or 'onchange', depending on whether it's a text input or a checkbox input.

```html
<input type="text" value-bind="model.email" />
```

## Inner workings

### `BindingFramework.bind(parentElem, model)`

This function binds the three bindings above to a model and returns a `BindingInstance`

### `BindingInstance.refresh()`

This function refreshes the bindings on the HTML elements based upon the model.