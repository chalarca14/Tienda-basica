<?php

namespace App\Models;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 


class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'active',
        'category_id'
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function category()
{
    return $this->belongsTo(Category::class);
}
}

