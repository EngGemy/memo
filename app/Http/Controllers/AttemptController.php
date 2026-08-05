<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Progress;
use App\Models\Question;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Grading happens here and only here. The player never receives is_correct,
 * so a viewer reading the network tab learns nothing about the answer key.
 */
class AttemptController extends Controller
{
    public function store(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'answers'   => ['required','array','min:1'],
            'answers.*' => ['required','integer'],
        ]);

        $user = $request->user();

        $used = Attempt::where('user_id', $user->id)->where('video_id', $video->id)->count();

        if ($used >= $video->max_attempts) {
            return response()->json([
                'message'  => 'No attempts left. Rewatch the chapter to reset.',
                'rewatch'  => true,
            ], 429);
        }

        // Must have actually watched it - 90% of runtime, tracked server-side.
        $progress = Progress::firstOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            ['unlocked' => true]
        );

        if ($video->duration > 0 && $progress->furthest_second < $video->duration * 0.9) {
            return response()->json(['message' => 'Finish the chapter before answering.'], 422);
        }

        $questions = Question::with('options')->where('video_id', $video->id)->get();
        $correct   = 0;
        $wrongIds  = [];

        foreach ($questions as $q) {
            $chosen = $data['answers'][$q->id] ?? null;
            $isRight = $q->options->firstWhere('is_correct', true)?->id === $chosen;
            $isRight ? $correct++ : $wrongIds[] = $q->id;
        }

        $score  = $questions->count() ? (int) round($correct / $questions->count() * 100) : 0;
        $passed = $score >= $video->pass_mark;

        DB::transaction(function () use ($user, $video, $used, $score, $passed, $data, $progress) {
            Attempt::create([
                'user_id'    => $user->id,
                'video_id'   => $video->id,
                'attempt_no' => $used + 1,
                'score'      => $score,
                'passed'     => $passed,
                'answers'    => $data['answers'],
            ]);

            if ($passed) {
                $progress->update(['completed' => true, 'completed_at' => now()]);

                // Open the next chapter.
                if ($next = Video::where('chapter', $video->chapter + 1)->first()) {
                    Progress::updateOrCreate(
                        ['user_id' => $user->id, 'video_id' => $next->id],
                        ['unlocked' => true]
                    );
                }
            }
        });

        return response()->json([
            'score'            => $score,
            'passed'           => $passed,
            'pass_mark'        => $video->pass_mark,
            'attempts_left'    => max(0, $video->max_attempts - $used - 1),
            // Point them back at the video, don't hand them the answer.
            'review_timestamps'=> $questions->whereIn('id', $wrongIds)->pluck('ask_at','id'),
            'next_chapter'     => $passed ? $video->chapter + 1 : null,
        ]);
    }

    /** Heartbeat from the player - the only source of truth for watch time. */
    public function heartbeat(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'second' => ['required','integer','min:0','max:' . max(1, $video->duration)],
        ]);

        $p = Progress::firstOrCreate(
            ['user_id' => $request->user()->id, 'video_id' => $video->id],
            ['unlocked' => true]
        );

        // Only accept forward motion at a plausible rate - blocks seek-to-end cheating.
        $delta = $data['second'] - $p->furthest_second;

        if ($delta > 0 && $delta <= 35) {
            $p->increment('watched_seconds', $delta);
            $p->update(['furthest_second' => $data['second']]);
        }

        return response()->json([
            'furthest'  => $p->furthest_second,
            'eligible'  => $video->duration > 0 && $p->furthest_second >= $video->duration * 0.9,
        ]);
    }
}
