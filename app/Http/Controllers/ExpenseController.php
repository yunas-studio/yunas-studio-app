<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get filter parameters from request
        $month = $request->input('month');
        $year = $request->input('year');
        
        // Build the main query for expenses
        $query = Expense::query();
        
        // Apply filters
        if ($month) {
            $query->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $query->whereYear('expense_date', $year);
        }
        
        // Get paginated results with query string preserved for pagination links
        $expenses = $query->orderBy('expense_date', 'desc')->paginate(10)->withQueryString();
        
        // Calculate totals based on the current filter
        $filteredQuery = Expense::query();
        
        // Apply the same filters to the total calculation query
        if ($month) {
            $filteredQuery->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $filteredQuery->whereYear('expense_date', $year);
        }
        
        // Calculate total expenses
        $totalExpenses = $filteredQuery->sum('amount');
        
        // Get expenses by category for the filtered data
        $expensesByCategory = (clone $filteredQuery)
            ->selectRaw('category, SUM(amount) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();
        
        // Get all months for the dropdown
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create(null, $i, 1)->format('F');
        }
        
        // Get years for the dropdown (last 5 years)
        $years = range(date('Y') - 5, date('Y'));
        
        return view('admin.expenses.index', compact(
            'expenses', 
            'totalExpenses', 
            'month', 
            'year', 
            'months', 
            'years',
            'expensesByCategory'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ]);
        
        $data = $request->except('receipt_image');
        
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $data['receipt_image'] = $path;
        }
        
        Expense::create($data);
        
        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        return view('admin.expenses.edit', compact('expense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ]);
        
        $data = $request->except('receipt_image');
        
        if ($request->hasFile('receipt_image')) {
            // Delete old image if exists
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $data['receipt_image'] = $path;
        }
        
        $expense->update($data);
        
        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }
        
        $expense->delete();
        
        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran berhasil dihapus');
    }
}
