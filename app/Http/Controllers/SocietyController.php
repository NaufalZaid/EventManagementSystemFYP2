<?php

namespace App\Http\Controllers;

use App\Models\Society;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SocietyController extends Controller
{
    public function index()
    {
        $societies = Society::withCount(['organizers', 'events'])->orderBy('name')->get();

        return view('societies.index', compact('societies'));
    }

    public function create()
    {
        return view('societies.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        Society::create($validated);

        return redirect()->route('societies.index')->with(
            'success',
            'Society created successfully. You can now assign an organizer from User Management.'
        );
    }

    public function edit(Society $society)
    {
        return view('societies.edit', compact('society'));
    }

    public function update(Request $request, Society $society)
    {
        $validated = $this->validated($request, $society);

        $society->update($validated);

        return redirect()->route('societies.index')->with('success', 'Society updated successfully.');
    }

    public function destroy(Society $society)
    {
        if ($society->organizers()->exists() || $society->events()->exists()) {
            throw ValidationException::withMessages([
                'society' => 'Deactivate this society instead. Societies with organizers or events cannot be deleted.',
            ]);
        }

        $society->delete();

        return redirect()->route('societies.index')->with('success', 'Society deleted successfully.');
    }

    private function validated(Request $request, ?Society $society = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('societies')->ignore($society)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
