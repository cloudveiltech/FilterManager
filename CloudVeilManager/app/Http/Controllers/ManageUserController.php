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
     */
    public function index(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = $request->input('length');
        $length = $length === null || $length === '' ? 10 : (int) $length;
        if ($length < 1) {
            $length = 10;
        }

        $search = $request->input('search.value', '');
        $search = $search === null ? '' : $search;

        $orderName = 'email';
        $orderColumn = $request->input('order.0.column');
        if ($orderColumn !== null && $orderColumn !== '') {
            $orderName = $request->input('columns.' . (int) $orderColumn . '.data', 'email');
        }

        $orderDirection = strtoupper((string) $request->input('order.0.dir', 'ASC'));
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'ASC';
        }

        $recordsTotal = User::count();

        $query = User::with(['group', 'roles', 'activations'])
            ->select('users.*');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereHas('activations', function ($query) use ($search) {
                        $query->where('identifier', $search);
                    });
            });
        }

        $input = $request->all();
        foreach (['email', 'id', 'customer_id', 'provider_id'] as $filter) {
            if (!array_key_exists($filter, $input)) {
                continue;
            }

            $value = $input[$filter];
            if ($value === null || $value === '') {
                $query->whereRaw('0 = 1');
                continue;
            }

            if ($filter === 'provider_id') {
                $query->where('users.provider', 'cloudveil')
                    ->where('users.provider_id', $value);
            } else {
                $query->where('users.' . $filter, $value);
            }
        }

        $orderColumns = [
            'group.id' => 'groups.id',
            'name' => 'users.name',
            'email' => 'users.email',
            'activations_allowed' => 'users.activations_allowed',
            'isactive' => 'users.isactive',
            'created_at' => 'users.created_at',
        ];

        if ($orderName === 'group.name' || $orderName === 'group.id') {
            $query->leftJoin('groups', 'groups.id', '=', 'users.group_id')
                ->orderBy($orderColumns[$orderName], $orderDirection);
        } elseif ($orderName === 'roles[, ].display_name') {
            $query->leftJoin('role_user', 'role_user.user_id', '=', 'users.id')
                ->orderBy('role_user.role_id', $orderDirection);
        } else {
            $query->orderBy($orderColumns[$orderName] ?? 'users.email', $orderDirection);
        }

        $recordsFiltered = $query->count();
        $users = $query->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'search' => $search,
            'data' => $users,
        ]);
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
