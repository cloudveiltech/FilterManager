<?php

namespace App\Http\Controllers;

use App\Events\DeactivationRequestGranted;
use App\Events\DeactivationRequestReceived;
use App\Models\AppUserActivation;
use App\Models\DeactivationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManageDeactivationController extends Controller
{
    /**
     * Update a deactivation request through the manage API.
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'granted' => 'required',
        ]);

        $deactivationRequest = DeactivationRequest::where('id', $id)->first();
        if ($deactivationRequest === null) {
            return response('', 404);
        }

        $input = $request->only(['granted']);
        $deactivationRequest->update($input);

        if ((int) $input['granted'] === 1) {
            try {
                event(new DeactivationRequestGranted($deactivationRequest));
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        return response('', 200);
    }

    /**
     * Create or update a deactivation request for an activation identifier.
     */
    public function apiCreateDeactivationRequest(Request $request)
    {
        $request->validate([
            'identifier' => [
                'required',
                'string',
                'exists:app_user_activations,identifier',
            ],
        ]);

        $identifier = $request->input('identifier');
        $activation = AppUserActivation::where('identifier', $identifier)->first();

        $deactivationRequest = DeactivationRequest::firstOrCreate([
            'identifier' => $identifier,
            'device_id' => $activation->device_id,
            'user_id' => $activation->user_id,
        ]);

        if ($request->input('approved')) {
            $deactivationRequest->update(['granted' => 1]);
            event(new DeactivationRequestGranted($deactivationRequest));
        } else {
            $deactivationRequest->update(['granted' => 0]);
            event(new DeactivationRequestReceived($deactivationRequest));
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
