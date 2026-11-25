<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FilterCheck\FilterChecker;
use App\Services\FilterCheck\FilterTargetResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        // Reject non-string query values (e.g. ?target[]=x) before anything reads them.
        $query = Validator::make($request->query(), [
            'target' => ['nullable', 'string'],
            'target_id' => ['nullable', 'string', 'max:255'],
            'urls' => ['nullable', 'string'],
            'bypass_active' => ['nullable', 'boolean'],
        ]);
        $valid = $query->fails() ? [] : $query->validated();

        $target = array_key_exists($valid['target'] ?? '', self::TARGETS) ? $valid['target'] : 'default';
        $urls = array_values(array_filter(array_map('trim', preg_split('/\R/', $valid['urls'] ?? ''))));

        $data = [
            'targets' => self::TARGETS,
            'target' => $target,
            'targetId' => $valid['target_id'] ?? '',
            'urlText' => implode("\n", $urls),
            'bypassActive' => filter_var($valid['bypass_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'report' => null,
            'checkErrors' => $query->errors()->all(),
        ];

        if ($urls === [] || $data['checkErrors'] !== []) {
            return view('vendor.backpack.crud.filter_check', $data);
        }

        try {
            Validator::make(['urls' => $urls, 'target_id' => $data['targetId']], [
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
        } catch (RuntimeException $e) {
            // grep failed or timed out, or no configuration could be built for the target.
            report($e);
            $data['checkErrors'] = [$e->getMessage()];
        }

        return view('vendor.backpack.crud.filter_check', $data);
    }
}
