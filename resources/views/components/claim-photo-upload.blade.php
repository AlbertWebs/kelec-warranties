@props([
    'id' => 'photos',
])

@php
    $maxPhotos = \App\Services\ClaimPhotoService::MAX_PHOTOS;
    $maxMb = (int) (\App\Services\ClaimPhotoService::MAX_KILOBYTES / 1024);
@endphp

<div
    x-data="{
        previews: [],
        error: '',
        maxFiles: {{ $maxPhotos }},
        maxBytes: {{ \App\Services\ClaimPhotoService::MAX_KILOBYTES * 1024 }},
        onChange(event) {
            this.error = '';
            const input = event.target;
            const files = Array.from(input.files || []);

            if (files.length > this.maxFiles) {
                this.error = 'You can upload up to ' + this.maxFiles + ' photos.';
                input.value = '';
                this.previews = [];
                return;
            }

            const oversized = files.find((file) => file.size > this.maxBytes);
            if (oversized) {
                this.error = 'Each photo must be {{ $maxMb }}MB or smaller.';
                input.value = '';
                this.previews = [];
                return;
            }

            this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
            this.previews = files.map((file) => ({
                name: file.name,
                url: URL.createObjectURL(file),
            }));
        }
    }"
>
    <label for="{{ $id }}" class="block text-sm font-medium text-brand-ink">Photos of the issue (optional)</label>
    <p class="mt-0.5 text-xs text-gray-500">JPG, PNG or WebP. Up to {{ $maxPhotos }} photos, {{ $maxMb }}MB each. Clear photos of the fault help us assess the claim faster.</p>
    <input
        id="{{ $id }}"
        name="photos[]"
        type="file"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        multiple
        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-soft file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand hover:file:bg-brand/10"
        @change="onChange($event)"
    >
    <p x-show="error" x-cloak x-text="error" class="mt-1 text-sm text-red-600"></p>
    @error('photos')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @foreach ($errors->get('photos.*') as $messages)
        @foreach ($messages as $message)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @endforeach
    @endforeach
    <ul x-show="previews.length" x-cloak class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
        <template x-for="preview in previews" :key="preview.url">
            <li class="overflow-hidden rounded-lg border border-gray-200 bg-slate-50">
                <img :src="preview.url" :alt="preview.name" class="h-24 w-full object-cover">
                <p class="truncate px-1.5 py-1 text-[11px] text-slate-500" x-text="preview.name"></p>
            </li>
        </template>
    </ul>
</div>
