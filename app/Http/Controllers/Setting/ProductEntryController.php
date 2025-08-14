<?php

namespace App\Http\Controllers\Setting;
use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\ProductEntry;
use Illuminate\Http\Request;

class ProductEntryController extends Controller
{
    public function index()
    {
        $entries = ProductEntry::with('product')->get();
        return view('product_entries.index', compact('entries'));
    }

    public function create()
    {
        $products = Product::all();
        return view('product_entries.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|numeric|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'entry_date'     => 'required|date',
        ]);

        ProductEntry::create($request->all());

        return redirect()->route('product_entries.index')->with('success', 'تم إضافة الإدخال بنجاح');
    }

    public function edit(ProductEntry $productEntry)
    {
        $products = Product::all();
        return view('product_entries.edit', compact('productEntry', 'products'));
    }

    public function update(Request $request, ProductEntry $productEntry)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|numeric|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'entry_date'     => 'required|date',
        ]);

        $productEntry->update($request->all());

        return redirect()->route('product_entries.index')->with('success', 'تم تحديث الإدخال بنجاح');
    }

    public function destroy(ProductEntry $productEntry)
    {
        $productEntry->delete();
        return redirect()->route('product_entries.index')->with('success', 'تم حذف الإدخال بنجاح');
    }
}