<?php

/*
 * Copyright © 2017 Jesse Nicholson, 2019 CloudVeil Technology, Inc.
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Models\AppUserActivation;
use App\Models\DeactivationRequest;
use App\Events\DeactivationRequestGranted;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\Group;
use App\Models\OauthAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Models\Helpers\UserActivationAttemptResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller {

	public function getDeactivationRequests(Request $request) {
		$user = $request->user();

		if(!$user->can(['all', 'manage-deactivations'])) {
			return response(json_encode(['error' => 'You do not have permission to manage your deactivation requests.']), 400);
		}

		$requests = DeactivationRequest::where('user_id', $user->id)->get();

		return $requests;
	}

	public function grantDeactivationRequest($id, Request $request) {
		$user = $request->user();

		if(!$user->can(['all', 'manage-deactivations'])) {
			return response(json_encode(['error' => 'You do not have permission to manage your deactivation requests.']), 400);
		}

		$deactivationReq = DeactivationRequest::where('id', $id)->first();

		if(!is_null($deactivationReq)) {
			$deactivationReq->granted = true;
			$deactivationReq->save();
            try {
                event(new DeactivationRequestGranted($deactivationReq));
            } catch (\Exception $e) {
                Log::error($e);
            }
		}

		return response('', 204);
	}

	public function deleteDeactivationRequest($id) {
		$user = Auth::user();

		if(!$user->can(['all', 'manage-deactivations'])) {
			return response(json_encode(['error' => 'You do not have permission to manage your deactivation requests.']), 400);
		}

		$deactivationReq = DeactivationRequest::where('id', $id)->first();

		if(!is_null($deactivationReq)) {
			$deactivationReq->delete();
			return response('', 204);
		} else {
			return response(json_encode(['error' => 'Could not find deactivation request to delete.']), 404);
		}
	}

	public function getActivations(Request $request) {
		$user = $request->user();

		if(!$user->can(['all', 'manage-own-activations'])) {
			return response(json_encode(['error' => 'You do not have permission to manage your activations.']), 400);
		}

		$activations = AppUserActivation::where('user_id', $user->id)->get();

		return $activations;
	}

	public function destroyActivation($id) {
		$user = Auth::user();

		$activation = AppUserActivation::where('id', $id)->first();
		if($user->can(['all', 'delete-activations']) && $this->verifyAppUserActivation($user, $activation)) {
			$activation->delete();
			return response('', 204);
		} else {
			return response(json_encode(['error' => 'You do not have permission to manage your activations.']), 400);
		}
	}

	public function blockActivation($id) {
		$user = Auth::user();

		$activation = AppUserActivation::where('id', $id)->first();
		if ($user->can(['all', 'delete-activations']) && $this->verifyAppUserActivation($user, $activation)) {
			// If we're blocking the activation we go in and revoke the token for that installation.
			if (!is_null($activation->token_id)) {
				$token = OauthAccessToken::where('id', $activation->token_id)->first();
				if (!is_null($token)) {
					$token->revoked = 1;
					$token->save();
				}
			}
			$activation->delete();
			return response('', 204);
		} else {
			return response(json_encode(['error' => 'You do not have permission to manage your activations.']), 400);
		}
	}

	private function verifyAppUserActivation(User $user, AppUserActivation $activation) {
		if(!$user || !$activation) {
			return false;
		}

		if($user->id != $activation->user_id) {
			return false;
		}

		return true;
	}
}
