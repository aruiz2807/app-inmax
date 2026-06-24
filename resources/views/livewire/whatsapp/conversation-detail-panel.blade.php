<div class="flex h-full min-h-0 w-full flex-col overflow-hidden">
    @php
        $contact = $selectedConversation->contact;
    @endphp

    <div class="border-b border-slate-200 bg-white px-5 py-5">
        <div class="flex flex-col gap-4 2xl:flex-row 2xl:items-start 2xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.heading level="h3" size="sm">
                        {{ $contact->user?->name ?? $contact->name ?? 'Sin nombre' }}
                    </x-ui.heading>

                    <x-ui.badge :color="$selectedConversation->status === 'archived' ? 'rose' : 'emerald'" size="sm" pill>
                        {{ $selectedConversation->status === 'archived' ? 'Archivado' : 'Abierto' }}
                    </x-ui.badge>

                    @if (! $contact->user)
                        <x-ui.badge color="amber" variant="outline" size="sm" pill>
                            Prospecto
                        </x-ui.badge>
                    @endif
                </div>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $contact->phone ?? $contact->normalized_phone }}
                </p>

                @if ($contact->user)
                    <p class="mt-1 text-xs text-slate-400">
                        Vinculado al usuario #{{ $contact->user->id }} ({{ $contact->user->profile }})
                    </p>
                @else
                    <p class="mt-1 text-xs text-slate-400">
                        Prospecto sin usuario vinculado.
                    </p>
                @endif

            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <x-ui.button
                        type="button"
                        icon="check-circle"
                        variant="outline"
                        color="teal"
                        wire:click="markSelectedConversationAsRead"
                    >
                        Marcar leído
                    </x-ui.button>
                </div>

                @if ($selectedConversation->status === 'archived')
                    <div>
                        <x-ui.button
                            type="button"
                            icon="arrow-path"
                            variant="outline"
                            color="blue"
                            wire:click="reopenSelectedConversation"
                        >
                            Reabrir
                        </x-ui.button>
                    </div>
                @else
                    <div>
                        <x-ui.button
                            type="button"
                            icon="archive-box"
                            variant="outline"
                            color="rose"
                            wire:click="archiveSelectedConversation"
                        >
                            Archivar
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <span class="font-medium text-slate-700">Último movimiento</span>
                <span>{{ $selectedConversation->last_message_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</span>

                @if ($selectedConversation->archived_at)
                    <span class="hidden h-1 w-1 rounded-full bg-slate-300 lg:block"></span>
                    <span>Archivado el {{ $selectedConversation->archived_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div
        wire:key="conversation-scroll-{{ $selectedConversation->id }}-{{ $selectedMessages->last()?->id ?? 'empty' }}"
        x-data="{ scrollToBottom() { const el = this.$refs.messageScroller; if (el) { el.scrollTop = el.scrollHeight; } } }"
        x-init="$nextTick(() => scrollToBottom())"
        x-ref="messageScroller"
        class="min-h-0 flex-1 overflow-y-auto bg-slate-50/80 px-4 py-5"
    >
        <div class="mx-auto max-w-4xl space-y-3">
            @forelse ($selectedMessages as $message)
                @php
                    $isOutbound = $message->direction === \App\Models\WhatsAppMessage::DIRECTION_OUTBOUND;
                    $alignment = $isOutbound ? 'justify-end' : 'justify-start';
                    $bubbleClasses = $isOutbound
                        ? 'bg-teal-600 text-white border-teal-500'
                        : 'bg-white text-slate-900 border-slate-200';
                    $attachment = $message->primaryAttachment;
                @endphp

                <div class="flex {{ $alignment }}" wire:key="conversation-message-{{ $message->id }}">
                    <div class="{{ $bubbleClasses }} max-w-3xl rounded-3xl border px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-[11px] font-semibold uppercase tracking-wide {{ $isOutbound ? 'text-teal-100' : 'text-slate-500' }}">
                                {{ $isOutbound ? 'Enviado' : 'Recibido' }}
                            </span>

                            <span class="text-[11px] {{ $isOutbound ? 'text-teal-100' : 'text-slate-400' }}">
                                {{ ($message->received_at ?? $message->created_at)?->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        @if ($attachment)
                            @include('livewire.whatsapp.partials.message-attachment', [
                                'attachment' => $attachment,
                                'isOutbound' => $isOutbound,
                            ])
                        @endif

                        @if ($message->body_text)
                            <div class="pt-2 text-sm leading-6">
                                {{ $message->body_text }}
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 pt-3">
                            <span class="rounded-full {{ $isOutbound ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-medium">
                                {{ strtoupper($message->status) }}
                            </span>

                            @if ($message->template_name)
                                <span class="rounded-full {{ $isOutbound ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-medium">
                                    {{ $message->template_name }}
                                </span>
                            @endif

                            @if ($message->meta_pricing_category)
                                <span class="rounded-full {{ $isOutbound ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-medium">
                                    {{ $message->meta_pricing_category }}
                                </span>
                            @endif

                            @if ($message->meta_message_id)
                                <span class="text-[11px] {{ $isOutbound ? 'text-teal-100' : 'text-slate-400' }}">
                                    ID: {{ $message->meta_message_id }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex min-h-[16rem] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/80 p-6 text-center text-sm text-slate-500">
                    Esta conversación aún no tiene mensajes persistidos.
                </div>
            @endforelse
        </div>
    </div>

    <form wire:submit="sendReply" class="border-t border-slate-200 bg-white px-5 py-4">
        <div class="grid gap-3">
            <div>
                <x-ui.label>{{ $replyAttachment ? 'Caption / mensaje del archivo' : 'Responder por WhatsApp' }}</x-ui.label>
                <textarea
                    wire:model.live="replyMessage"
                    rows="4"
                    placeholder="{{ $replyAttachment ? 'Escribe un caption opcional para imagen, video o documento...' : 'Escribe aquí la respuesta para el cliente...' }}"
                    class="w-full rounded-box border border-black/10 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-50 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                ></textarea>
                <x-ui.error name="replyMessage" />
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <x-ui.label>Adjuntar archivo</x-ui.label>
                        <input
                            type="file"
                            wire:model="replyAttachment"
                            accept="image/*,audio/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <x-ui.error name="replyAttachment" />
                        <p class="mt-2 text-xs text-slate-500">
                            Soporta imagen, audio, video y documentos. Si adjuntas archivo, el texto superior se usa como caption cuando Meta lo permite.
                        </p>
                    </div>

                    @if ($replyAttachment)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                            <p class="font-medium text-slate-900">{{ $replyAttachment->getClientOriginalName() }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ number_format(($replyAttachment->getSize() ?? 0) / 1024, 1) }} KB
                            </p>
                        </div>
                    @endif
                </div>

                <div wire:loading wire:target="replyAttachment" class="mt-3 text-xs text-teal-600">
                    Cargando archivo...
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <p class="text-xs text-slate-500">
                    {{ $replyAttachment ? 'Audio no usa caption en WhatsApp. Documentos PDF, imagen y video quedaran disponibles tambien para descarga en la consola.' : 'Meta solo permite texto libre dentro de la ventana activa de conversación.' }}
                </p>

                <x-ui.button
                    type="submit"
                    icon="paper-airplane"
                    variant="primary"
                    color="teal"
                >
                    {{ $replyAttachment ? 'Enviar archivo' : 'Enviar mensaje' }}
                </x-ui.button>
            </div>
        </div>
    </form>
</div>
