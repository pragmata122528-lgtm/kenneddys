<?php

namespace App\Http\Requests\Checkout;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'payment_method' => ['sometimes', 'string', new Enum(PaymentMethod::class)],
            'shipping_amount' => ['sometimes', 'numeric', 'min:0'],
            'simulate_failure' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item must be included in your checkout order.',
            'items.min' => 'At least one item must be included in your checkout order.',
            'items.*.product_id.required' => 'Product ID is missing for an item in the cart.',
            'items.*.product_id.exists' => 'One or more products in your cart are no longer available.',
            'items.*.quantity.required' => 'Quantity must be specified for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'payment_method.enum' => 'Payment method must be one of: jazzcash, easypaisa, cod.',
        ];
    }
}
