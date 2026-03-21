<?php

namespace App\Http\Controllers;

use App\Models\AppointmentNote;
use App\Models\AppointmentService;
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
}
