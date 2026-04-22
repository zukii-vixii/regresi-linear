<?php

namespace App\Services;

use App\Models\Dataset;

class RegressionService
{
    /**
     * Hitung regresi linear sederhana Y = a + bX.
     *  b = (n·ΣXY − ΣX·ΣY) / (n·ΣX² − (ΣX)²)
     *  a = (ΣY − b·ΣX) / n
     *
     * Mengembalikan koefisien, kualitas model, tabel langkah perhitungan,
     * dan data scatter+trend untuk visualisasi grafik.
     */
    public function fit(Dataset $dataset): array
    {
        $rows = $dataset->rows()->orderBy('id')->get();
        $n = $rows->count();

        if ($n < 2) {
            return ['ok' => false, 'message' => 'Minimal butuh 2 baris data untuk regresi linear.'];
        }

        $x = $rows->pluck('x_value')->map(fn ($v) => (float) $v)->all();
        $y = $rows->pluck('y_value')->map(fn ($v) => (float) $v)->all();
        $labels = $rows->pluck('label')->all();
        $ids    = $rows->pluck('id')->all();

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0; $sumX2 = 0; $sumY2 = 0;
        $detail = []; // langkah per baris

        for ($i = 0; $i < $n; $i++) {
            $xi = $x[$i]; $yi = $y[$i];
            $xy = $xi * $yi;
            $x2 = $xi * $xi;
            $y2 = $yi * $yi;
            $sumXY += $xy;
            $sumX2 += $x2;
            $sumY2 += $y2;
            $detail[] = [
                'i'      => $i + 1,
                'label'  => $labels[$i] ?? null,
                'x'      => $xi,
                'y'      => $yi,
                'xy'     => round($xy, 6),
                'x2'     => round($x2, 6),
                'y2'     => round($y2, 6),
            ];
        }

        $meanX = $sumX / $n;
        $meanY = $sumY / $n;

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if (abs($denominator) < 1e-12) {
            return ['ok' => false, 'message' => 'Variasi nilai X = 0 (semua X identik). Tidak bisa membuat garis regresi.'];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Prediksi & residual
        $sse = 0; $sst = 0; $ssr = 0; $sumAbs = 0;
        $yHat = []; $residual = [];
        for ($i = 0; $i < $n; $i++) {
            $yh = $intercept + ($slope * $x[$i]);
            $yHat[$i] = round($yh, 6);
            $residual[$i] = round($y[$i] - $yh, 6);
            $sse += pow($y[$i] - $yh, 2);
            $ssr += pow($yh - $meanY, 2);
            $sst += pow($y[$i] - $meanY, 2);
            $sumAbs += abs($y[$i] - $yh);
        }

        $rSquared = $sst > 0 ? 1 - ($sse / $sst) : 0;
        $r       = sqrt(max(0, $rSquared)) * ($slope >= 0 ? 1 : -1); // korelasi pearson
        $mse     = $sse / $n;
        $rmse    = sqrt($mse);
        $mae     = $sumAbs / $n;

        // Garis trend untuk chart (50 titik dari minX..maxX)
        $minX = min($x); $maxX = max($x);
        $padX = ($maxX - $minX) * 0.05;
        $trend = [];
        $start = $minX - $padX; $end = $maxX + $padX;
        $steps = 50;
        for ($i = 0; $i <= $steps; $i++) {
            $xv = $start + ($end - $start) * ($i / $steps);
            $trend[] = ['x' => round($xv, 4), 'y' => round($intercept + $slope * $xv, 4)];
        }

        // Step-by-step pedagogis
        $sign = $slope >= 0 ? '+' : '−';
        $absB = round(abs($slope), 4);
        $steps_text = [
            [
                'title' => 'Langkah 1 — Susun tabel ΣX, ΣY, ΣXY, ΣX²',
                'body'  => "Untuk n = {$n} pasang data (X, Y) hitung jumlah X, Y, X·Y, dan X²:\n"
                          ."ΣX  = ".$this->fmt($sumX)."\n"
                          ."ΣY  = ".$this->fmt($sumY)."\n"
                          ."ΣXY = ".$this->fmt($sumXY)."\n"
                          ."ΣX² = ".$this->fmt($sumX2),
            ],
            [
                'title' => 'Langkah 2 — Hitung slope (b)',
                'body'  => "Rumus: b = (n·ΣXY − ΣX·ΣY) / (n·ΣX² − (ΣX)²)\n"
                          ."b = ({$n} × ".$this->fmt($sumXY)." − ".$this->fmt($sumX)." × ".$this->fmt($sumY).") / "
                          ."({$n} × ".$this->fmt($sumX2)." − (".$this->fmt($sumX).")²)\n"
                          ."b = ".$this->fmt(($n * $sumXY) - ($sumX * $sumY))." / ".$this->fmt($denominator)."\n"
                          ."b = ".round($slope, 6),
            ],
            [
                'title' => 'Langkah 3 — Hitung intercept (a)',
                'body'  => "Rumus: a = (ΣY − b·ΣX) / n\n"
                          ."a = (".$this->fmt($sumY)." − ".round($slope, 6)." × ".$this->fmt($sumX).") / {$n}\n"
                          ."a = ".$this->fmt($sumY - ($slope * $sumX))." / {$n}\n"
                          ."a = ".round($intercept, 6),
            ],
            [
                'title' => 'Langkah 4 — Persamaan regresi',
                'body'  => "Persamaan: Ŷ = a + b·X\n"
                          ."Ŷ = ".round($intercept, 4)." {$sign} {$absB}·X",
            ],
            [
                'title' => 'Langkah 5 — Evaluasi model (R²)',
                'body'  => "SSR = Σ(Ŷ − Ȳ)² = ".$this->fmt($ssr)."\n"
                          ."SSE = Σ(Y − Ŷ)² = ".$this->fmt($sse)."\n"
                          ."SST = SSR + SSE = ".$this->fmt($sst)."\n"
                          ."R²  = 1 − (SSE / SST) = ".round($rSquared, 6)."\n"
                          ."r   = ".round($r, 6)." (koefisien korelasi Pearson)",
            ],
        ];

        $equation = "Ŷ = " . round($intercept, 4) . " " . $sign . " " . $absB . " · X";

        return [
            'ok'        => true,
            'n'         => $n,
            'equation'  => $equation,
            'slope'     => round($slope, 6),
            'intercept' => round($intercept, 6),
            'r_squared' => round($rSquared, 6),
            'r'         => round($r, 6),
            'mean_x'    => round($meanX, 6),
            'mean_y'    => round($meanY, 6),
            'sum_x'     => round($sumX, 6),
            'sum_y'     => round($sumY, 6),
            'sum_xy'    => round($sumXY, 6),
            'sum_x2'    => round($sumX2, 6),
            'sum_y2'    => round($sumY2, 6),
            'sst'       => round($sst, 6),
            'ssr'       => round($ssr, 6),
            'sse'       => round($sse, 6),
            'mse'       => round($mse, 6),
            'rmse'      => round($rmse, 6),
            'mae'       => round($mae, 6),
            'detail'    => $detail,
            'y_hat'     => $yHat,
            'residual'  => $residual,
            'ids'       => $ids,
            'labels'    => $labels,
            'x'         => $x,
            'y'         => $y,
            'trend'     => $trend,
            'steps'     => $steps_text,
        ];
    }

    public function predict(float $intercept, float $slope, float $x): float
    {
        return $intercept + $slope * $x;
    }

    private function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
    }
}
