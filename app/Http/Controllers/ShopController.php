<?php

namespace App\Http\Controllers;

use App\Product;
use App\Shop;
use App\Transaction;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function getShops()
    {
        $shops = Shop::select(['id', 'shop_name', 'image'])->get();

        if ($shops->isEmpty()) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', $shops, 200);
    }

    public function getShop($id)
    {
        $shop = Shop::find($id);

        if (!$shop) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', $shop, 200);
    }

    public function myShop($user_id)
    {
        $shop = Shop::where('user_id', $user_id)->with('owner', 'shopdetail')->first();

        if (!$shop) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', $shop, 200);
    }

    public function setShop(Request $request, Shop $shop)
    {
        if (!User::find($request->user_id)) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'shop_name' => 'string|max:255',
            'description' => 'string',
        ]);

        if ($validator->fails()) {
            return response($validator->errors());
        }

        if (!Shop::where('user_id', $request->user_id)->count() < 1) {
            $shop = Shop::where('user_id', $request->user_id)->first();

            $data = $request->except(['image']);

            $result = array_filter($data);

            if ($request->hasFile('image')) {
                $file = base64_encode(file_get_contents($request->image));

                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'https://freeimage.host/api/1/upload', [
                    'form_params' => [
                        'key' => '6d207e02198a847aa98d0a2a901485a5',
                        'action' => 'upload',
                        'source' => $file,
                        'format' => 'json'
                    ]
                ]);

                $data = $response->getBody()->getContents();
                $data = json_decode($data);
                $image = $data->image->url;

                $shop->image = $image;
            }

            try {
                $shop->update($result);
    
                $shop = Shop::where('user_id', $request->user_id)->with('owner')->first();
                return $this->sendResponse('success', 'Toko Berhasil Diupdate', compact('shop'), 200);
            } catch (\Throwable $th) {
                return $this->sendResponse('error', 'Toko Gagal Diupdate', $th->getMessage(), 500);
            }
        } else {
            $shop->shop_name = $request->shop_name;
            $shop->description = $request->description;
            if ($request->hasFile('image')) {
                $file = base64_encode(file_get_contents($request->image));

                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'https://freeimage.host/api/1/upload', [
                    'form_params' => [
                        'key' => '6d207e02198a847aa98d0a2a901485a5',
                        'action' => 'upload',
                        'source' => $file,
                        'format' => 'json'
                    ]
                ]);

                $data = $response->getBody()->getContents();
                $data = json_decode($data);
                $image = $data->image->url;

                $shop->image = $image;
            }
            $shop->user_id = $request->user_id;

            try {
                $shop->save();
    
                $shop = Shop::where('user_id', $request->user_id)->with('owner')->first();
                return $this->sendResponse('success', 'Toko Berhasil Ditambah', compact('shop'), 200);
            } catch (\Throwable $th) {
                return $this->sendResponse('error', 'Toko Gagal Ditambah', $th->getMessage(), 500);
            }
        }
    }

    public function getProducts($id)
    {
        $products = Product::select(['id', 'product_name', 'price', 'stock', 'image'])->where('shop_id', $id)->get();

        if ($products->isEmpty()) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', $products, 200);
    }

    public function getProductsByCategory(Request $request, $id)
    {
        $shop = Shop::where('user_id', $id)->first();

        if (!$shop) {
            return $this->sendResponse('error', 'Shop Tidak Ada', null, 404);
        }

        $products = Product::select(['id', 'product_name', 'price', 'stock', 'image'])->where('category_id' , '=' , $request->category_id, 'AND', 'shop_id', '=', $id)->get();

        if ($products->isEmpty()) {
            return $this->sendResponse('error', 'Data Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', compact('shop', 'products'), 200);
    }

    public function getMyTransaction($shop_id)
    {
        $shop = Shop::find($shop_id);

        if (!$shop) {
            return $this->sendResponse('error', 'Shop Tidak Ada', null, 404);
        }

        $transactions = Transaction::where('shop_id', $shop_id)->with(['buyer', 'buying' => function($query) {
            $query->select(['id', 'product_name', 'price', 'stock', 'image']);
        }])->get();

        if (!$transactions) {
            return $this->sendResponse('error', 'Transactions Tidak Ada', null, 404);
        }

        return $this->sendResponse('success', 'Data Berhasil Diambil', $transactions, 200);
    }
}
