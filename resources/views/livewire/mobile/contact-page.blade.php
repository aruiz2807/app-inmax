<div class="min-h-screen bg-gradient-to-b from-neutral-50 to-neutral-100 dark:from-neutral-900 dark:to-neutral-800">

    <!-- Back button and title -->
    <div class="px-4 pt-4 pb-2">
        <button 
            @click="window.history.back()" 
            class="inline-flex items-center gap-2 text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-white transition-colors"
        >
            <x-ui.icon name="arrow-left" class="w-5 h-5" />
            <span class="text-sm font-medium">Volver</span>
        </button>
    </div>

    <!-- Main content -->
    <div class="px-4 py-6 max-w-md mx-auto">
        <!-- Profile section -->
        <div class="text-center mb-8">
            <img src="/img/logo.png" alt="INMAX Logo" class="w-20 h-20 rounded-full shadow-lg mx-auto mb-4 object-cover">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white mb-2">INMAX - SURE</h1>
            <p class="text-neutral-600 dark:text-neutral-400 text-base">Comunicate con nosotros por alguno de los siguientes medios</p>
        </div>

        <!-- Social Icons Section -->
        <x-ui.social-icons :links="$socialLinks" />

        <!-- Contact items -->
        <div class="space-y-3">
            @forelse ($socialLinks as $link)
                <x-ui.contact-item
                    title="{{ $link->description }}"
                    value="{{ $link->value }}"
                    type="{{ strtolower($link->key) }}"
                />
            @empty
                <x-ui.contact-item
                    title="Llamanos"
                    value="3300000000"
                    type="phone"
                />
                <x-ui.contact-item
                    title="Envianos un email"
                    value="contacto@inmax-sure.com"
                    type="email"
                />
                <x-ui.contact-item
                    title="Chatea con nosotros"
                    value="3300000000"
                    type="whatsapp"
                />
            @endforelse
        </div>

        <!-- Footer info -->
        <div class="mt-10 pt-6 border-t border-neutral-200 dark:border-neutral-700 text-center">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                Estaremos encantados de ayudarte
            </p>
        </div>
    </div>
</div>
