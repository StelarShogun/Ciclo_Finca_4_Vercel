<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'categoria_id';
    public $timestamps = true;
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = ['nombre','descripcion','categoria_padre_id'];
    
    // Relación con productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id', 'categoria_id');
    }
    
    // Relación con categoría padre (para subcategorías)
    public function categoriaPadre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id', 'categoria_id');
    }
    
    // Relación con categorías hijas
    public function categoriasHijas()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id', 'categoria_id');
    }
}


