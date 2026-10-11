<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StatusController extends Controller
{
    public function index()
    {
        $statuses = Status::orderBy('order')->paginate(request('per_page', 15));

        return view('admin.settings.statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
        ]);

        $maxOrder = Status::max('order') ?? 0;

        Status::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->back()->with('success', 'Status created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string',
            'order' => 'numeric',
        ]);

        $status = Status::findOrFail($id);
        $status->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'order' => $request->order ?? $status->order,
        ]);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        $status = Status::findOrFail($id);

        if ($status->tasks()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete status with associated tasks.');
        }

        if ($status->is_default) {
            return redirect()->back()->with('error', 'Cannot delete default status.');
        }

        $status->delete();

        return redirect()->back()->with('success', 'Status deleted successfully.');
    }
}
