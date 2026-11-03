@php
    $horizontalTabs = $crud->getTabsType()=='horizontal' ? true : false;
    $tabWithError = (function() use ($crud) {
        if(! session()->get('errors')) {
            return false;
        }
        foreach(session()->get('errors')->getBags() as $bag => $errorMessages) {
            foreach($errorMessages->getMessages() as $fieldName => $messages) {

                // extract the "parent" field name by separating the field name string by `.digit.` (eg. relation.0.fieldName)
                // and take the first element,  AKA the "parent" name. In the above example: `relation` 
                $fieldName = preg_split('/\.\d+\./',$fieldName)[0];

                if(array_key_exists($fieldName, $crud->getCurrentFields()) && array_key_exists('tab', $crud->getCurrentFields()[$fieldName])) {
                    return $crud->getCurrentFields()[$fieldName]['tab'];
                }
            }
        }
        return false;
    })();
    // Opt-in per operation (CRUD::setOperationSetting('tabBadges', true)): each tab shows a small
    // count of what's set in it, kept live by the script at the bottom.
    $tabBadges = (bool) $crud->getOperationSetting('tabBadges');
@endphp

@if ($crud->getFieldsWithoutATab()->filter(function ($value, $key) { return $value['type'] != 'hidden'; })->count())
<div class="card">
    <div class="card-body row">
    @include('crud::inc.show_fields', ['fields' => $crud->getFieldsWithoutATab()])
    </div>
</div>
@else
    @include('crud::inc.show_fields', ['fields' => $crud->getFieldsWithoutATab()])
@endif

<div class="tab-container {{ $horizontalTabs ? '' : 'container'}} mb-2">

    <div class="nav-tabs-custom {{ $horizontalTabs ? '' : 'row'}}" id="form_tabs">
        <ul class="nav {{ $horizontalTabs ? 'nav-tabs' : 'flex-column nav-pills'}} {{ $horizontalTabs ? '' : 'col-md-3' }}" role="tablist">
            @foreach ($crud->getTabs() as $k => $tab)
            @php
                $tabSlug = Str::slug($tab);
                if(empty($tabSlug)) {
                    $tabSlug = $k;
                }
            @endphp
                <li role="presentation" class="nav-item">
                    <a href="#tab_{{ $tabSlug }}"
                        aria-controls="tab_{{ $tabSlug }}"
                        role="tab"
                        data-toggle="tab" {{-- tab indicator for Bootstrap v4 --}}
                        tab_name="{{ $tabSlug }}" {{-- tab name for Bootstrap v4 --}}
                        data-name="{{ $tabSlug }}" {{-- tab name for Bootstrap v5 --}}
                        data-bs-toggle="tab" {{-- tab name for Bootstrap v5 --}}
                        class="nav-link text-decoration-none {{ isset($tabWithError) && $tabWithError ? ($tab == $tabWithError ? 'active' : '') : ($k == 0 ? 'active' : '') }}"
                        >{{ $tab }}@if ($tabBadges)<span class="badge bg-blue-lt ms-1 tab-count-badge" hidden></span>@endif</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content {{$horizontalTabs ? '' : 'col-md-9'}}">

            @foreach ($crud->getTabs() as $k => $tabLabel)
            @php
                $tabSlug = Str::slug($tabLabel);
                if(empty($tabSlug)) {
                    $tabSlug = $k;
                }
            @endphp
            <div role="tabpanel" class="tab-pane {{ isset($tabWithError) && $tabWithError ? ($tabLabel == $tabWithError ? ' active' : '') : ($k == 0 ? ' active' : '') }}" id="tab_{{ $tabSlug }}">

                <div class="row">
                    @include('crud::inc.show_fields', ['fields' => $crud->getTabItems($tabLabel, 'fields')])
                </div>
            </div>
            @endforeach

        </div>
    </div>
</div>

@push('crud_fields_styles')
    <style>
        .nav-tabs-custom {
            box-shadow: none;
        }
        .nav-tabs-custom > .nav-tabs.nav-stacked > li {
            margin-right: 0;
        }

        .tab-pane .form-group h1:first-child,
        .tab-pane .form-group h2:first-child,
        .tab-pane .form-group h3:first-child {
            margin-top: 0;
        }

        /*
            when select2 is multiple and it's not on the first displayed tab the placeholder would
            not display correctly because the element was not "visible" on the page (hidden by tab)
            thus getting `0px` width. This makes sure that the placeholder element is always 100% width
            by preventing the select2 inline style (0px) from applying using !important
        */
        .select2-search__field {
            width: 100% !important;
        }
    </style>
@endpush

@if ($tabBadges)
@push('crud_fields_styles')
    <style>
        .tab-count-badge { font-size: .7rem; padding: .15em .45em; vertical-align: text-top; }
    </style>
@endpush

@push('crud_fields_scripts')
    @bassetBlock('cloudveil/crud/tab-count-badges.js')
    <script>
        (function () {
            var $ = window.jQuery;

            function parseJson(value, fallback) {
                try {
                    return JSON.parse(value || '') || fallback;
                } catch (e) {
                    return fallback;
                }
            }

            // How many things each countable field type holds, keyed by bp-field-type.
            var COUNTERS = {
                // Category overrides: one entry per non-default row in the field's JSON payload.
                rule_selection_table: function ($field) {
                    return Object.keys(parseJson($field.find('.rule-status-json').val(), {})).length;
                },
                // Table (blocked sites, triggers, apps…): rows with at least one non-blank cell.
                table: function ($field) {
                    return parseJson($field.find('input.array-json').val(), []).filter(function (row) {
                        return Object.keys(row || {}).some(function (key) {
                            return String(row[key] ?? '').trim() !== '';
                        });
                    }).length;
                },
                // Repeatable: rows switched on when the rows have an "enabled" switch (time
                // restrictions), otherwise every row.
                repeatable: function ($field) {
                    var $rows = $field.find('.container-repeatable-elements .repeatable-element');
                    var $enabled = $rows.find('input[type=hidden][data-repeatable-input-name="enabled"]');
                    if (!$enabled.length) {
                        return $rows.length;
                    }
                    return $enabled.filter(function () { return this.value === '1'; }).length;
                },
            };

            function updateBadges() {
                $('#form_tabs .tab-pane').each(function () {
                    var count = 0;
                    $(this).find('[bp-field-wrapper]').each(function () {
                        var counter = COUNTERS[this.getAttribute('bp-field-type')];
                        if (counter) {
                            count += counter($(this));
                        }
                    });
                    var $badge = $('#form_tabs a[href="#' + this.id + '"] .tab-count-badge');
                    $badge.text(count).prop('hidden', count === 0);
                });
            }

            // Field scripts update their hidden inputs after the triggering event, so recount on
            // the next tick.
            var pending = null;
            function scheduleUpdate() {
                clearTimeout(pending);
                pending = setTimeout(updateBadges, 0);
            }

            $(function () {
                $('#form_tabs .tab-content').on('change input click keyup', scheduleUpdate);
                scheduleUpdate();
            });
            $(window).on('load', scheduleUpdate);
        })();
    </script>
    @endBassetBlock
@endpush
@endif
