<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrMenuController extends Controller
{
    public function generateQr(Request $request)
    {
        $restaurantUrl = $request->input('url', config('app.url'));

        $qrCode = new QrCode($restaurantUrl . '/menu/scan');
        $writer = new PngWriter();

        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'max-age=3600'
        ]);
    }

    public function viewMenu()
    {
        $categories = Category::where('status', 'active')->orderBy('sort_order')->get();
        $products = Product::where('status', 'active')->with('category')->get();

        return view('qr-menu.menu', [
            'categories' => $categories,
            'products' => $products
        ]);
    }

    public function getProductsByCategory(Request $request, $categoryId)
    {
        if ($categoryId == 'all') {
            $products = Product::where('status', 'active')->with('category')->get();
        } else {
            $products = Product::where('category_id', $categoryId)
                ->where('status', 'active')
                ->with('category')
                ->get();
        }

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) ($product->selling_price ?? $product->price),
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'image' => $product->image,
                'is_unlimited_stock' => $product->is_unlimited_stock,
                'quantity' => $product->quantity,
            ];
        }));
    }

    public function downloadQr()
    {
        $restaurantUrl = config('app.url');

        $qrCode = new QrCode($restaurantUrl . '/menu/scan');
        $writer = new PngWriter();

        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="menu-qr-code.png"'
        ]);
    }

    public function downloadQrPdf()
    {
        $restaurantUrl = config('app.url');

        $qrCode = new QrCode($restaurantUrl . '/menu/scan');
        $writer = new PngWriter();

        $result = $writer->write($qrCode);
        $qrImage = base64_encode($result->getString());

        return view('qr-menu.qr-pdf', [
            'qrImage' => $qrImage,
            'restaurantName' => config('app.name', 'Restaurant'),
            'restaurantUrl' => $restaurantUrl
        ]);
    }
}
