@php
    $previewUrl = route('whatsapp.attachments.preview', $attachment);
    $downloadUrl = route('whatsapp.attachments.download', $attachment);
    $surfaceClasses = $isOutbound
        ? 'border-white/15 bg-white/10'
        : 'border-slate-200 bg-slate-50';
    $mutedTextClasses = $isOutbound ? 'text-teal-100' : 'text-slate-500';
@endphp

<div class="pt-3">
    @if ($attachment->isDownloaded())
        @if ($attachment->isImage())
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-2xl border {{ $surfaceClasses }}">
                <img src="{{ $previewUrl }}" alt="{{ $attachment->file_name ?: 'Imagen de WhatsApp' }}" class="max-h-[28rem] w-full object-contain">
            </a>
        @elseif ($attachment->isVideo())
            <div class="overflow-hidden rounded-2xl border {{ $surfaceClasses }}">
                <video controls preload="metadata" class="max-h-[28rem] w-full bg-black">
                    <source src="{{ $previewUrl }}" type="{{ $attachment->mime_type ?: 'video/mp4' }}">
                </video>
            </div>
        @elseif ($attachment->isAudio())
            <div class="rounded-2xl border {{ $surfaceClasses }} p-3">
                <audio controls preload="metadata" class="w-full">
                    <source src="{{ $previewUrl }}" type="{{ $attachment->mime_type ?: 'audio/mpeg' }}">
                </audio>
            </div>
        @elseif ($attachment->isDocument() && $attachment->isPdf())
            <div class="overflow-hidden rounded-2xl border {{ $surfaceClasses }}">
                <iframe src="{{ $previewUrl }}" title="{{ $attachment->file_name ?: 'Documento PDF' }}" class="h-[28rem] w-full bg-white"></iframe>
            </div>
        @elseif ($attachment->isDocument())
            <div class="rounded-2xl border {{ $surfaceClasses }} p-4">
                <p class="text-sm font-semibold {{ $isOutbound ? 'text-white' : 'text-slate-900' }}">
                    {{ $attachment->file_name ?: 'Documento adjunto' }}
                </p>
                <p class="mt-1 text-xs {{ $mutedTextClasses }}">
                    Vista previa externa. Abre o descarga el archivo.
                </p>
            </div>
        @endif
    @else
        <div class="rounded-2xl border {{ $surfaceClasses }} p-4">
            <p class="text-sm font-semibold {{ $isOutbound ? 'text-white' : 'text-slate-900' }}">
                {{ $attachment->file_name ?: 'Adjunto '.$attachment->type }}
            </p>
            <p class="mt-1 text-xs {{ $mutedTextClasses }}">
                @if ($attachment->download_status === \App\Models\WhatsAppMessageAttachment::STATUS_FAILED)
                    No se pudo descargar el archivo: {{ $attachment->error_message ?: 'Error desconocido.' }}
                @elseif ($attachment->download_status === \App\Models\WhatsAppMessageAttachment::STATUS_DOWNLOADING)
                    Descargando archivo desde Meta...
                @else
                    Archivo pendiente de descarga desde Meta.
                @endif
            </p>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="rounded-full {{ $isOutbound ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-medium">
            {{ strtoupper($attachment->type) }}
        </span>

        <span class="rounded-full {{ $isOutbound ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-medium">
            {{ strtoupper($attachment->download_status) }}
        </span>

        @if ($attachment->isDownloaded())
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center rounded-full {{ $isOutbound ? 'bg-white/15 text-white hover:bg-white/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} px-3 py-1 text-[11px] font-medium transition">
                Abrir
            </a>

            <a href="{{ $downloadUrl }}"
                class="inline-flex items-center rounded-full {{ $isOutbound ? 'bg-white/15 text-white hover:bg-white/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} px-3 py-1 text-[11px] font-medium transition">
                Descargar
            </a>
        @endif
    </div>
</div>
