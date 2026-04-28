<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Events\LoginApprovedEvent;

class ActionApprovalController extends Controller
{
   public function approve(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            return response("This link has expired or is invalid.", 401);
        }

        $action = DB::table('pending_actions')->where('id', $id)->first();

        if(!$action || $action->status !== 'pending') {
            return view('admin.action_response', [
                'status' => 'rejected',
                'message' => 'This action has already been processed or is no longer valid.'
            ]);
        }

        DB::table('pending_actions')->where('id', $id)->update(['status' => 'approved']);
        $payload = json_decode($action->payload);

        if($action->action_type === 'admin_login') {
            $user = \App\Models\User::find($action->user_id);
            
            // NAYA: Purane tokens delete nahi karenge! 
            // Token ka naam wahi rakhenge jo payload se aayega
            $deviceName = $payload->device_info ?? 'Unknown Device';
            $token = $user->createToken($deviceName)->plainTextToken;

            // SessionId ke through WebSocket par token bhejenge
            $sessionId = $payload->session_id;
            broadcast(new \App\Events\LoginApprovedEvent($sessionId, 'approved', $token));
        }

        return view('admin.action_response', [
            'status' => 'approved',
            'message' => 'Login request successfully approved. The user is being redirected to the dashboard.'
        ]);
    }

  public function reject(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            return response("This link has expired or is invalid.", 401);
        }

        // 1. Fetch the pending action to get the payload (session_id)
        $action = DB::table('pending_actions')->where('id', $id)->first();

        if(!$action || $action->status !== 'pending') {
            return view('admin.action_response', [
                'status' => 'rejected',
                'message' => 'This action has already been processed or is no longer valid.'
            ]);
        }

        // 2. Mark the action as rejected in the database
        DB::table('pending_actions')->where('id', $id)->update(['status' => 'rejected']);
        
        // 3. Extract the Reverb session ID from the payload
        $payload = json_decode($action->payload);
        $sessionId = $payload->session_id;

        // 4. Broadcast the rejection event back to the user's browser
        // Passing 'rejected' as status and null for the token
        broadcast(new \App\Events\LoginApprovedEvent($sessionId, 'rejected', null));

        // 5. Return the visual UI for the Admin
        return view('admin.action_response', [
            'status' => 'rejected',
            'message' => 'The login request has been successfully denied.'
        ]);
    }
}