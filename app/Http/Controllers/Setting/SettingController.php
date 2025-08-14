<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    // عرض جميع الإعدادات
    public function index()
    {
        $settings = Setting::all();
        return view('settings.index', compact('settings'));
    }

    // عرض صفحة إنشاء إعداد جديد
    public function create()
    {
        return view('settings.create');
    }

    // حفظ إعداد جديد
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,ico|max:1024',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $setting = new Setting();
        $setting->name = $request->name;
        $setting->name_ar = $request->name_ar;

        // رفع صورة اللوغو
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('settings', 'public');
            $setting->logo = $logoPath;
        }

        // رفع صورة الأيقونة (favicon)
        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('settings', 'public');
            $setting->favicon = $faviconPath;
        }

        $setting->save();

        // بعد الإنشاء ارجع للاندكس مع رسالة نجاح
        return redirect()->route('settings.index')->with('success', 'تم إنشاء الإعداد بنجاح');
    }

    // عرض تفاصيل إعداد معين
    public function show($id)
    {
        $setting = Setting::findOrFail($id);
        return view('settings.show', compact('setting'));
    }

    // عرض صفحة تعديل الإعداد
    public function edit($id)
    {
        $setting = Setting::findOrFail($id);
        return view('settings.edit', compact('setting'));
    }

    // تحديث الإعداد
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,ico|max:1024',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $setting = Setting::findOrFail($id);
        $setting->name = $request->name;
        $setting->name_ar = $request->name_ar;

        if ($request->hasFile('logo')) {
            // حذف اللوغو القديم لو موجود
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $logoPath = $request->file('logo')->store('settings', 'public');
            $setting->logo = $logoPath;
        }

        if ($request->hasFile('favicon')) {
            // حذف الأيقونة القديمة لو موجودة
            if ($setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $faviconPath = $request->file('favicon')->store('settings', 'public');
            $setting->favicon = $faviconPath;
        }

        $setting->save();

        // بعد التحديث ارجع للاندكس مع رسالة نجاح
        return redirect()->route('settings.index')->with('success', 'تم تحديث الإعداد بنجاح');
    }

    // حذف إعداد
    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);

        // حذف الملفات المرتبطة
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
