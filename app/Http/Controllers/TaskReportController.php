<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReport\TaskReportIndexRequest;
use App\Services\TaskReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskReportController extends Controller
{
    /**
     * Display task report with filters
     */
    public function index(TaskReportIndexRequest $request, TaskReportService $service): Response
    {
        $filters = $request->all();

        $perPage = (int) $request->get('per_page', 25);

        $response = $service->getIndexData($filters, $perPage);

        return Inertia::render('favorites/task-report/Index', $response);
    }

    /**
     * Export task report as CSV
     */
    public function export(Request $request, TaskReportService $service): StreamedResponse
    {
        $filters = $request->only([
            'names',
            'statuses',
            'priorities',
            'types',
            'start_date_from',
            'start_date_to',
            'due_date_from',
            'due_date_to',
            'search',
        ]);

        $callback = $service->generateExportCallback($filters);
        $filename = 'task-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
