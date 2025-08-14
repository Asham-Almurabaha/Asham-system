<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Schema;

class LanguageController extends Controller
{
    protected array $supported = ['ar', 'en'];

    public function switch(string $locale)
    {
        if (!in_array($locale, $this->supported, true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        if (auth()->check() && Schema::hasColumn('users', 'locale')) {
            auth()->user()->forceFill(['locale' => $locale])->save();
        }

        return back();
    }

    public function toggle()
    {
        $next = app()->getLocale() === 'ar' ? 'en' : 'ar';
        return $this->switch($next);
    }
}
