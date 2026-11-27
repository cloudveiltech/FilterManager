<?php

namespace App\Http\Controllers\Admin\Traits;

use Closure;

/**
 * A "Filter check" line button that opens the filter check page for the row.
 */
trait AddsFilterCheckButton
{
    /**
     * @param  string  $target  The filter check target: group_id, user_id or activation_id.
     * @param  (Closure(mixed): bool)|null  $showFor  Which rows show the button; all rows when null.
     */
    protected function addFilterCheckButton(string $target, ?Closure $showFor = null): void
    {
        $this->crud->setAccessCondition('filterCheck', $showFor ?? fn () => true);

        $this->crud->button('filter_check')->stack('line')->view('crud::buttons.quick')->meta([
            'wrapper' => [
                'href' => fn ($entry) => backpack_url('filter-check').'?'.http_build_query([
                    'target' => $target,
                    'target_id' => $entry->getKey(),
                ]),
            ],
            'icon' => 'la la-filter',
            'access' => 'filterCheck',
            'label' => 'Filter check',
        ]);
    }
}
