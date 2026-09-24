<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstituteSearchController
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $institutions = Institution::query()
            ->where('status', 'active')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($search) use ($q): void {
                    $search
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('official_name', 'like', "%{$q}%")
                        ->orWhere('short_name', 'like', "%{$q}%")
                        ->orWhereHas('names', function ($names) use ($q): void {
                            $names->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->withCount(['campuses', 'programs'])
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('institutes.search', [
            'institutions' => $institutions,
            'query' => $q,
        ]);
    }
}
