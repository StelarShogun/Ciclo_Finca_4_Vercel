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

        // Por padre canónico: una fila por nombre (evita duplicados si hay varias raíces con el mismo nombre y mismas subs repetidas).
        $buckets = [];
        foreach ($raw as $row) {
            $phys = (int) $row->parent_category_id;
            $canonical = $canonicalByPhysical[$phys] ?? $phys;
            if (! isset($buckets[$canonical])) {
                $buckets[$canonical] = [];
            }
            $nameKey = mb_strtolower(trim((string) ($row->name ?? '')));
            $cid = (int) $row->category_id;
            if (! isset($buckets[$canonical][$nameKey]) || $cid < $buckets[$canonical][$nameKey]['category_id']) {
                $buckets[$canonical][$nameKey] = [
                    'category_id' => $cid,
                    'name' => $row->name,
                ];
            }
        }

        return collect($buckets)->map(
            fn (array $items) => collect($items)->sortBy(fn ($i) => mb_strtolower((string) ($i['name'] ?? '')))->values()
        );
    }

    /**
     * Filas para la tabla “Jerarquía” en admin: evita listar dos veces la misma sub
     * cuando hay varias categorías raíz con el mismo nombre (mismo padre “lógico”).
     *
     * @return Collection<int, Category>
     */
    public static function hierarchyRowsForAdminDisplay(): Collection
    {
        $canonical = static::canonicalRootIdByPhysicalRootId();

        $rows = static::query()
            ->with('parent:category_id,name')
            ->orderByRaw('CASE WHEN parent_category_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->orderBy('category_id')
            ->get(['category_id', 'name', 'parent_category_id']);

        $seen = [];
        $out = collect();

        foreach ($rows as $row) {
            if ($row->parent_category_id === null) {
                $key = 'root|' . mb_strtolower(trim((string) ($row->name ?? '')));
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out->push($row);

                continue;
            }

            $physParent = (int) $row->parent_category_id;
            $canonParent = $canonical[$physParent] ?? $physParent;
            $key = 'sub|' . $canonParent . '|' . mb_strtolower(trim((string) ($row->name ?? '')));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out->push($row);
        }

        return $out->values();
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


