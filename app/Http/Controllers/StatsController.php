<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\DataRow;
use App\Models\Prediction;
use App\Models\User;

class StatsController extends Controller
{
    public function index()
    {
        return [
            'datasets'    => Dataset::count(),
            'data_rows'   => DataRow::count(),
            'predictions' => Prediction::count(),
            'users'       => User::count(),
            'recent_predictions' => Prediction::with(['dataset:id,name', 'user:id,name'])
                ->orderByDesc('id')->limit(8)->get(),
            'datasets_summary' => Dataset::withCount('rows')->orderByDesc('id')->limit(8)->get(),
        ];
    }
}
