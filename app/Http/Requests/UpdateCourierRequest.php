<?php

namespace App\Http\Requests;

use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Courier|int|string|null $courier */
        $courier = $this->route('courier');
        $courierId = $courier instanceof Courier ? $courier->id : $courier;

        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('couriers', 'code')->ignore($courierId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('couriers', 'email')->ignore($courierId)],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('couriers', 'phone')->ignore($courierId)],
            'vehicle_type' => ['sometimes', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'license_plate' => ['sometimes', 'string', 'max:15'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
            'registered_at' => ['sometimes', 'date'],
        ];
    }
}
