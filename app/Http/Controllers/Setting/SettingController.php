<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /** التخزين */
    private const DISK = 'public';
    private const DIR  = 'settings';

    /**
     * ⚠️ يجب أن يطابق هذا المفتاح ما هو مستخدم في View Composer
     * راجع ViewServiceProvider: Cache::remember('app.setting.latest', ...)
     */
    private const CACHE_KEY = 'app.setting.latest';

    /**
     * عرض الفهرس: يوضّح آخر إعداد محفوظ (إن وُجد) مع زر تعديل أو إنشاء.
     */
    public function index()
    {
        $setting = Setting::query()->latest('id')->first();
        return view('settings.index', compact('setting'));
    }

    /**
     * عرض فورم الإنشاء (يسمح بصف واحد فقط).
     */
    public function create()
    {
        if (Setting::query()->exists()) {
            return redirect()
                ->route('settings.index')
                ->with('success', 'هناك إعداد محفوظ بالفعل.');
        }

        return view('settings.create');
    }

    /**
     * حفظ إعداد جديد.
     */
    public function store(Request $request)
    {
        // حماية مضاعفة: صف واحد فقط
        if (Setting::query()->exists()) {
            return redirect()
                ->route('settings.index')
                ->with('success', 'هناك إعداد محفوظ بالفعل.');
        }

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $setting = new Setting();
        $setting->owner_name = (string) $request->input('owner_name');
        $setting->name       = (string) $request->input('name');
        $setting->name_ar    = (string) $request->input('name_ar');

        if ($request->hasFile('logo')) {
            $setting->logo = $this->storeFile($request->file('logo'));
        }

        if ($request->hasFile('favicon')) {
            $setting->favicon = $this->storeFile($request->file('favicon'));
        }

        $setting->save();
        Cache::forget(self::CACHE_KEY);

        return redirect()
            ->route('settings.show', $setting)
            ->with('success', 'تم إنشاء الإعداد بنجاح');
    }

    /**
     * عرض تفاصيل الإعداد (Route Model Binding).
     */
    public function show(Setting $setting)
    {
        return view('settings.show', compact('setting'));
    }

    /**
     * عرض فورم التعديل.
     */
    public function edit(Setting $setting)
    {
        return view('settings.edit', compact('setting'));
    }

    /**
     * تحديث الإعداد الحالي.
     */
    public function update(Request $request, Setting $setting)
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $setting->owner_name = (string) $request->input('owner_name');
        $setting->name       = (string) $request->input('name');
        $setting->name_ar    = (string) $request->input('name_ar');

        // حذف الشعار عند الطلب
        if ($request->boolean('remove_logo')) {
            $this->deleteIfExists($setting->logo);
            $setting->logo = null;
        }

        // رفع شعار جديد
        if ($request->hasFile('logo')) {
            $this->deleteIfExists($setting->logo);
            $setting->logo = $this->storeFile($request->file('logo'));
        }

        // حذف فاف آيكون عند الطلب
        if ($request->boolean('remove_favicon')) {
            $this->deleteIfExists($setting->favicon);
            $setting->favicon = null;
        }

        // رفع فاف آيكون جديد
        if ($request->hasFile('favicon')) {
            $this->deleteIfExists($setting->favicon);
            $setting->favicon = $this->storeFile($request->file('favicon'));
        }

        $setting->save();
        Cache::forget(self::CACHE_KEY);

        return redirect()
            ->route('settings.show', $setting)
            ->with('success', 'تم تحديث الإعداد بنجاح');
    }

    /**
     * حذف الإعداد (مع حذف الملفات المرتبطة).
     */
    public function destroy(Setting $setting)
    {
        $this->deleteIfExists($setting->logo);
        $this->deleteIfExists($setting->favicon);

        $setting->delete();
        Cache::forget(self::CACHE_KEY);

        return redirect()
            ->route('settings.index')
            ->with('success', 'تم حذف الإعداد بنجاح');
    }

    /**
     * قواعد التحقق:
     * - max:50 لتتوافق مع طول الحقول في قاعدة البيانات.
     * - تجنّبنا قاعدة "image" حتى لا ترفض SVG/ICO.
     */
    private function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:50'],
            'name'       => ['required', 'string', 'max:50'],
            'name_ar'    => ['required', 'string', 'max:50'],

            // Logo: صيغ شائعة + SVG
            'logo'    => [
                'nullable',
                'mimes:png,jpg,jpeg,gif,webp,svg',
                'mimetypes:image/png,image/jpeg,image/gif,image/webp,image/svg+xml',
                'max:4096', // 4MB
            ],

            // Favicon: يدعم ICO + صيغ الصور الشائعة
            'favicon' => [
                'nullable',
                'mimes:ico,png,jpg,jpeg,gif,webp,svg',
                'mimetypes:image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/gif,image/webp,image/svg+xml',
                'max:2048', // 2MB
            ],

            // فلاج للحذف من الفورم (اختياري)
            'remove_logo'    => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * حفظ ملف على القرص العام وإرجاع مساره.
     */
    private function storeFile(UploadedFile $file): string
    {
        return $file->store(self::DIR, self::DISK);
    }

    /**
     * حذف ملف بأمان إن وُجد.
     */
    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            try {
                Storage::disk(self::DISK)->delete($path);
            } catch (\Throwable $e) {
                // نتجاهل خطأ الحذف حتى لا يوقف العملية
            }
        }
    }
}
