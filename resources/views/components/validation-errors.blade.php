@if ($errors->any())
    <div {{ $attributes }}>
        <x-ui.error :messages="$errors->all()" />
    </div>
@endif
