<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::latest('id')->first();
        return view('settings.index', compact('setting'));
    }

    public function create()
{
    if (Setting::count() > 0) {
        return redirect()->route('settings.index')
            ->with('success', 'هناك إعداد محفوظ بالفعل.');
    }
    return view('settings.create');
}

    public function store(Request $request)
    {
        $rules = [
            'name'    => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            // ⚠️ بدون قاعدة "image" حتى يقبل ico
            'favicon' => 'nullable|mimes:ico,png,jpg,jpeg,gif,webp,svg'
                       .'|mimetypes:image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/gif,image/webp,image/svg+xml'
                       .'|max:1024',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $setting = new Setting();
        $setting->name = $request->name;
        $setting->name_ar = $request->name_ar;

        if ($request->hasFile('logo')) {
            $setting->logo = $request->file('logo')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            $setting->favicon = $request->file('favicon')->store('settings', 'public');
        }

        $setting->save();

        return redirect()->route('settings.show', $setting->id)
            ->with('success', 'تم إنشاء الإعداد بنجاح');
    }

    public function show($id)
    {
        // استخدم find بدل findOrFail للسماح بحالة عدم وجود السجل وعرض رسالة داخل الـ Blade
        $setting = Setting::findOrFail($id);
        return view('settings.show', compact('setting'));
    }

    public function edit($id)
    {
        $setting = Setting::findOrFail($id);
        return view('settings.edit', compact('setting'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name'    => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            // ⚠️ بدون "image"
            'favicon' => 'nullable|mimes:ico,png,jpg,jpeg,gif,webp,svg'
                       .'|mimetypes:image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/gif,image/webp,image/svg+xml'
                       .'|max:1024',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $setting = Setting::findOrFail($id);
        $setting->name = $request->name;
        $setting->name_ar = $request->name_ar;

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $setting->logo = $request->file('logo')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $setting->favicon = $request->file('favicon')->store('settings', 'public');
        }

        $setting->save();

        return redirect()->route('settings.show', $setting->id)
            ->with('success', 'تم تحديث الإعداد بنجاح');
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);

        if ($setting->logo) {
            Storage::disk('public')->delete($setting->logo);
        }
        if ($setting->favicon) {
            Storage::disk('public')->delete($setting->favicon);
        }

        $setting->delete();

        return redirect()->route('settings.index')->with('success', 'تم حذف الإعداد بنجاح');
    }
}
