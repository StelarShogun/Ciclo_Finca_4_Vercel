<?php

namespace App\Support\ProductCatalog;

use Illuminate\Http\UploadedFile;
use SimpleXMLElement;

/**
 * Parses supplier catalog files into normalized row arrays.
 */
final class ProductCatalogFileParser
{
    /**
     * @return list<array<string, mixed>>
     */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        return match ($ext) {
            'zip' => $this->parseZipManifest($file),
            'json' => $this->parseJson((string) file_get_contents($file->getRealPath())),
            'xml' => $this->parseXml((string) file_get_contents($file->getRealPath())),
            'csv', 'txt' => $this->parseCsv((string) file_get_contents($file->getRealPath())),
            default => throw new \InvalidArgumentException('Formato no soportado. Use ZIP (paquete completo), JSON, XML o CSV.'),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseJson(string $raw): array
    {
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            throw new \InvalidArgumentException('JSON inválido.');
        }

        if (isset($data['products']) && is_array($data['products'])) {
            return $this->filterProductRows($data['products']);
        }
        if (isset($data['catalog']['products']) && is_array($data['catalog']['products'])) {
            return $this->filterProductRows($data['catalog']['products']);
        }
        if (array_is_list($data)) {
            return $this->filterProductRows($data);
        }

        return ProductCatalogFieldMapper::rowLooksLikeProduct($data) ? [$data] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseXml(string $raw): array
    {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            throw new \InvalidArgumentException('XML inválido o no legible.');
        }

        $rows = [];
        foreach (['product', 'item', 'articulo', 'articulos', 'producto', 'row', 'record'] as $tag) {
            $nodes = $xml->xpath('//'.$tag) ?: [];
            if ($nodes !== []) {
                foreach ($nodes as $node) {
                    $row = $this->xmlNodeToArray($node);
                    if (ProductCatalogFieldMapper::rowLooksLikeProduct($row)) {
                        $rows[] = ProductCatalogFieldMapper::normalizeRow($row);
                    }
                }
                if ($rows !== []) {
                    return $rows;
                }
            }
        }

        foreach ($xml->children() as $child) {
            $row = $this->xmlNodeToArray($child);
            if (ProductCatalogFieldMapper::rowLooksLikeProduct($row)) {
                $rows[] = ProductCatalogFieldMapper::normalizeRow($row);
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function xmlNodeToArray(SimpleXMLElement $node): array
    {
        $row = [];
        foreach ($node->children() as $name => $child) {
            $row[(string) $name] = trim((string) $child);
        }
        if ($row === [] && trim((string) $node) !== '') {
            $row['name'] = trim((string) $node);
        }
        foreach ($node->attributes() as $attrName => $attrValue) {
            $row[(string) $attrName] = trim((string) $attrValue);
        }

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsv(string $raw): array
    {
        $raw = preg_replace("/^\xEF\xBB\xBF/", '', $raw) ?? $raw;
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        if ($lines === []) {
            return [];
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $headers = str_getcsv(array_shift($lines), $delimiter, '"', '\\');
        if ($headers === false || $headers === []) {
            return [];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, $delimiter, '"', '\\');
            if ($cells === false) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = $cells[$i] ?? '';
            }
            if (ProductCatalogFieldMapper::rowLooksLikeProduct($assoc)) {
                $rows[] = ProductCatalogFieldMapper::normalizeRow($assoc);
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseZipManifest(UploadedFile $file): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new \InvalidArgumentException('No se pudo abrir el archivo ZIP.');
        }

        $jsonName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('/catalog\.json$/i', $name)) {
                $jsonName = $name;
                break;
            }
        }

        if ($jsonName === null) {
            $zip->close();
            throw new \InvalidArgumentException('El ZIP debe incluir catalog.json (exportación completa de Ciclo Finca).');
        }

        $json = $zip->getFromName($jsonName);
        $zip->close();
        if ($json === false) {
            throw new \InvalidArgumentException('No se pudo leer catalog.json del ZIP.');
        }

        return $this->parseJson($json);
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterProductRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (ProductCatalogFieldMapper::rowLooksLikeProduct($row)) {
                $out[] = ProductCatalogFieldMapper::normalizeRow($row);
            }
        }

        return $out;
    }
}
