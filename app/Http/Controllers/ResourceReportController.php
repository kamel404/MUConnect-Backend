<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Resource;
use App\Models\ResourceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResourceReportController extends Controller
{
    private const REPORT_DELETE_THRESHOLD = 5;

    /**
     * Report a resource (one report per user per resource).
     * Auto-deletes resource once open reports reach threshold.
     */
    public function store(Request $request, $resourceId)
    {
        $user = Auth::user();

        $resource = Resource::with('attachments')->findOrFail($resourceId);

        $validated = $request->validate([
            'reason' => 'required|string|max:100',
            'details' => 'nullable|string|max:1000',
        ]);

        $exists = ResourceReport::query()
            ->where('resource_id', $resource->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You already reported this resource'], 409);
        }

        DB::beginTransaction();
        try {
            ResourceReport::create([
                'resource_id' => $resource->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => ResourceReport::STATUS_OPEN,
            ]);

            $openCount = ResourceReport::query()
                ->where('resource_id', $resource->id)
                ->open()
                ->count();

            if ($openCount >= self::REPORT_DELETE_THRESHOLD) {
                $this->deleteResourceAndOrphanAttachments($resource);

                DB::commit();

                return response()->json([
                    'message' => 'Resource deleted due to multiple reports',
                    'deleted' => true,
                    'open_reports_count' => $openCount,
                    'threshold' => self::REPORT_DELETE_THRESHOLD,
                ], 200);
            }

            DB::commit();

            return response()->json([
                'message' => 'Report submitted',
                'deleted' => false,
                'open_reports_count' => $openCount,
                'threshold' => self::REPORT_DELETE_THRESHOLD,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit report'], 500);
        }
    }

    /**
     * Admin/Moderator: list resources with open reports.
     */
    public function index(Request $request)
    {
        $minReports = max(1, (int) $request->input('min_reports', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));

        $resources = Resource::query()
            ->with([
                'user',
                'course',
                'openReports' => function ($q) {
                    $q->with('reporter:id,username,first_name,last_name,avatar');
                },
            ])
            ->withCount([
                'reports as open_reports_count' => function ($q) {
                    $q->where('status', ResourceReport::STATUS_OPEN);
                },
            ])
            ->having('open_reports_count', '>=', $minReports)
            ->orderByDesc('open_reports_count')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($resources);
    }

    /**
     * Admin/Moderator: mark all open reports for a resource as reviewed.
     */
    public function markReviewed($resourceId)
    {
        $user = Auth::user();

        $resource = Resource::findOrFail($resourceId);

        $updated = ResourceReport::query()
            ->where('resource_id', $resource->id)
            ->open()
            ->update([
                'status' => ResourceReport::STATUS_REVIEWED,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

        return response()->json([
            'message' => 'Reports marked as reviewed',
            'count' => $updated,
        ], 200);
    }

    /**
     * Admin/Moderator: dismiss all open reports for a resource.
     */
    public function dismiss($resourceId)
    {
        $user = Auth::user();

        $resource = Resource::findOrFail($resourceId);

        $updated = ResourceReport::query()
            ->where('resource_id', $resource->id)
            ->open()
            ->update([
                'status' => ResourceReport::STATUS_DISMISSED,
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

        return response()->json([
            'message' => 'Reports dismissed',
            'count' => $updated,
        ], 200);
    }

    private function deleteResourceAndOrphanAttachments(Resource $resource): void
    {
        $resource->loadMissing('attachments');

        $attachments = $resource->attachments;

        $resource->attachments()->detach();
        $resource->delete();

        foreach ($attachments as $attachment) {
            if ($attachment->resources()->count() === 0) {
                if (Storage::disk('s3')->exists($attachment->file_path)) {
                    Storage::disk('s3')->delete($attachment->file_path);
                }
                $attachment->delete();
            }
        }
    }
}
