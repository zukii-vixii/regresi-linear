<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;

class DatasetController extends Controller
{
    public function index()
    {
        $datasets = Dataset::withCount('rows')
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get();
        return ['data' => $datasets];
    }

    public function show(Dataset $dataset)
    {
        $dataset->loadCount('rows');
        return $dataset;
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'        => 'required|string|max:160',
            'case_label'  => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'x_label'     => 'required|string|max:60',
            'x_unit'      => 'nullable|string|max:50',
            'y_label'     => 'required|string|max:60',
            'y_unit'      => 'nullable|string|max:50',
        ]);
        $data['user_id'] = $req->user()->id;
        $dataset = Dataset::create($data);
        return response()->json($dataset, 201);
    }

    public function update(Request $req, Dataset $dataset)
    {
        $data = $req->validate([
            'name'        => 'required|string|max:160',
            'case_label'  => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'x_label'     => 'required|string|max:60',
            'x_unit'      => 'nullable|string|max:50',
            'y_label'     => 'required|string|max:60',
            'y_unit'      => 'nullable|string|max:50',
        ]);
        $dataset->update($data);
        return $dataset->fresh();
    }

    public function destroy(Dataset $dataset)
    {
        $dataset->delete();
        return ['ok' => true];
    }
}
