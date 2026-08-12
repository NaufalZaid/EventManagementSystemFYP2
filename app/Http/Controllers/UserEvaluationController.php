<?php

namespace App\Http\Controllers;

use App\Models\UserEvaluation;
use Illuminate\Http\Request;

class UserEvaluationController extends Controller
{
    public function edit(Request $request)
    {
        $evaluation = $request->user()->evaluation;

        return view('evaluation.form', compact('evaluation'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ease_of_use' => ['required', 'integer', 'between:1,5'],
            'usefulness' => ['required', 'integer', 'between:1,5'],
            'scheduling_confidence' => ['required', 'integer', 'between:1,5'],
            'satisfaction' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:3000'],
            'consent' => ['accepted'],
        ]);
        $request->user()->evaluation()->updateOrCreate([], $validated + [
            'role' => $request->user()->role->value,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Thank you. Your evaluation response has been saved.');
    }

    public function results()
    {
        $evaluations = UserEvaluation::latest('submitted_at')->get();
        $averages = [
            'ease_of_use' => round((float) $evaluations->avg('ease_of_use'), 2),
            'usefulness' => round((float) $evaluations->avg('usefulness'), 2),
            'scheduling_confidence' => round((float) $evaluations->avg('scheduling_confidence'), 2),
            'satisfaction' => round((float) $evaluations->avg('satisfaction'), 2),
        ];
        $byRole = $evaluations->groupBy('role')->map(fn ($items) => [
            'count' => $items->count(),
            'average' => round((float) $items->avg(fn ($item) => ($item->ease_of_use + $item->usefulness + $item->scheduling_confidence + $item->satisfaction) / 4), 2),
        ]);

        return view('evaluation.results', compact('evaluations', 'averages', 'byRole'));
    }
}
