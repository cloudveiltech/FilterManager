<?php

use App\Models\FilterList;
use App\Models\Group;
use App\Services\GroupFilterAssignmentService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

afterEach(function () {
    Mockery::close();
});

test('it decodes the configured status payload and maps pivot flags', function () {
    $service = new GroupFilterAssignmentService();
    $request = Request::create('/', 'POST', [
        'group_status_json' => json_encode(['12' => 'whitelist']),
    ]);

    expect($service->statusesFromRequest($request, 'group_status_json'))
        ->toBe(['12' => 'whitelist'])
        ->and($service->pivotAttributesForStatus('bypass'))
        ->toBe([
            'as_blacklist' => 0,
            'as_whitelist' => 0,
            'as_bypass' => 1,
        ])
        ->and($service->statusFromPivot((object) [
            'as_blacklist' => 0,
            'as_whitelist' => 1,
            'as_bypass' => 0,
        ]))
        ->toBe('whitelist');
});

test('it does not sync or report a group when assignment flags are unchanged', function () {
    $assignedFilter = new FilterList();
    $assignedFilter->setAttribute('id', 12);
    $assignedFilter->setRelation('pivot', (object) [
        'as_blacklist' => 1,
        'as_whitelist' => 0,
        'as_bypass' => 0,
    ]);

    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('get')->once()->andReturn(collect([$assignedFilter]));
    $relation->shouldNotReceive('sync');

    $group = Mockery::mock(Group::class);
    $group->shouldReceive('assignedFilters')->once()->andReturn($relation);
    $group->shouldReceive('getKey')->never();

    expect((new GroupFilterAssignmentService())->syncGroupAssignments($group, [
        12 => 'blacklist',
    ]))->toBe([]);
});

test('it reports only changed group ids from the filter list side', function () {
    $assignedGroup = new Group();
    $assignedGroup->setAttribute('id', 7);
    $assignedGroup->setRelation('pivot', (object) [
        'as_blacklist' => 0,
        'as_whitelist' => 1,
        'as_bypass' => 0,
    ]);

    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('get')->once()->andReturn(collect([$assignedGroup]));
    $relation->shouldReceive('sync')->once()->with([
        7 => [
            'as_blacklist' => 1,
            'as_whitelist' => 0,
            'as_bypass' => 0,
        ],
    ]);

    $filterList = Mockery::mock(FilterList::class);
    $filterList->shouldReceive('groups')->once()->andReturn($relation);

    expect((new GroupFilterAssignmentService())->syncFilterListAssignments($filterList, [
        7 => 'blacklist',
    ]))->toBe([7]);
});

test('partial filter list updates preserve assignments outside the target groups', function () {
    $assignedWhitelistGroup = new Group();
    $assignedWhitelistGroup->setAttribute('id', 7);
    $assignedWhitelistGroup->setRelation('pivot', (object) [
        'as_blacklist' => 0,
        'as_whitelist' => 1,
        'as_bypass' => 0,
    ]);

    $assignedBypassGroup = new Group();
    $assignedBypassGroup->setAttribute('id', 9);
    $assignedBypassGroup->setRelation('pivot', (object) [
        'as_blacklist' => 0,
        'as_whitelist' => 0,
        'as_bypass' => 1,
    ]);

    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('get')->once()->andReturn(collect([
        $assignedWhitelistGroup,
        $assignedBypassGroup,
    ]));
    $relation->shouldReceive('sync')->once()->with([
        7 => [
            'as_blacklist' => 1,
            'as_whitelist' => 0,
            'as_bypass' => 0,
        ],
        9 => [
            'as_blacklist' => 0,
            'as_whitelist' => 0,
            'as_bypass' => 1,
        ],
    ]);

    $filterList = Mockery::mock(FilterList::class);
    $filterList->shouldReceive('groups')->once()->andReturn($relation);

    expect((new GroupFilterAssignmentService())->updateFilterListGroupAssignments($filterList, [
        7 => 'blacklist',
    ]))->toBe([7]);
});
