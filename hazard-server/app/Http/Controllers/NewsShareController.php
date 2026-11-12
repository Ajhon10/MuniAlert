<?php

namespace App\Http\Controllers;

use App\Models\HazardReport;
use Illuminate\Http\Request;

class NewsShareController extends Controller
{
    public function show(Request $request, HazardReport $report)
    {
        abort_unless($report->isPublished(), 404);

        return response()->view('news.show', ['report' => $report->load(['category', 'department']), 'canonical' => $request->url()])->header('Cache-Control', 'no-store, private');
    }
}
