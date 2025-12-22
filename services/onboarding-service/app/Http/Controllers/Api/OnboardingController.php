<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingChecklist;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index($candidateId)
    {
        $tasks = OnboardingChecklist::where('candidate_id', $candidateId)
                                    ->orderBy('order')
                                    ->get();

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $task = OnboardingChecklist::create($request->all());
        return response()->json($task, 201);
    }

    public function update(Request $request, $id)
    {
        $task = OnboardingChecklist::findOrFail($id);
        $task->update($request->all());

        return response()->json($task);
    }

    public function markComplete($id)
    {
        $task = OnboardingChecklist::findOrFail($id);
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json($task);
    }

    public function progress($candidateId)
    {
        $total = OnboardingChecklist::where('candidate_id', $candidateId)->count();
        $completed = OnboardingChecklist::where('candidate_id', $candidateId)
                                        ->where('status', 'completed')
                                        ->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return response()->json([
            'total' => $total,
            'completed' => $completed,
            'pending' => $total - $completed,
            'percentage' => $percentage
        ]);
    }
}
