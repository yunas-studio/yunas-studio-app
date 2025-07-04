<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'expense_date',
        'status',
        'category',
        'receipt_image',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function getStatusColorAttribute()
    {
        return $this->status === 'lunas' ? 'success' : 'danger';
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getMonthYearAttribute()
    {
        return Carbon::parse($this->expense_date)->format('F Y');
    }

    public static function getTotalByStatus($status = null)
    {
        $query = self::query();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->sum('amount');
    }

    public static function getMonthlyExpenses($year = null)
    {
        $year = $year ?? date('Y');
        
        $expenses = self::whereYear('expense_date', $year)
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        $result = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create($year, $i, 1)->format('F');
            $expense = $expenses->firstWhere('month', $i);
            $result[$month] = $expense ? $expense->total : 0;
        }
        
        return $result;
    }
}
