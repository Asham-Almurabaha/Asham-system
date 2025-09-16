<?php

namespace Modules\Lookups\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lookups\Entities\ProductType;

class ProductTypeController extends Controller
{
    public function index()
    {
        $productTypes = ProductType::orderBy('name')->get();

        return view('lookups::product_types.index', compact('productTypes'));
    }

    public function create()
    {
        return view('lookups::product_types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_types,name'],
            'description' => ['nullable', 'string'],
        ]);

        ProductType::create($data);

        return redirect()
            ->route('product_types.index')
            ->with('success', __('lookups::messages.product_types.created'));
    }

    public function edit(ProductType $productType)
    {
        return view('lookups::product_types.edit', compact('productType'));
    }

    public function update(Request $request, ProductType $productType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_types,name,' . $productType->id],
            'description' => ['nullable', 'string'],
        ]);

        $productType->update($data);

        return redirect()
            ->route('product_types.index')
            ->with('success', __('lookups::messages.product_types.updated'));
    }

    public function destroy(ProductType $productType)
    {
        $productType->delete();

        return redirect()
            ->route('product_types.index')
            ->with('success', __('lookups::messages.product_types.deleted'));
    }
}
