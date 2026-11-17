<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return Auth::check() && $transaction && $transaction->user_id === Auth::id();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount') && is_string($this->amount)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->amount);
            $this->merge(['amount' => $cleaned === '' ? null : (int) $cleaned]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'transaction_date' => ['required', 'date'],
            'bucket' => ['nullable', 'required_if:type,expense', 'in:needs,wants,emergency'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'income_source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('category_id')) {
                return;
            }

            $cat = Category::where('id', $this->input('category_id'))
                ->where('user_id', Auth::id())
                ->first();

            if (!$cat) {
                $validator->errors()->add('category_id', 'Kategori tidak valid atau bukan milik akun Anda.');
                return;
            }

            if ($this->input('type') === 'expense' && $cat->bucket !== $this->input('bucket')) {
                $validator->errors()->add('category_id', 'Kategori tidak sesuai dengan pos anggaran yang dipilih.');
            }

            if ($this->input('type') === 'income' && $cat->transaction_type !== 'income') {
                $validator->errors()->add('category_id', 'Kategori yang dipilih bukan kategori pemasukan.');
            }
        });
    }
}
