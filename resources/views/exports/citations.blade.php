<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11pt; color: #1a1a1a; line-height: 1.5; }
        h1 { font-size: 17pt; margin: 0 0 4px; }
        h2 { font-size: 12pt; margin: 22px 0 2px; }
        .reference { font-style: italic; color: #333; margin: 0 0 10px; font-size: 10pt; }
        .quote { margin: 0 0 6px; text-align: justify; }
        .note { font-style: italic; color: #555; margin: 0 0 12px; font-size: 10pt; }
        .meta { color: #777; font-size: 9pt; margin-bottom: 18px; }
    </style>
</head>
<body>
    <h1>{{ $heading }}</h1>
    <p class="meta">Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @foreach ($materials as $material)
        <h2>{{ $material->title }}</h2>
        <p class="reference">{{ $abnt->reference($material) }}</p>

        @foreach ($material->citations as $citation)
            <p class="quote">&ldquo;{{ trim($citation->quote_text) }}&rdquo; {{ $abnt->inText($material, $citation->location) }}</p>
            @if (filled($citation->personal_note))
                <p class="note">Nota: {{ trim($citation->personal_note) }}</p>
            @endif
        @endforeach
    @endforeach
</body>
</html>
