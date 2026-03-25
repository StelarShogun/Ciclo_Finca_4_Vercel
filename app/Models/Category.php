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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id', 'category_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id', 'category_id');
    }

    public function parentCategory()
    {
        return $this->parent();
    }

    public function childCategories()
    {
        return $this->children();
    }
}


