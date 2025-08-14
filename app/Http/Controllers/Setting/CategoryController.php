<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;


use App\Models\Category;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('transactionStatuses')->get();
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $transactionStatuses = TransactionStatus::all();
        return view('categories.create', compact('transactionStatuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'transaction_statuses' => 'array',
            'transaction_statuses.*' => 'exists:transaction_statuses,id',
        ]);

        $category = Category::create(['name' => $request->name]);
        if ($request->has('transaction_statuses')) {
            $category->transactionStatuses()->sync($request->transaction_statuses);
        }

        return redirect()->route('categories.index')->with('success', 'تم إنشاء المجال بنجاح');
    }

    public function edit(Category $category)
    {
        $transactionStatuses = TransactionStatus::all();
        $selectedStatuses = $category->transactionStatuses->pluck('id')->toArray();

        return view('categories.edit', compact('category', 'transactionStatuses', 'selectedStatuses'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,' . $category->id,
            'transaction_statuses' => 'array',
            'transaction_statuses.*' => 'exists:transaction_statuses,id',
        ]);

        $category->update(['name' => $request->name]);
        if ($request->has('transaction_statuses')) {
            $category->transactionStatuses()->sync($request->transaction_statuses);
        } else {
            $category->transactionStatuses()->detach();
        }

        return redirect()->route('categories.index')->with('success', 'تم تحديث المجال بنجاح');
    }

    public function destroy(Category $category)
    {
        $category->transactionStatuses()->detach();
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'تم حذف المجال بنجاح');
    }
}