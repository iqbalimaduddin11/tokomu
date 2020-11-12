<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['title', 'description', 'price', 'stock', 'image', 'category_id', 'shop_id'];

    protected $hidden = ['shop_id', 'category_id'];

    public function shop()
    {
        // return $this->belongsTo('App\Shop')->with('owner');
        return $this->belongsTo('App\Shop');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
