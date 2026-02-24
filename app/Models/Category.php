<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['name','description','parent_category_id'];
    
    // Relationship with products
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
    
    // Relationship with parent category (for subcategories)
    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_category_id', 'category_id');
    }
    
    // Relationship with child categories
    public function childCategories()
    {
        return $this->hasMany(Category::class, 'parent_category_id', 'category_id');
    }
}


