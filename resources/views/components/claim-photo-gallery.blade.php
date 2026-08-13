@props([
    'photos',
    'routeName',
    'claim',
])

@if ($photos->isNotEmpty())
    <div {{ $attributes }}>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Photos ({{ $photos->count() }})</p>
        <ul class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($photos as $photo)
                <li>
                    <a href="{{ route($routeName, [$claim, $photo]) }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition hover:border-brand/40">
                        <img
                            src="{{ route($routeName, [$claim, $photo]) }}"
                            alt="{{ $photo->original_name }}"
                            class="h-32 w-full object-cover"
                        >
                        <span class="block truncate px-2 py-1.5 text-xs text-slate-500 group-hover:text-brand">{{ $photo->original_name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
