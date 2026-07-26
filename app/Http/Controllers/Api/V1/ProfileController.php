<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Services\MediaConverterService;

class ProfileController extends Controller
{
    public function updateProfile(Request $request, MediaConverterService $converter)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Aapka Global Context function use kar rahe hain pehchanne ke liye
        $context = $this->getGlobalContext(); 
        
        // 1. Password Update Logic
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // Check if current password is correct (Assuming passwords are Hash::make in DB)
            // Agar plain text passwords hain (jaisa Member Auth me dikha), toh sirf '==' check karein
            $isPasswordCorrect = Hash::needsRehash($user->password) 
                                 ? ($request->current_password === $user->password) // Plain text fallback
                                 : Hash::check($request->current_password, $user->password); // Hashed

            if (!$isPasswordCorrect) {
                return response()->json(['status' => 'error', 'message' => 'Current password incorrect'], 400);
            }
            // Hamesha Hash karke save karein
            $user->password = Hash::make($request->new_password);
        }

        // 2. Profile Image Upload Logic
        if ($request->hasFile('profile_image')) {
            // Employee aur Member dono me image ka column shayad 'passport_photo' hai
            $photoColumn = 'passport_photo'; 

            // Delete old image if exists
            if (!empty($user->$photoColumn) && File::exists(public_path($user->$photoColumn))) {
                File::delete(public_path($user->$photoColumn));
            }

            // Convert and Upload new image
            $media = $converter->uploadAndConvert($request->file('profile_image'));
            if ($media) {
                $user->$photoColumn = $media->file_path;
            }
        }

        // 3. Other Details Update Logic
        if ($request->filled('mobile')) {
            // Employee ke liye column 'contact_no' hai, Member ke liye 'mobile'
            if ($context->is_employee) {
                $user->contact_no = $request->mobile;
            } else {
                $user->mobile = $request->mobile;
            }
        }

        if ($request->filled('address')) {
            $user->address = $request->address;
        }

        // Save all changes
        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Profile updated successfully!'
        ]);
    }
}