<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        // Strip non-digit characters if formatted Rupiah slipped through, keeping integer invariant
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
            'source' => ['nullable', 'in:web,pwa,quick_add'],
            'return_month' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.min' => 'Nominal transaksi harus lebih dari Rp 0.',
            'bucket.required_if' => 'Pos anggaran wajib dipilih untuk pengeluaran.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('category_id')) {
                return;
            }

            $user = Auth::user();
            $cat = Category::where('id', $this->input('category_id'))
                ->where('user_id', $user->id)
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
