@extends('layouts.main')

@section('content')
<section class="error-page">
    <div class="container error-inner">
        <p class="error-code">500</p>
        <h1>Something went wrong</h1>
        <p>An unexpected error occurred. Please try again, or contact support if the problem continues.</p>
        <div class="error-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
        </div>
    </div>
</section>

@endsection
