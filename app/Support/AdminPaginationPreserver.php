<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Flattens the current request query into hidden input fields
 * so GET forms (e.g. per_page) keep filters like nested classifications[].
 */
final class AdminPaginationPreserver
{
    /**
     * @param  array<int, string>  $except
     */
    public static function hiddenInputsHtml(Request $request, array $except = ['per_page', 'page']): string
    {
        $pairs = [];
        foreach ($request->except($except) as $key => $value) {
            self::flattenField((string) $key, $value, $pairs);
        }

        $html = '';
        foreach ($pairs as $pair) {
            $html .= '<input type="hidden" name="'.e($pair['name']).'" value="'.e($pair['value']).'">'."\n";
        }

        return $html;
    }

    /**
     * @param  array<int, array{name: string, value: string}>  $pairs
     */
    private static function flattenField(string $name, mixed $value, array &$pairs): void
    {
        if (! is_array($value)) {
            $pairs[] = ['name' => $name, 'value' => (string) $value];

            return;
        }

        if ($value === []) {
            return;
        }

        if (array_is_list($value)) {
            foreach ($value as $item) {
                self::flattenField($name.'[]', $item, $pairs);
            }

            return;
        }

        foreach ($value as $key => $item) {
            self::flattenField($name.'['.$key.']', $item, $pairs);
        }
    }
}
