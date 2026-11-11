<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FilterCheck\FilterChecker;
use App\Services\FilterCheck\FilterTargetResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * The admin "Filter check" page: would these URLs be blocked for a device, a
 * user, a group or the default group, and which lists decide it.
 */
class FilterCheckController extends Controller
{
    /** The page's target choices, mapped to the check's target fields. */
    public const TARGETS = [
        'default' => 'Default group',
        'group_id' => 'Group ID',
        'user_id' => 'User ID',
        'email' => 'User email',
        'activation_id' => 'Activation ID',
    ];

    public function index(Request $request, FilterTargetResolver $resolver, FilterChecker $checker)
    {
        $target = array_key_exists($request->query('target'), self::TARGETS) ? $request->query('target') : 'default';
        $urls = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $request->query('urls', '')))));

        $data = [
            'targets' => self::TARGETS,
            'target' => $target,
            'targetId' => (string) $request->query('target_id', ''),
            'urlText' => implode("\n", $urls),
            'bypassActive' => $request->boolean('bypass_active'),
            'report' => null,
            'checkErrors' => [],
        ];

        if ($urls === []) {
            return view('vendor.backpack.crud.filter_check', $data);
        }

        try {
            Validator::make([...$request->query(), 'urls' => $urls], [
                'urls' => ['array', 'max:'.config('filter_check.max_urls', 50)],
                'urls.*' => ['string', 'max:2048'],
                'target_id' => [$target === 'default' ? 'nullable' : 'required', 'string', 'max:255'],
            ], [], ['target_id' => self::TARGETS[$target]])->validate();

            $input = $target === 'default' ? [] : [$target => $data['targetId']];
            $data['report'] = $checker->check(
                $resolver->resolve($input),
                $urls,
                $data['bypassActive'],
            );
        } catch (ValidationException $e) {
            $data['checkErrors'] = $e->validator->errors()->all();
        }

        return view('vendor.backpack.crud.filter_check', $data);
    }
}
