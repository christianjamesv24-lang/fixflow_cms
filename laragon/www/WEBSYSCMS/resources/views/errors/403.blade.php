@extends('layouts.main')

@section('content')
<section class="error-page">
    <div class="container error-inner">
        <p class="error-code">403</p>
        <h1>Access denied</h1>
        <p>You do not have permission to view this page. If you believe this is a mistake, contact the administrator.</p>
        <div class="error-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
            <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Switch account</a>
        </div>
    </div>
</section>

@endsection
