@props([
    'photos',
    'routeName',
    'claim',
    'size' => 'md',
    'showHeading' => true,
])

@if ($photos->isNotEmpty())
    @php
        $items = $photos->values()->map(fn ($photo) => [
            'src' => route($routeName, [$claim, $photo]),
            'name' => $photo->original_name,
        ])->all();
        $thumbHeight = $size === 'lg' ? 'h-44 sm:h-52' : 'h-32';
    @endphp

    <div
        {{ $attributes->class('claim-photo-gallery') }}
        x-data="{
            photos: {{ \Illuminate\Support\Js::from($items) }},
            open: false,
            index: 0,
            scale: 1,
            x: 0,
            y: 0,
            dragging: false,
            startX: 0,
            startY: 0,
            originX: 0,
            originY: 0,
            minScale: 1,
            maxScale: 5,
            get current() {
                return this.photos[this.index] || this.photos[0];
            },
            get imageStyle() {
                return {
                    transform: 'translate(' + this.x + 'px, ' + this.y + 'px) scale(' + this.scale + ')',
                };
            },
            show(i) {
                this.index = i;
                this.resetZoom();
                this.open = true;
            },
            close() {
                this.open = false;
                this.resetZoom();
            },
            resetZoom() {
                this.scale = 1;
                this.x = 0;
                this.y = 0;
                this.dragging = false;
            },
            zoomIn() {
                this.scale = Math.min(this.maxScale, Math.round((this.scale + 0.5) * 10) / 10);
            },
            zoomOut() {
                this.scale = Math.max(this.minScale, Math.round((this.scale - 0.5) * 10) / 10);
                if (this.scale === 1) {
                    this.x = 0;
                    this.y = 0;
                }
            },
            toggleZoom() {
                if (this.scale > 1) {
                    this.resetZoom();
                    return;
                }
                this.scale = 2.5;
            },
            onWheel(event) {
                event.preventDefault();
                if (event.deltaY < 0) {
                    this.zoomIn();
                } else {
                    this.zoomOut();
                }
            },
            prev() {
                this.index = (this.index - 1 + this.photos.length) % this.photos.length;
                this.resetZoom();
            },
            next() {
                this.index = (this.index + 1) % this.photos.length;
                this.resetZoom();
            },
            onPointerDown(event) {
                if (this.scale <= 1 || event.button === 2) {
                    return;
                }
                this.dragging = true;
                this.startX = event.clientX;
                this.startY = event.clientY;
                this.originX = this.x;
                this.originY = this.y;
                event.target.setPointerCapture?.(event.pointerId);
            },
            onPointerMove(event) {
                if (! this.dragging) {
                    return;
                }
                this.x = this.originX + (event.clientX - this.startX);
                this.y = this.originY + (event.clientY - this.startY);
            },
            onPointerUp() {
                this.dragging = false;
            },
            handleKey(event) {
                if (! this.open) {
                    return;
                }
                if (event.key === 'Escape') {
                    this.close();
                }
                if (event.key === 'ArrowLeft' && this.photos.length > 1) {
                    this.prev();
                }
                if (event.key === 'ArrowRight' && this.photos.length > 1) {
                    this.next();
                }
                if (event.key === '=' || event.key === '+') {
                    event.preventDefault();
                    this.zoomIn();
                }
                if (event.key === '-' || event.key === '_') {
                    event.preventDefault();
                    this.zoomOut();
                }
                if (event.key === '0') {
                    this.resetZoom();
                }
            },
        }"
        x-on:keydown.window="handleKey"
    >
        @if ($showHeading)
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Photos ({{ $photos->count() }})</p>
            <p class="mt-1 text-xs text-slate-500">Click a photo to view it full size, then zoom in to inspect details.</p>
        @endif
        <ul class="{{ $showHeading ? 'mt-3' : '' }} grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($photos as $index => $photo)
                <li>
                    <button
                        type="button"
                        class="claim-photo-thumb group w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-left transition hover:border-brand/40 hover:shadow-sm"
                        x-on:click="show({{ $index }})"
                    >
                        <span class="relative block {{ $thumbHeight }}">
                            <img
                                src="{{ route($routeName, [$claim, $photo]) }}"
                                alt="{{ $photo->original_name }}"
                                class="h-full w-full object-cover"
                            >
                            <span class="claim-photo-thumb-hint">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14zM8 11h6M11 8v6" />
                                </svg>
                                Click to zoom
                            </span>
                        </span>
                        <span class="block truncate px-2 py-1.5 text-xs text-slate-500 group-hover:text-brand">{{ $photo->original_name }}</span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div
            x-show="open"
            x-cloak
            class="claim-photo-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="Claim photo viewer"
            x-on:click.self="close()"
        >
            <div class="claim-photo-lightbox-toolbar">
                <p class="min-w-0 truncate text-sm font-medium text-white" x-text="current?.name"></p>
                <div class="flex items-center gap-2">
                    <span class="hidden text-xs text-white/70 sm:inline" x-text="Math.round(scale * 100) + '%'"></span>
                    <button type="button" class="claim-photo-lightbox-btn" x-on:click="zoomOut()" title="Zoom out" aria-label="Zoom out">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                    </button>
                    <button type="button" class="claim-photo-lightbox-btn" x-on:click="zoomIn()" title="Zoom in" aria-label="Zoom in">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </button>
                    <button type="button" class="claim-photo-lightbox-btn" x-on:click="resetZoom()" title="Reset zoom" aria-label="Reset zoom">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M20 9A8 8 0 006.3 6.3M4 15a8 8 0 0013.7 2.7" /></svg>
                    </button>
                    <a :href="current?.src" target="_blank" rel="noopener" class="claim-photo-lightbox-btn" title="Open original" aria-label="Open original">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                    <button type="button" class="claim-photo-lightbox-btn" x-on:click="close()" title="Close" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div
                class="claim-photo-lightbox-stage"
                :class="{ 'is-zoomed': scale > 1, 'is-dragging': dragging }"
                x-on:wheel.prevent="onWheel($event)"
            >
                <template x-if="photos.length > 1">
                    <button type="button" class="claim-photo-lightbox-nav claim-photo-lightbox-nav-prev" x-on:click.stop="prev()" aria-label="Previous photo">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                </template>
                <img
                    :src="current?.src"
                    :alt="current?.name"
                    class="claim-photo-lightbox-image"
                    :style="imageStyle"
                    draggable="false"
                    x-on:pointerdown="onPointerDown($event)"
                    x-on:pointermove="onPointerMove($event)"
                    x-on:pointerup="onPointerUp()"
                    x-on:pointerleave="onPointerUp()"
                    x-on:dblclick.prevent="toggleZoom()"
                >
                <template x-if="photos.length > 1">
                    <button type="button" class="claim-photo-lightbox-nav claim-photo-lightbox-nav-next" x-on:click.stop="next()" aria-label="Next photo">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </template>
            </div>

            <p class="claim-photo-lightbox-hint">
                Scroll or use +/− to zoom · drag to pan · Esc to close
                <span x-show="photos.length > 1" x-text="' · ' + (index + 1) + ' of ' + photos.length"></span>
            </p>
        </div>
    </div>
@endif
