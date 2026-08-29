<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadExportController extends Controller
{
    public function __invoke(Request $request, Export $export): StreamedResponse
    {
        abort_unless(
            (int) $export->access_token_id === (int) $request->session()->get('access_token_id'),
            403,
        );

        abort_unless(
            $export->status->isDownloadable() && $export->disk && $export->path
                && Storage::disk($export->disk)->exists($export->path),
            404,
        );

        return Storage::disk($export->disk)->download(
            $export->path,
            $export->filename,
            ['Content-Type' => $export->format->mimeType()],
        );
    }
}
