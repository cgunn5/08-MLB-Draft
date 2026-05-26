<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportApplicationBundleRequest;
use App\Support\ApplicationBundleExporter;
use App\Support\ApplicationBundleImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationBundleController extends Controller
{
    public function show(): View
    {
        return view('admin.application-bundle.show');
    }

    public function download(ApplicationBundleExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        try {
            $result = $exporter->export();
        } catch (\Throwable $e) {
            return back()->withErrors(['bundle' => $e->getMessage()]);
        }

        return response()->download($result['path'], basename($result['path']))->deleteFileAfterSend(true);
    }

    public function store(ImportApplicationBundleRequest $request, ApplicationBundleImporter $importer): RedirectResponse
    {
        $path = $request->file('bundle')->getRealPath();
        if ($path === false) {
            return back()->withErrors(['bundle' => 'Could not read the uploaded file.']);
        }

        try {
            $manifest = $importer->import($path);
        } catch (\Throwable $e) {
            return back()->withErrors(['bundle' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.application-bundle.show')
            ->with('status', 'bundle-imported')
            ->with('bundle_manifest', $manifest);
    }
}
