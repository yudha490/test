<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserMission;
use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
                'status' => $userMission->status, // Mengembalikan status
                'proof' => $userMission->proof,
            ],
        ]);
    }

    /**
     * Receive proof of mission completion from the user as a URL.
     * The mission status will be set to 'pending' after submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userMissionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitMissionProof(Request $request, $userMissionId)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'proof_file' => 'required|file|mimes:jpeg,png,jpg,mp4,mov,avi|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $userMission = UserMission::where('id', $userMissionId)
                ->where('user_id', $user->id)
                ->first();

            if (!$userMission) {
                return response()->json(['message' => 'User mission not found or does not belong to you.'], 404);
            }

            if ($userMission->status == 'pending' || $userMission->status == 'selesai') {
                return response()->json(['message' => 'Mission cannot be submitted. Its status is already ' . $userMission->status . '.'], 400);
            }

            if ($request->hasFile('proof_file')) {
                $file = $request->file('proof_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads'), $filename);
                $fileUrl = url('uploads/' . $filename);

                $userMission->proof = $fileUrl;
                $userMission->status = 'pending';
                $userMission->save();

                return response()->json([
                    'message' => 'Mission proof uploaded successfully.',
                    'user_mission' => $userMission,
                    'file_url' => $fileUrl,
                ], 200);
            }

            return response()->json(['message' => 'No file was uploaded.'], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Get user missions history for a specific user, filtered by status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserMissionsHistory(Request $request, $userId)
    {
        // Pastikan hanya user yang bersangkutan atau admin yang bisa mengakses
        if (Auth::id() != $userId) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $query = UserMission::with('mission')
            ->where('user_id', $userId);

        // REVISI: Filter berdasarkan status jika disediakan dalam request
        if ($request->has('statuses') && is_array($request->input('statuses'))) {
            $query->whereIn('status', $request->input('statuses'));
        }

        // Tambahkan pengurutan berdasarkan tanggal terbaru
        $userMissions = $query->orderBy('created_at', 'desc')->get(); // Mengurutkan berdasarkan waktu dibuat terbaru

        return response()->json([
            'message' => 'User missions history retrieved successfully.',
            'user_missions' => $userMissions,
        ]);
    }
}
