<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMission;
use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; // For date handling

class UserMissionController extends Controller
{
    /**
     * Display active missions for the authenticated user.
     * Missions are active if their 'tanggal_aktif' is today.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeMissions(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get today's date
        $today = Carbon::today()->toDateString();

        // Retrieve user missions that are active today and associated with the user
        $userMissions = UserMission::with('mission')
            ->where('user_id', $user->id)
            ->whereHas('mission', function ($query) use ($today) {
                $query->whereDate('tanggal_aktif', $today);
            })
            ->get();

        // Return the active missions
        return response()->json([
            'message' => 'Active missions retrieved successfully.',
            'missions' => $userMissions,
        ]);
    }

    /**
     * Display the progress of a specific user mission.
     *
     * @param  int  $userMissionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showMissionProgress($userMissionId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the user mission by ID and ensure it belongs to the authenticated user
        $userMission = UserMission::with('mission')
            ->where('id', $userMissionId)
            ->where('user_id', $user->id)
            ->first();

        // If user mission not found, return error
        if (!$userMission) {
            return response()->json(['message' => 'User mission not found or does not belong to you.'], 404);
        }

        // Return the mission progress
        return response()->json([
            'message' => 'Mission progress retrieved successfully.',
            'mission_progress' => [
                'id' => $userMission->id,
                'title' => $userMission->mission->title,
                'description' => $userMission->mission->description,
                'points' => $userMission->mission->points,
                'is_completed' => $userMission->is_completed,
                'proof' => $userMission->proof ? Storage::url($userMission->proof) : null, // Get public URL for proof
            ],
        ]);
    }

    /**
     * Receive proof of mission completion from the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userMissionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitMissionProof(Request $request, $userMissionId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'proof_url' => 'required|url|max:2048', // Ensure it's a valid URL and not too long
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user mission by ID and ensure it belongs to the authenticated user
        $userMission = UserMission::where('id', $userMissionId)
            ->where('user_id', $user->id)
            ->first();

        // If user mission not found or already completed, return error
        if (!$userMission) {
            return response()->json(['message' => 'User mission not found or does not belong to you.'], 404);
        }

        if ($userMission->is_completed) {
            return response()->json(['message' => 'Mission already completed.'], 400);
        }

        // Store the uploaded proof file
        $userMission->proof = $request->proof_url;
        //$userMission->is_completed = true; // Mark as completed after proof submission
        $userMission->save();

        // Add points to the user
        //$user->points += $userMission->mission->points;
        //$user->save();

        // Return success response
        return response()->json([
            'message' => 'Mission proof submitted successfully and points added.',
            'user_mission' => $userMission,
            'current_points' => $user->points,
            'proof_url' => $userMission->proof,
        ], 200);

        // If no file was uploaded (should be caught by validation, but as a fallback)
        return response()->json(['message' => 'No proof file provided.'], 400);
    }
}