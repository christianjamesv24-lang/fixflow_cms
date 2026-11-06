@extends('layouts.main')

@section('content')
<section class="error-page">
    <div class="container error-inner">
        <p class="error-code">419</p>
        <h1>Session expired</h1>
        <p>Your form session expired for security reasons. Please go back, refresh the page, and submit again.</p>
        <div class="error-actions">
            <a class="btn btn-primary" href="javascript:history.back()">Go back</a>
            <a class="btn btn-ghost" href="<?= e(url('/')) ?>">Home</a>
        </div>
    </div>
</section>

@endsection
