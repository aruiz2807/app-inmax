<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-neutral-50 dark:bg-neutral-950">
    <div>
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-5 bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-lg shadow-sm">
        {{ $slot }}
    </div>
</div>
