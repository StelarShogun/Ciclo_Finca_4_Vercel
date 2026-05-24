<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Support\ClientCategoryIcons;
use PHPUnit\Framework\TestCase;

class ClientCategoryIconsTest extends TestCase
{
    public function test_mtb_subcategory_uses_bicycle_icon(): void
    {
        $this->assertSame('fas fa-bicycle', ClientCategoryIcons::iconClassForName('MTB'));
    }

    public function test_ruta_gravel_subcategory_falls_back_via_parent_in_taxonomy(): void
    {
        $icon = ClientCategoryIcons::iconClassForNames(['Ruta / Gravel', 'Bicicletas']);

        $this->assertSame('fas fa-bicycle', $icon);
    }

    public function test_ruta_gravel_alone_matches_gravel_needle(): void
    {
        $this->assertSame('fas fa-bicycle', ClientCategoryIcons::iconClassForName('Ruta / Gravel'));
    }

    public function test_transmision_subcategory_uses_cogs(): void
    {
        $icon = ClientCategoryIcons::iconClassForNames(['Transmisión', 'Componentes']);

        $this->assertSame('fas fa-cogs', $icon);
    }

    public function test_nutricion_bebidas_uses_bottle_icon(): void
    {
        $this->assertSame('fas fa-wine-bottle', ClientCategoryIcons::iconClassForName('Bebidas'));
    }

    public function test_accesorios_category_uses_box_open_icon(): void
    {
        $this->assertSame('fas fa-box-open', ClientCategoryIcons::iconClassForName('Accesorios'));
    }

    public function test_unknown_category_uses_default_box(): void
    {
        $this->assertSame(ClientCategoryIcons::DEFAULT_ICON, ClientCategoryIcons::iconClassForName('Misceláneo XYZ'));
    }

    public function test_icon_class_for_product_uses_subcategory_then_parent(): void
    {
        $product = new Product;
        $parent = new Category(['name' => 'Bicicletas', 'parent_category_id' => null]);
        $parent->category_id = 1;
        $sub = new Category(['name' => 'Modelo X', 'parent_category_id' => 1]);
        $sub->setRelation('parent', $parent);
        $product->setRelation('category', $sub);

        $this->assertSame('fas fa-bicycle', ClientCategoryIcons::iconClassForProduct($product));
    }
}
