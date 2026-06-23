<?php

namespace App\Http\Controllers;

use App\Facades\Settings;
use App\Services\CvService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CvController extends Controller
{
    public function __construct(public CvService $cvService) {}

    public function show(): Response
    {
        if (! $this->cvService->exists()) {
            abort(404, 'CV has not been generated yet.');
        }

        $filename = str(Settings::getName())->slug()->append('_cv.pdf')->value();
        $contents = Storage::disk(CvService::DISK)->get(CvService::PATH);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}