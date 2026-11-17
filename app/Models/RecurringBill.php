<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'due_day',
        'bucket',
        'icon',
        'last_paid_month',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaidForMonth(string $yearMonth): bool
    {
        return $this->last_paid_month === $yearMonth;
    }

    public function getStatusBadgeForMonth(string $yearMonth): array
    {
        if ($this->isPaidForMonth($yearMonth)) {
            return [
                'label' => 'Lunas',
                'bg' => 'bg-[#D8F5E5]',
                'text' => 'text-[#114A2D]',
                'is_paid' => true,
            ];
        }

        $today = Carbon::now();
        $currentMonth = $today->format('Y-m');

        if ($yearMonth === $currentMonth) {
            $currentDay = (int) $today->day;
            $diff = $this->due_day - $currentDay;

            if ($diff < 0) {
                return [
                    'label' => 'Lewat ' . abs($diff) . ' hari',
                    'bg' => 'bg-[#FFE2DE]',
                    'text' => 'text-[#8C1D1D]',
                    'is_paid' => false,
                ];
            }

            if ($diff === 0) {
                return [
                    'label' => 'Jatuh tempo hari ini',
                    'bg' => 'bg-[#FFE2DE]',
                    'text' => 'text-[#8C1D1D]',
                    'is_paid' => false,
                ];
            }

            if ($diff <= 5) {
                return [
                    'label' => $diff . ' hari lagi',
                    'bg' => 'bg-[#FFEAA0]',
                    'text' => 'text-[#634800]',
                    'is_paid' => false,
                ];
            }
        }

        return [
            'label' => 'Tgl ' . $this->due_day,
            'bg' => 'bg-[#FCF6EB]',
            'text' => 'text-[#263238]',
            'is_paid' => false,
        ];
    }
}
