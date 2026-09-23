<?php
namespace App\Http\Controllers;
use App\Models\Institution;
use Illuminate\View\View;
class InstitutionController
{
    public function index(): View
    {
        return view('institutes.index', [
            'institutions' => Institution::query()->where('status','active')->orderBy('name')->paginate(24),
        ]);
    }
    public function show(Institution $institution): View
    {
        return view('institutes.show', compact('institution'));
    }
}
