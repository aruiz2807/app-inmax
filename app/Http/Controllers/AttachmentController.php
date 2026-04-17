<?php

namespace App\Http\Controllers;

use App\Models\AppointmentNote;
use App\Models\AppointmentService;
use App\Models\PolicyExternalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download($service_id)
    {
        $service = AppointmentService::findOrFail($service_id);

        $path = $service->attachment_path;
        $name = $service->attachment_name;

        if (!$path || !Storage::exists($path)) {
            abort(404);
        }

        return Storage::download(
            $path,
            $name,
        );
    }

    public function downloadExternalService($external_service_id)
    {
        $externalService = PolicyExternalService::findOrFail($external_service_id);

        $path = $externalService->attachment_path;
        $name = $externalService->attachment_name;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $path,
            $name,
        );
    }
}
