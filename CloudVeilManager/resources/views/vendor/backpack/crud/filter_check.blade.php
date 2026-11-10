@extends(backpack_view('blank'))

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Filter check' => false,
    ];
    $verdicts = [
        'allowed' => ['Allowed', 'bg-success'],
        'blocked' => ['Blocked', 'bg-danger'],
        'blocked_bypassable' => ['Blocked (bypassable)', 'bg-warning text-dark'],
        'time_restricted' => ['Blocked (time restriction)', 'bg-danger'],
    ];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="mb-0" bp-section="page-heading">Filter check</h1>
        <p class="ms-2 ml-2 mb-0 text-muted" bp-section="page-subheading">Would these sites be blocked, and by which lists?</p>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <form method="get" action="{{ backpack_url('filter-check') }}" class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="target">Check against</label>
                        <select name="target" id="target" class="form-select form-control">
                            @foreach ($targets as $value => $label)
                                <option value="{{ $value }}" @selected($value === $target)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="target_id">ID or email</label>
                        <input type="text" name="target_id" id="target_id" class="form-control" value="{{ $targetId }}" placeholder="Leave blank for the default group">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="urls">Sites to check, one per line</label>
                        <textarea name="urls" id="urls" rows="6" class="form-control" placeholder="example.com&#10;https://example.com/page" required>{{ $urlText }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="at">At this device time (optional)</label>
                        <input type="datetime-local" name="at" id="at" class="form-control" value="{{ $at }}">
                        <small class="text-muted">Checks time restrictions as well.</small>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="bypass_active" id="bypass_active" value="1" class="form-check-input" @checked($bypassActive)>
                        <label class="form-check-label" for="bypass_active">Relaxed policy (bypass) is on</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Check</button>
                </div>
            </form>
        </div>

        <div class="col-lg-8">
            @if ($checkErrors)
                <div class="alert alert-danger">
                    <ul class="list-unstyled mb-0">
                        @foreach ($checkErrors as $message)
                            <li><i class="la la-info-circle"></i> {{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($report)
                <div class="card mb-3">
                    <div class="card-body">
                        Checked against
                        <strong>{{ str_replace('_', ' ', $report['target']['type']) }}</strong>
                        @if ($report['target']['activation_id'])
                            activation #{{ $report['target']['activation_id'] }},
                        @endif
                        @if ($report['target']['user_id'])
                            user #{{ $report['target']['user_id'] }},
                        @endif
                        group <a href="{{ backpack_url('group/'.$report['target']['group']['id'].'/edit') }}">{{ $report['target']['group']['name'] }}</a>.
                        @if ($report['time_restriction'])
                            <br>Time restriction on {{ ucfirst($report['time_restriction']['day']) }} at {{ $report['time_restriction']['at'] }}:
                            {{ $report['time_restriction']['restricted'] ? 'internet is off' : 'not in effect' }}.
                        @endif
                    </div>
                </div>

                @foreach ($report['results'] as $result)
                    <div class="card mb-3">
                        <div class="card-body">
                            @if (isset($result['error']))
                                <strong>{{ $result['url'] }}</strong>
                                <span class="badge bg-secondary ms-2">{{ $result['error'] }}</span>
                            @else
                                @php([$label, $class] = $verdicts[$result['verdict']])
                                <div class="d-flex align-items-baseline flex-wrap gap-2">
                                    <strong class="text-break">{{ $result['checked_url'] }}</strong>
                                    <span class="badge {{ $class }}">{{ $label }}</span>
                                    @if ($result['mac']['differs'])
                                        <span class="badge bg-info text-dark">Mac may {{ $result['mac']['blocked'] ? 'block' : 'allow' }} this</span>
                                    @endif
                                </div>

                                @if ($result['decided_by'])
                                    <p class="mb-2 mt-2">
                                        Decided by <strong>{{ $result['decided_by']['category'] }}</strong>
                                        ({{ $result['decided_by']['list_type'] }}, {{ $result['decided_by']['source'] }} list),
                                        rule <code>{{ $result['decided_by']['rule'] }}</code>.
                                        @if ($result['host_passthrough'])
                                            The whole site is allowed, so the apps don't inspect its pages.
                                        @endif
                                    </p>
                                @else
                                    <p class="mb-2 mt-2 text-muted">No list matches this site.</p>
                                @endif

                                @if ($result['matches'])
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr><th>List</th><th>Type</th><th>Effect</th><th>Rule</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($result['matches'] as $match)
                                                <tr>
                                                    <td>{{ $match['category'] }} <small class="text-muted">({{ $match['source'] }})</small></td>
                                                    <td>{{ $match['list_type'] }}</td>
                                                    <td>
                                                        <span class="badge {{ $match['effect'] === 'allow' ? 'bg-success' : 'bg-danger' }}">{{ $match['effect'] }}</span>
                                                    </td>
                                                    <td><code class="text-break">{{ $match['rule'] }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach

                @foreach ($report['notes'] as $note)
                    <p class="text-muted small mb-1">{{ $note }}</p>
                @endforeach
            @endif
        </div>
    </div>
@endsection
