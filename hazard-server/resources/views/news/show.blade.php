<!doctype html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->public_title }} · MuniAlert</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($report->public_summary, 200) }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="MuniAlert">
    <meta property="og:title" content="{{ $report->public_title }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($report->public_summary, 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ url('/news-icon.png') }}">
    <meta property="og:image:alt" content="MuniAlert community safety emblem">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $report->public_title }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($report->public_summary, 200) }}">
    <meta name="twitter:image" content="{{ url('/news-icon.png') }}">
    <link rel="stylesheet" href="{{ url('/news-share.css') }}">
</head>
<body>
    <main>
        <a class="brand" href="{{ url('/') }}">MuniAlert · Community news</a>
        <article>
            <p class="verified">✓ Fact-checked and accepted by the municipality</p>
            <h1>{{ $report->public_title }}</h1>
            <p class="meta">{{ $report->barangay }} · {{ $report->category->name }} · {{ $report->published_at->timezone('Asia/Manila')->format('j M Y, g:i A') }} PHT</p>
            <p class="summary">{{ $report->public_summary }}</p>
            <section class="incident-details" aria-label="Incident details">
                <h2>Incident details</h2>
                <p>Submitted by <strong>{{ $report->public_reporter_name ?: 'Community member (name withheld)' }}</strong></p>
                <dl>
                    <div><dt>Hazard</dt><dd>{{ $report->category->name }}</dd></div>
                    <div><dt>Area</dt><dd>{{ $report->barangay }}</dd></div>
                    <div><dt>Status</dt><dd>{{ ucfirst(str_replace('_', ' ', $report->status)) }}</dd></div>
                    <div><dt>Priority</dt><dd>{{ ucfirst($report->severity) }}</dd></div>
                    <div><dt>Incident time</dt><dd>{{ $report->public_occurred_at ? $report->public_occurred_at->timezone('Asia/Manila')->format('j M Y, g:i A').' PHT' : 'Not confirmed' }}</dd></div>
                    <div><dt>Reported</dt><dd>{{ $report->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }} PHT</dd></div>
                    <div><dt>Fact-checked</dt><dd>{{ $report->fact_checked_at->timezone('Asia/Manila')->format('j M Y, g:i A') }} PHT</dd></div>
                    <div><dt>Responsible office</dt><dd>{{ $report->department?->name ?: 'Municipal response team' }}</dd></div>
                    <div><dt>Reference</dt><dd>{{ $report->reference_number }}</dd></div>
                </dl>
            </section>
            <a class="button" href="{{ url('/?news='.$report->id) }}">Open this post in the newsfeed</a>
        </article>
        <p class="footnote">Submitter credit and incident details are reviewed for publication. Contact information, original evidence and exact locations remain private. For immediate danger, use the approved local emergency contacts.</p>
    </main>
</body>
</html>
