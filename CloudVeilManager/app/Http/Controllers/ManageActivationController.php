<?php

namespace App\Http\Controllers;

use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Http\Request;

class ManageActivationController extends Controller
{
    /**
     * Return activations for a user or a DataTables activation listing.
     */
    public function index(Request $request, $user_id = null)
    {
        $input = $request->all();

        if (array_key_exists('email', $input)) {
            $email = $input['email'];
            if ($email === null || $email === '') {
                return response()->json([]);
            }

            $user = User::where('email', $email)->first();

            return response()->json($user === null ? [] : $this->activationsForUser($user));
        }

        if ($user_id !== null || array_key_exists('user_id', $input)) {
            $requestedUserId = $user_id !== null ? $user_id : $input['user_id'];
            $user = User::find($requestedUserId);

            return response()->json($user === null ? [] : $this->activationsForUser($user));
        }

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = $request->input('length', 10);
        $length = $length === null || $length === '' ? 10 : (int) $length;
        if ($length < 1) {
            $length = 10;
        }

        $search = $request->input('search.value', '');
        $search = is_scalar($search) ? (string) $search : '';
        $showBanned = (int) $request->input('show_banned', 0);

        $orderColumns = [
            'id' => 'app_user_activations.id',
            'identifier' => 'app_user_activations.identifier',
            'device_id' => 'app_user_activations.device_id',
            'friendly_name' => 'app_user_activations.friendly_name',
            'ip_address' => 'app_user_activations.ip_address',
            'name' => 'users.name',
            'user.name' => 'users.name',
            'email' => 'users.email',
            'user.email' => 'users.email',
            'banned' => 'app_user_activations.banned',
            'created_at' => 'app_user_activations.created_at',
            'updated_at' => 'app_user_activations.updated_at',
        ];

        $orderName = 'id';
        $orderColumn = $request->input('order.0.column');
        if ($orderColumn !== null && $orderColumn !== '') {
            $requestedOrderName = $request->input('columns.' . (int) $orderColumn . '.data');
            if (is_string($requestedOrderName) && isset($orderColumns[$requestedOrderName])) {
                $orderName = $requestedOrderName;
            }
        }

        $orderDirection = strtoupper((string) $request->input('order.0.dir', 'ASC'));
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'ASC';
        }

        $recordsTotal = AppUserActivation::count();
        $query = AppUserActivation::query()
            ->leftJoin('users', 'users.id', '=', 'app_user_activations.user_id')
            ->select('app_user_activations.*', 'users.name')
            ->where('app_user_activations.banned', $showBanned);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('app_user_activations.device_id', 'like', "%{$search}%")
                    ->orWhere('app_user_activations.identifier', 'like', "%{$search}%")
                    ->orWhere('app_user_activations.friendly_name', 'like', "%{$search}%")
                    ->orWhere('app_user_activations.ip_address', 'like', "%{$search}%");
            });
        }

        $query->orderBy($orderColumns[$orderName], $orderDirection);

        $recordsFiltered = (clone $query)->count();
        $rows = $query->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Block an activation.
     */
    public function block($id)
    {
        $activation = AppUserActivation::where('id', $id)->first();
        if ($activation !== null) {
            $activation->banned = 1;
            $activation->save();
        }

        return response('', 204);
    }

    /**
     * Delete an activation when it exists.
     */
    public function destroy($id)
    {
        $activation = AppUserActivation::where('id', $id)->first();
        if ($activation !== null) {
            $activation->delete();
        }

        return response('', 204);
    }

    private function activationsForUser(User $user)
    {
        return $user->activations()
            ->with(['deactivation_request', 'group'])
            ->get();
    }
}
