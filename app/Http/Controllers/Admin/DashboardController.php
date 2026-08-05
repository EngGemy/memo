<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeakReport;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * One request fills the whole dashboard.
 *
 * The old dashboard had these numbers typed into the HTML. Everything here
 * is a real query, so the page reflects the database and nothing else.
 */
class DashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $videos = Video::with(['expert', 'category'])->orderBy('position')->get();

        $viewsToday = DB::table('video_views')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $topVideos = DB::table('video_views')
            ->select('video_id', DB::raw('count(*) as hits'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('video_id')
            ->orderByDesc('hits')
            ->limit(5)
            ->get()
            ->map(function ($r) use ($videos) {
                $v = $videos->firstWhere('id', $r->video_id);

                return ['title' => $v ? $v->title : '—', 'hits' => (int) $r->hits];
            });

        return response()->json([
            'stats' => [
                'total' => $videos->count(),
                'public' => $videos->where('is_public', true)->count(),
                'processing' => $videos->whereIn('status', ['queued', 'transcoding'])->count(),
                'failed' => $videos->where('status', 'failed')->count(),
                'views_total' => (int) $videos->sum('views'),
                'views_today' => $viewsToday,
                'storage_gb' => round($videos->sum('size_bytes') / 1073741824, 2),
                'leaks_open' => LeakReport::where('status', 'open')->count(),
                'watermarked' => $videos->where('watermark_burned', true)->count(),
            ],
            'videos' => $videos->map->forAdmin()->values(),
            'top' => $topVideos,
            'leaks' => LeakReport::with('video:id,title')->latest()->limit(12)->get(),
            'activity' => DB::table('playback_events')
                ->select('type', 'ip', 'created_at', 'video_id')
                ->orderByDesc('created_at')->limit(15)->get(),
        ]);
    }
}
