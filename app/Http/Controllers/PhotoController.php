<?php

namespace App\Http\Controllers;

use App\Services\PhotoGapService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function index(Request $request, PhotoGapService $gaps)
    {
        $q = trim((string) $request->query('q'));
        $filter = $request->query('filter', 'missing') === 'all' ? 'all' : 'missing';

        $all = $gaps->rows($q);
        $rows = $filter === 'missing' ? $all->filter(fn ($r) => $r['missing'] > 0)->values() : $all;

        $summary = [
            'products' => $all->count(),
            'products_missing' => $all->filter(fn ($r) => $r['missing'] > 0)->count(),
            'colors_missing' => $all->sum('missing'),
        ];

        return view('photos.index', compact('rows', 'q', 'filter', 'summary'));
    }
}