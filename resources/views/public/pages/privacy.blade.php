@extends('layouts.public')

@section('title', 'Privacy Policy')

@section('content')
<div class="prose mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8">
    <h1>Privacy Policy</h1>
    @if ($url)
        <p class="text-sm text-slate-500">Canonical URL: <a href="{{ $url }}">{{ $url }}</a></p>
    @endif
    <div class="whitespace-pre-line text-slate-700">{{ $content }}</div>
</div>
@endsection
