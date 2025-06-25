<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Club;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\User;

class VotingController extends Controller
{
    /**
     * Get the list of candidates for a club.
     */
    public function getCandidates(Club $club)
    {
        $candidates = $club->candidates()->get();
        return response()->json($candidates);
    }

    /**
     * Add a new candidate to a club.
     */
    public function addCandidate(Request $request, Club $club)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isModerator()) {
            return response()->json(['message' => 'You are not authorized to add candidates.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $candidate = $club->candidates()->create([
            'name' => $request->name,
        ]);

        return response()->json($candidate, 201);
    }

    /**
     * Cast a vote for a candidate in a club.
     */
    public function vote(Request $request, Club $club)
    {

        // Check if voting is open
        if (getVotingStatus() !== 'open') {
            return response()->json(['message' => 'Voting is currently closed.'], 403);
        }

        $validated = $request->validate([
            'candidate_ids'   => 'required|array|max:6',
            'candidate_ids.*' => 'required|exists:candidates,id',
        ]);

        $user = Auth::user();

        // Ensure user is a member of the club
        if (!$user->clubs()->where('club_id', $club->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this club.'], 403);
        }

        $candidateIds = $validated['candidate_ids'];

        // Ensure all candidates belong to the correct club
        $candidatesInClubCount = Candidate::where('club_id', $club->id)->whereIn('id', $candidateIds)->count();
        if ($candidatesInClubCount !== count($candidateIds)) {
            return response()->json(['message' => 'One or more candidates do not belong to this club.'], 422);
        }

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();
        try {
            // Check how many votes the user has already cast in this club's election
            $existingVotesCount = Vote::where('user_id', $user->id)
                ->where('club_id', $club->id)
                ->count();

            if ($existingVotesCount + count($candidateIds) > 6) {
                DB::rollBack();
                return response()->json(['message' => 'You cannot cast more than 6 votes in total.'], 409);
            }

            // Check if the user has already voted for any of these candidates
            $alreadyVotedFor = Vote::where('user_id', $user->id)
                ->whereIn('candidate_id', $candidateIds)
                ->exists();

            if ($alreadyVotedFor) {
                DB::rollBack();
                return response()->json(['message' => 'You have already voted for one or more of these candidates.'], 409);
            }

            // Create the votes
            $votes = [];
            foreach ($candidateIds as $candidateId) {
                $votes[] = Vote::create([
                    'user_id' => $user->id,
                    'candidate_id' => $candidateId,
                    'club_id' => $club->id,
                ]);
            }

            DB::commit();

            return response()->json($votes, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // For debugging, you might want to log the actual error: \Log::error($e);
            return response()->json(['message' => 'An error occurred while casting your vote.'], 500);
        }
    }

    /**
     * Get the voting results for a club.
     */
    public function results(Club $club)
    {
        $results = Candidate::where('club_id', $club->id)
            ->withCount('votes')
            ->orderBy('votes_count', 'desc')
            ->get();

        return response()->json($results);
    }

    /**
     * Get the current user's voting status for a club.
     */
    public function getVoteStatus(Club $club)
    {
        $user = Auth::user();

        $votes = Vote::where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->get();

        $votedForCandidates = $votes->pluck('candidate_id');

        return response()->json([
            'votes_cast' => $votes->count(),
            'voted_for_candidates' => $votedForCandidates,
        ]);
    }

    // Open/Close voting
    public function toggleSystemVoting(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isModerator()) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $request->validate([
            'voting_status' => 'required|in:open,closed',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'voting_status'],
            ['value' => $request->voting_status, 'updated_at' => now()]
        );

        // Notify all verified users that the voting status has changed
        $status = $request->voting_status; // open or closed
        User::where('is_verified', true)->select('id')->chunk(200, function ($users) use ($user, $status) {
            foreach ($users as $u) {
                Notification::create([
                    'user_id'   => $u->id,
                    'sender_id' => $user->id,
                    'type'      => 'voting_status',
                    'data'      => [
                        'status'  => $status,
                        'message' => "Voting has been {$status}!",
                        'url'     => url('/clubs'),
                    ],
                ]);
            }
        });

        return response()->json(['message' => 'System voting status updated.']);
    }

    // Get voting status for whole system
    public function getVotingStatus()
    {
        $votingStatus = DB::table('settings')->where('key', 'voting_status')->value('value');
        return response()->json(['voting_status' => $votingStatus]);
    }


}
