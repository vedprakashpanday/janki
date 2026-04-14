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
        // 1. Signature check (Security ki link valid hai)
        if (! $request->hasValidSignature()) {
            return response("This link has expired or is invalid.", 401);
        }

        $action = DB::table('pending_actions')->where('id', $id)->first();

        // 2. Check if already processed
        if(!$action || $action->status !== 'pending') {
            return response("This action has already been processed.", 400);
        }

        // 3. Mark as Approved
        DB::table('pending_actions')->where('id', $id)->update(['status' => 'approved']);

        $payload = json_decode($action->payload);

        // 4. Agar action Admin Login ka tha
        if($action->action_type === 'admin_login') {
            $user = User::find($action->user_id);
            $token = $user->createToken('admin_auth_token')->plainTextToken;

           if($action->action_type === 'admin_login') {
    $user = User::find($action->user_id);
    $token = $user->createToken('admin_auth_token')->plainTextToken;

    // Payload se sessionId nikalna
    $payload = json_decode($action->payload);
    $sessionId = $payload->session_id;

    // Reverb ko signal bhejo!
    broadcast(new LoginApprovedEvent($sessionId, 'approved', $token));

    return response("<h3>Approved!</h3><p>User is being redirected...</p>");
}

       return view('admin.action_response', [
    'status' => 'approved',
    'message' => 'Login request successfully approved. User is being redirected.'
]);
    }
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