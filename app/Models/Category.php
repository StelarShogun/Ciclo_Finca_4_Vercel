<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Cada categoría raíz (física) -> id canónico = MIN(category_id) entre raíces con el mismo nombre.
     * Los desplegables de inventario usan ese MIN; las subcategorías en BD cuelgan del parent_category_id real.
     * Sin este mapa, el árbol JS queda vacío cuando el padre “visible” no coincide con el id padre de las hijas.
     */
    public static function canonicalRootIdByPhysicalRootId(): array
    {
        $roots = static::query()
            ->whereNull('parent_category_id')
            ->get(['category_id', 'name']);

        $map = [];
        foreach ($roots->groupBy(fn ($c) => mb_strtolower(trim((string) ($c->name ?? '')))) as $group) {
            $canonical = (int) $group->min('category_id');
            foreach ($group as $r) {
                $map[(int) $r->category_id] = $canonical;
            }
        }

        return $map;
    }

    /**
     * Subcategorías agrupadas por id de padre canónico (coincide con el value del select de categoría raíz deduplicada).
     *
     * @return Collection<int, Collection<int, array{category_id: int, name: string}>>
     */
    public static function subcategoriesGroupedByCanonicalParent(): Collection
    {
        $canonicalByPhysical = static::canonicalRootIdByPhysicalRootId();

        $raw = static::query()
            ->whereNotNull('parent_category_id')
            ->orderBy('name')
            ->get(['category_id', 'name', 'parent_category_id']);

        $buckets = [];
        foreach ($raw as $row) {
            $phys = (int) $row->parent_category_id;
            $canonical = $canonicalByPhysical[$phys] ?? $phys;
            if (! isset($buckets[$canonical])) {
                $buckets[$canonical] = [];
            }
            $buckets[$canonical][(int) $row->category_id] = [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
            ];
        }

        return collect($buckets)->map(fn (array $items) => collect($items)->values());
    }

    /** Ids de todas las raíces físicas que comparten el mismo padre canónico (MIN por nombre). */
    public static function physicalRootIdsForCanonicalParent(int $canonicalParentId): array
    {
        $map = static::canonicalRootIdByPhysicalRootId();
        $ids = [];
        foreach ($map as $phys => $canon) {
            if ($canon === $canonicalParentId) {
                $ids[] = $phys;
            }
        }

        return $ids !== [] ? $ids : [$canonicalParentId];
    }
}


