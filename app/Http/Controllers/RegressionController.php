<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\Prediction;
use App\Services\RegressionService;
use Illuminate\Http\Request;

class RegressionController extends Controller
{
    public function __construct(private RegressionService $service) {}

    public function compute(Request $req)
    {
        $req->validate(['dataset_id' => 'required|exists:datasets,id']);
        $dataset = Dataset::with('rows')->findOrFail($req->integer('dataset_id'));
        $result = $this->service->fit($dataset);

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Tidak bisa menghitung model.',
            ], 422);
        }

        return array_merge($result, [
            'dataset' => [
                'id'         => $dataset->id,
                'name'       => $dataset->name,
                'case_label' => $dataset->case_label,
                'x_label'    => $dataset->x_label,
                'x_unit'     => $dataset->x_unit,
                'y_label'    => $dataset->y_label,
                'y_unit'     => $dataset->y_unit,
            ],
        ]);
    }

    public function predict(Request $req)
    {
        $data = $req->validate([
            'dataset_id' => 'required|exists:datasets,id',
            'x'          => 'required|numeric',
        ]);
        $dataset = Dataset::with('rows')->findOrFail($data['dataset_id']);
        $fit = $this->service->fit($dataset);
        if (!($fit['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $fit['message']], 422);
        }
        $x = (float) $data['x'];
        $yHat = $this->service->predict($fit['intercept'], $fit['slope'], $x);

        $sign = $fit['slope'] >= 0 ? '+' : '−';
        $absB = round(abs($fit['slope']), 4);
        $sub = "Ŷ = " . round($fit['intercept'], 4) . " {$sign} {$absB} · {$x}\n"
             . "Ŷ = " . round($yHat, 4);

        $pred = Prediction::create([
            'dataset_id' => $dataset->id,
            'user_id'    => $req->user()->id,
            'x_input'    => $x,
            'y_predicted' => $yHat,
            'slope'      => $fit['slope'],
            'intercept'  => $fit['intercept'],
            'r_squared'  => $fit['r_squared'],
        ]);

        return [
            'ok'         => true,
            'id'         => $pred->id,
            'x'          => $x,
            'y_predicted' => round($yHat, 4),
            'slope'      => $fit['slope'],
            'intercept'  => $fit['intercept'],
            'r_squared'  => $fit['r_squared'],
            'r'          => $fit['r'],
            'equation'   => $fit['equation'],
            'substitution' => $sub,
            'dataset' => [
                'id' => $dataset->id, 'name' => $dataset->name,
                'x_label' => $dataset->x_label, 'x_unit' => $dataset->x_unit,
                'y_label' => $dataset->y_label, 'y_unit' => $dataset->y_unit,
            ],
        ];
    }

    public function history(Request $req)
    {
        $q = Prediction::with(['dataset:id,name,x_label,y_label', 'user:id,name'])->orderByDesc('id');
        if ($req->filled('dataset_id')) $q->where('dataset_id', $req->integer('dataset_id'));
        return $q->paginate(20);
    }
}
