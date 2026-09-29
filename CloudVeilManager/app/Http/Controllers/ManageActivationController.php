<?php

namespace App\Http\Controllers;

use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Http\Request;

class ManageActivationController extends Controller
{
    /**
     * List activations for the external management API.
     *
     * An `email` lookup keeps the legacy bare array of that user's activations the
     * manage site reads; everything else is a standard paginated listing.
     */
    public function index(Request $request)
    {
        if ($request->has('email')) {
            $email = $request->input('email');
            $user = $email === null || $email === '' ? null : User::where('email', $email)->first();

            return response()->json($user === null ? [] : $this->activationsForUser($user));
        }

        $search = trim((string) $request->query('search', ''));

        return AppUserActivation::with(['user', 'group'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->has('banned'), fn ($query) => $query->where('banned', $request->boolean('banned')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('identifier', 'like', "%{$search}%")
                        ->orWhere('device_id', 'like', "%{$search}%")
                        ->orWhere('friendly_name', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();
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
