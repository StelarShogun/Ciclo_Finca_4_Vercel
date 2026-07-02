<?php

namespace Tests\Unit;

use App\Services\Api\PublicIdMapper;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Lógica pura del mapper (recorrido de paths, sustitución y scrub de URLs)
 * con los mapas sembrados por reflexión: corre sin base de datos.
 */
class PublicIdMapperTest extends TestCase
{
    private function mapperWith(array $maps): PublicIdMapper
    {
        $mapper = new PublicIdMapper;
        $prop = new ReflectionProperty(PublicIdMapper::class, 'maps');
        $prop->setValue($mapper, $maps);

        return $mapper;
    }

    public function test_catalog_spec_replaces_nested_ids_with_public_ones(): void
    {
        $mapper = $this->mapperWith([
            'product' => [7 => 'PUBP7'],
            'category' => [3 => 'PUBC3', 4 => 'PUBC4'],
            'brand' => [2 => 'PUBB2'],
        ]);

        $payload = [
            'products' => [[
                'id' => 7,
                'category' => ['id' => 3, 'name' => 'Cascos'],
                'parentCategory' => ['id' => 4, 'name' => 'Seguridad'],
                'brands' => [['id' => 2, 'name' => 'Marca']],
            ]],
            'categories' => [['id' => 4, 'children' => [['id' => 3, 'url' => '/catalog?category_id=3']]]],
            'brands' => [['id' => 2, 'name' => 'Marca']],
            'filters' => ['categoryId' => 3, 'brandId' => 2, 'search' => ''],
            'selectedCategory' => ['id' => 3, 'name' => 'Cascos'],
            'pagination' => ['currentPage' => 1, 'total' => 1],
        ];

        $out = $mapper->map('catalog', $payload);

        $this->assertSame('PUBP7', $out['products'][0]['id']);
        $this->assertSame('PUBC3', $out['products'][0]['category']['id']);
        $this->assertSame('PUBC4', $out['products'][0]['parentCategory']['id']);
        $this->assertSame('PUBB2', $out['products'][0]['brands'][0]['id']);
        $this->assertSame('PUBC4', $out['categories'][0]['id']);
        $this->assertSame('PUBC3', $out['categories'][0]['children'][0]['id']);
        $this->assertSame('PUBC3', $out['filters']['categoryId']);
        $this->assertSame('PUBB2', $out['filters']['brandId']);
        $this->assertSame('PUBC3', $out['selectedCategory']['id']);
        // El scrub reescribe la URL embebida con id numérico.
        $this->assertSame('/catalog?category_id=PUBC3', $out['categories'][0]['children'][0]['url']);
        // Lo no mapeado no se toca.
        $this->assertSame(1, $out['pagination']['currentPage']);
    }

    public function test_null_and_missing_branches_do_not_break(): void
    {
        $mapper = $this->mapperWith(['product' => [1 => 'PUBP1']]);

        $out = $mapper->map('catalog', [
            'products' => [['id' => 1, 'category' => null, 'brands' => []]],
            'selectedCategory' => null,
            'filters' => ['categoryId' => null, 'brandId' => null],
        ]);

        $this->assertSame('PUBP1', $out['products'][0]['id']);
        $this->assertNull($out['selectedCategory']);
        $this->assertNull($out['filters']['categoryId']);
    }

    public function test_scrub_rewrites_product_urls(): void
    {
        $mapper = $this->mapperWith(['product' => [9 => 'PUBP9']]);

        $out = $mapper->map('cart', [
            'items' => [['productId' => 9, 'productUrl' => '/products/9']],
        ]);

        $this->assertSame('PUBP9', $out['items'][0]['productId']);
        $this->assertSame('/product/PUBP9', $out['items'][0]['productUrl']);
    }
}
