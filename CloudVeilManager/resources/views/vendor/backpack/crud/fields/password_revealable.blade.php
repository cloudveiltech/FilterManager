{{-- password_revealable --}}

@php
    $field['value'] = old_empty_or_null($field['name'], '') ?? ($field['value'] ?? ($field['default'] ?? ''));
    // autocomplete off, if not otherwise specified
    if (!isset($field['attributes']['autocomplete'])) {
        $field['attributes']['autocomplete'] = "off";
    }
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div class="input-group password-visibility-toggler">
        <input
            type="password"
            name="{{ $field['name'] }}"
            value="{{ $field['value'] }}"
            @include('crud::fields.inc.attributes')
        >
        <span class="input-group-text p-0 px-2">
            <a href="#" class="link-secondary p-2 password-toggle-btn" data-bs-toggle="tooltip" aria-label="Show password" title="Show password" style="cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                </svg>
            </a>
        </span>
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const passcodeField = document.querySelector('input[name="{{ $field['name'] }}"]');
        const toggleBtn = document.querySelector('input[name="{{ $field['name'] }}"]').closest('.password-visibility-toggler')?.querySelector('.password-toggle-btn');

        if (passcodeField && toggleBtn) {
            // Ensure initial type is password
            passcodeField.type = "password";

            toggleBtn.addEventListener("click", function(e) {
                e.preventDefault();
                const svg = toggleBtn.querySelector("svg");

                if (passcodeField.type === "password") {
                    passcodeField.type = "text";
                    svg.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" />';
                    svg.setAttribute("class", "icon icon-tabler icon-tabler-eye-off");
                    toggleBtn.setAttribute("aria-label", "Hide password");
                    toggleBtn.setAttribute("title", "Hide password");
                } else {
                    passcodeField.type = "password";
                    svg.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>';
                    svg.setAttribute("class", "icon icon-tabler icon-tabler-eye");
                    toggleBtn.setAttribute("aria-label", "Show password");
                    toggleBtn.setAttribute("title", "Show password");
                }
                passcodeField.focus();
            });
        }
    });
</script>
@endpush

