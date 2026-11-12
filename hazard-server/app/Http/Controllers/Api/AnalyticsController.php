<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IncidentStatistics;
use App\Services\StatisticsExport;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request, IncidentStatistics $statistics): array
    {
        abort_unless($request->user()->role === 'admin', 403);

        return $statistics->summarize($request);
    }

    public function export(Request $request, string $format, IncidentStatistics $statistics, StatisticsExport $exporter)
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(in_array($format, ['pdf', 'docx', 'xlsx'], true), 404);
        $data = $statistics->summarize($request);
        $bytes = $exporter->create($data, $format);
        $type = match ($format) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
        $filename = 'munialert-statistics-'.$data['group'].'-'.$data['from'].'-'.$data['to'].'.'.$format;

        return response($bytes, 200, ['Content-Type' => $type, 'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }
}
