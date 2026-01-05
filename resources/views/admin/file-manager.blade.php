@extends('layouts.admin')

@section('title', 'File Manager')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
@endpush

@section('content')
    <div class="container-fluid py-3">
        <h5 class="mb-3">Media Library</h5>

        <!-- File Manager App -->
        <div id="fm" style="height: 600px;"></div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
@endpush
