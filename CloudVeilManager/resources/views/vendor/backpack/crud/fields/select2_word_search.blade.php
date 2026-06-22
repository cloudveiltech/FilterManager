{{-- select2 word search --}}
@php
    $current_value = old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '';

    // if it's part of a relationship here we have the full related model, we want the key.
    if (is_object($current_value) && is_subclass_of(get_class($current_value), 'Illuminate\Database\Eloquent\Model') ) {
        $current_value = $current_value->getKey();
    }
    if (!isset($field['options'])) {
        $options = $field['model']::all();
    } else {
        $options = call_user_func($field['options'], $field['model']::query());
    }
    $field['allows_null'] = $field['allows_null'] ?? $crud->model::isColumnNullable($field['name']);
    $field['placeholder'] = $field['placeholder'] ?? trans('backpack::crud.select_entry');
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select
        name="{{ $field['name'] }}"
        style="width: 100%"
        data-field-is-inline="{{var_export($inlineCreate ?? false)}}"
        data-init-function="bpFieldInitSelect2WordSearchElement"
        data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
        data-field-placeholder="{{$field['placeholder']}}"
        data-field-allow-clear="{{var_export($field['allows_null'])}}"
        @include('crud::fields.inc.attributes', ['default_class' =>  'form-control select2_field'])
        >

        @if ($field['allows_null'])
            <option value="">-</option>
        @endif

        @if (count($options))
            @foreach ($options as $option)
                @if($current_value == $option->getKey())
                    <option value="{{ $option->getKey() }}" selected>{{ $option->{$field['attribute']} }}</option>
                @else
                    <option value="{{ $option->getKey() }}">{{ $option->{$field['attribute']} }}</option>
                @endif
            @endforeach
        @endif
    </select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->fieldTypeNotLoaded($field))
    @php
        $crud->markFieldTypeAsLoaded($field);
    @endphp

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        {{-- include select2 css --}}
        @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css')
        @basset('https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css')
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        {{-- include select2 js --}}
        @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js')
        @if (app()->getLocale() !== 'en')
            @basset('https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js')
        @endif
        @bassetBlock('backpack/pro/fields/select2-word-search-field.js')
        <script>
            function bpSelect2AllWordsAnyOrderMatcher(params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }

                if (data.children && data.children.length > 0) {
                    var match = $.extend(true, {}, data);
                    match.children = [];

                    for (var childIndex = 0; childIndex < data.children.length; childIndex++) {
                        var child = bpSelect2AllWordsAnyOrderMatcher(params, data.children[childIndex]);

                        if (child != null) {
                            match.children.push(child);
                        }
                    }

                    return match.children.length > 0 ? match : null;
                }

                var text = (data.text || '').toString().toLowerCase();
                var words = params.term.toLowerCase().split(/\s+/).filter(function(word) {
                    return word.length > 0;
                });

                return words.every(function(word) {
                    return text.indexOf(word) !== -1;
                }) ? data : null;
            }

            function bpInitializeSelect2WordSearchElement(element) {
                let isFieldInline = element.data('field-is-inline');
                let placeholder = element.data('field-placeholder');
                let allowClear = element.data('field-allow-clear');

                element.select2({
                    theme: "bootstrap",
                    placeholder: placeholder,
                    allowClear: allowClear,
                    dropdownParent: isFieldInline ? $('#inline-create-dialog .modal-content') : $(document.body),
                    matcher: bpSelect2AllWordsAnyOrderMatcher
                });
            }

            function bpFieldInitSelect2WordSearchElement(element) {
                // element will be a jQuery wrapped DOM node
                if (!element.hasClass("select2-hidden-accessible"))
                {
                    bpInitializeSelect2WordSearchElement(element);
                }
            }
        </script>
        @endBassetBlock
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
