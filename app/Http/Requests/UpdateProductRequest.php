<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'category_id'  => ['required','exists:categories,category_id'],
            'supplier_id'  => ['required','exists:suppliers,supplier_id'],
            'name'         => ['required','string','max:200', Rule::unique('products', 'name')->ignore($productId, 'product_id')],
            'description'  => ['nullable','string'],
            'sale_price'   => ['required','numeric','min:0','gt:purchase_price'],
            'purchase_price' => ['required','numeric','min:0'],
            'stock_current' => ['required','integer','min:0','gte:stock_minimum'],
            'stock_minimum' => ['required','integer','min:0'],
            'status'       => ['required','in:active,inactive,out_of_stock,discontinued'],
            'image'        => ['nullable','image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
            'images'       => ['nullable','array'],
            'images.*'     => ['image','mimes:jpeg,png,jpg,gif,svg','max:2048'],
        ];
    }

    public function messages(): array {
        return [
            'required' => 'This field is required.',
            'exists' => 'The selected option is invalid.',
            'string' => 'This field must be a string.',
            'max' => 'This field may not be greater than :max characters.',
            'numeric' => 'This field must be a number.',
            'min' => 'This field must be at least :min.',
            'integer' => 'This field must be an integer.',
            'in' => 'The selected option is invalid.',
            'unique' => 'A product with this name already exists.',
            'gt' => 'This field must be greater than the purchase price.',
            'gte' => 'This field must be greater than or equal to the minimum stock.',
            'image' => 'The file must be a valid image.',
            'mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'max.2048' => 'The image may not be greater than 2MB.',
        ];
    }

    public function attributes(): array {
        return [
            'category_id' => 'category',
            'supplier_id' => 'supplier',
            'name' => 'product name',
            'description' => 'description',
            'sale_price' => 'sale price',
            'purchase_price' => 'purchase price',
            'stock_current' => 'current stock',
            'stock_minimum' => 'minimum stock',
            'status' => 'status',
            'image' => 'image',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'      => $this->name ? trim($this->name) : null,
            'description' => $this->description ? trim($this->description) : null,
            'status'      => $this->status ? strtolower($this->status) : null,
        ]);
    }
}
