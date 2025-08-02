<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\ProductMedia;
use App\Models\Product;

class ProductImageController extends Controller
{
    public function upload(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        if ($request->hasFile('images')) {

            $destination = storage_path('app/public/products/' . $product->id);
            if (!is_dir($destination)) {
                mkdir($destination, 0777, true);
            }

            foreach ($request->file('images') as $file) {
                $filename = uniqid('product_') . '.' . $file->getClientOriginalExtension();
                $path = $file->move($destination, $filename);

                $media = ProductMedia::create([
                    'product_id' => $product->id,
                    'file' => $filename,
                    'ordering' => ProductMedia::where('product_id', $product->id)->max('ordering') + 1,
                ]);
            }

            return response()->json(['success' => true]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    }

    public function list($productId)
    {
        $product = Product::findOrFail($productId);
        $media = $product->media()->orderBy('ordering')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'file' => $item->file,
                'url' => asset('storage/products/' . $item->product_id . '/' . $item->file),
            ];
        });
        return response()->json($media);
    }

    public function delete($productId, $mediaId)
    {
        $media = ProductMedia::where('product_id', $productId)->where('id', $mediaId)->firstOrFail();
        $destination = storage_path('app/public/products/' . $media->product_id);
        if (file_exists($destination . '/' . $media->file)) {
            unlink($destination . '/' . $media->file);
        }

        $media->delete();
        return response()->json(['success' => true]);
    }

    public function sort(Request $request, $productId)
    {
        $order = $request->input('order', []);
        foreach ($order as $index => $mediaId) {
            ProductMedia::where('product_id', $productId)->where('id', $mediaId)->update(['ordering' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }
} 