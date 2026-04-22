<?php

namespace App\Http\Controllers;

use App\Models\DataRow;
use App\Models\Dataset;
use Illuminate\Http\Request;

class DataRowController extends Controller
{
    public function index(Request $req)
    {
        $q = DataRow::query();
        if ($req->filled('dataset_id')) $q->where('dataset_id', $req->integer('dataset_id'));
        return $q->orderBy('id')->paginate(50);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'dataset_id' => 'required|exists:datasets,id',
            'label'      => 'nullable|string|max:160',
            'x_value'    => 'required|numeric',
            'y_value'    => 'required|numeric',
        ]);
        $row = DataRow::create($data);
        return response()->json($row, 201);
    }

    public function update(Request $req, DataRow $dataRow)
    {
        $data = $req->validate([
            'label'   => 'nullable|string|max:160',
            'x_value' => 'required|numeric',
            'y_value' => 'required|numeric',
        ]);
        $dataRow->update($data);
        return $dataRow;
    }

    public function destroy(DataRow $dataRow)
    {
        $dataRow->delete();
        return ['ok' => true];
    }

    public function bulkStore(Request $req)
    {
        $data = $req->validate([
            'dataset_id' => 'required|exists:datasets,id',
            'rows'       => 'required|array|min:1',
            'rows.*.label'   => 'nullable|string|max:160',
            'rows.*.x_value' => 'required|numeric',
            'rows.*.y_value' => 'required|numeric',
        ]);
        $now = now();
        $payload = array_map(fn ($r) => [
            'dataset_id' => $data['dataset_id'],
            'label'      => $r['label'] ?? null,
            'x_value'    => $r['x_value'],
            'y_value'    => $r['y_value'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $data['rows']);
        DataRow::insert($payload);
        return ['inserted' => count($payload)];
    }
}
