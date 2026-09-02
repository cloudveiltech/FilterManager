{{-- Backwards-compatible alias for the generalized status assignment field. --}}
@php
    $field['type'] = 'status_assignment_table';
    $field['rows'] = collect($field['filter_lists'] ?? [])->map(function ($list) {
        return [
            'id' => data_get($list, 'id'),
            'label' => data_get($list, 'category'),
            'sublabel' => data_get($list, 'type'),
        ];
    });
    $field['input_name'] = $field['input_name'] ?? 'rule_status_json';
    $field['current_relation'] = $field['current_relation'] ?? 'assignedFilters';
    $field['label'] = $field['label'] ?? 'Rule Selection';
    $field['row_label'] = $field['row_label'] ?? 'Category';
    $field['row_sublabel'] = $field['row_sublabel'] ?? 'Type';
@endphp

@include('crud::fields.status_assignment_table', ['field' => $field])
