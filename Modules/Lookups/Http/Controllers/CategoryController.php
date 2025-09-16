<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\Category;
use Modules\Lookups\Entities\TransactionStatus;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('transactionStatuses')->get();

        return view('lookups::categories.index', compact('categories'));
    }

    public function create()
    {
        $transactionStatuses = TransactionStatus::all();

        return view('lookups::categories.create', compact('transactionStatuses'));
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

        return view('lookups::categories.edit', compact('category', 'transactionStatuses', 'selectedStatuses'));
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
