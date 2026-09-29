<?php

namespace App\Http\Controllers;

use App\Models\Helpers\Utils;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ManageUserController extends Controller
{
    /**
     * List users for the external management API.
     *
     * The manage site looks a user up with `?email=` and reads `data[0]`.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return User::with(['group:id,name', 'roles', 'activations'])
            ->when($request->has('email'), fn ($query) => $query->where('email', (string) $request->input('email')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('activations', fn ($query) => $query->where('identifier', $search));
                });
            })
            ->orderBy('email')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();
    }

    /**
     * Create a user through the external management API.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:password_verify',
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'required|exists:groups,id',
        ]);

        $input = $request->only([
            'name',
            'email',
            'password',
            'group_id',
            'customer_id',
            'provider',
            'provider_id',
            'activations_allowed',
            'isactive',
            'debug_enabled',
            'config_override',
        ]);
        $input['password'] = Hash::make($input['password']);

        if (array_key_exists('config_override', $input)) {
            $input['config_override'] = $this->purgeConfigOverride($input['config_override']);
        }

        $user = new User();
        $user->forceFill($input);
        $user->save();

        $role = Role::where('id', $request->input('role_id'))->first();
        $user->attachRole($role);

        return response('', 204);
    }

    /**
     * Update a user through the external management API.
     */
    public function update(Request $request, $id)
    {
        $user = User::where('id', $id)->first();
        if ($user === null) {
            return response('', 404);
        }

        $input = $request->all();

        if (array_key_exists('customer_id', $input)
            && $input['customer_id'] !== null
            && $input['customer_id'] !== ''
            && User::where('id', '!=', $id)->where('customer_id', $input['customer_id'])->exists()) {
            return response('customer_id is duplicated. please choose another customer_id', 403);
        }

        if (array_key_exists('email', $input)
            && User::where('id', '!=', $id)->where('email', $input['email'])->exists()) {
            return response('email address exists, please choose another email_address', 403);
        }

        $provider = array_key_exists('provider', $input) ? $input['provider'] : $user->provider;
        $providerId = array_key_exists('provider_id', $input) ? $input['provider_id'] : $user->provider_id;
        if (($provider !== null && $provider !== '')
            && ($providerId !== null && $providerId !== '')
            && (array_key_exists('provider', $input) || array_key_exists('provider_id', $input))
            && User::where('id', '!=', $id)
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->exists()) {
            return response('provider_id is duplicated. please choose another provider_id', 403);
        }

        $rules = [
            'name' => 'required',
            'email' => 'required',
        ];
        $includePassword = array_key_exists('password', $input)
            && array_key_exists('password_verify', $input);
        if ($includePassword) {
            $rules['password'] = 'required|same:password_verify';
        }

        $this->validate($request, $rules);

        if (array_key_exists('role_id', $input)) {
            $this->validate($request, [
                'role_id' => 'required|exists:roles,id',
            ]);
        }

        $allowedKeys = [
            'name',
            'email',
            'group_id',
            'customer_id',
            'provider',
            'provider_id',
            'activations_allowed',
            'isactive',
            'config_override',
            'relaxed_policy_passcode',
            'enable_relaxed_policy_passcode',
        ];
        $changes = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $input)) {
                $changes[$key] = $input[$key];
            }
        }

        if (array_key_exists('config_override', $changes)) {
            $changes['config_override'] = $this->purgeConfigOverride($changes['config_override']);
        }

        if ($includePassword) {
            $changes['password'] = Hash::make($input['password']);
        }

        $user->forceFill($changes);
        $user->save();

        if (array_key_exists('role_id', $input)) {
            $role = Role::where('id', $input['role_id'])->first();
            if (!$user->hasRole($role)) {
                $user->detachRoles();
                $user->attachRole($role);
            }
        }

        if (array_key_exists('isactive', $input) && (string) $input['isactive'] === '0') {
            foreach ($user->tokens as $token) {
                $token->revoke();
            }
        }

        return response('', 204);
    }

    private function purgeConfigOverride($configOverride)
    {
        $configOverride = Utils::purgeNullsFromJSONSelfModeration($configOverride);
        if (!is_string($configOverride)) {
            return $configOverride;
        }

        $decoded = json_decode($configOverride, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $configOverride;
    }
}
