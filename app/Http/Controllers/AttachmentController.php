<?php

namespace App\Http\Controllers;

use App\Models\AppointmentNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download($note_id)
    {
        $note = AppointmentNote::findOrFail($note_id);

        $path = $note->attachment_path;
        $name = $note->attachment_name;

        if (!$path || !Storage::exists($path)) {
            abort(404);
        }

        return Storage::download(
            $path,
            $name,
        );
    }
}
