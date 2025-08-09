<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
// Tambahkan use statement
use App\Models\ExpenseCategory;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Expense::class);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get filter parameters from request
        $month = $request->input('month');
        $year = $request->input('year');
        $category_id = $request->input('category_id');
        $sort_amount = $request->input('sort_amount');
        
        // Build the main query for expenses
        $query = Expense::query();
        
        // Apply filters
        if ($month) {
            $query->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $query->whereYear('expense_date', $year);
        }
        
        if ($category_id) {
            $query->where('category_id', $category_id);
        }
        
        // Apply sorting
        if ($sort_amount) {
            $query->orderBy('amount', $sort_amount);
        } else {
            $query->orderBy('expense_date', 'desc');
        }
        
        // Get paginated results with query string preserved for pagination links
        $expenses = $query->paginate(10)->withQueryString();
        
        // Calculate totals based on the current filter
        $filteredQuery = Expense::query();
        
        // Apply the same filters to the total calculation query
        if ($month) {
            $filteredQuery->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $filteredQuery->whereYear('expense_date', $year);
        }
        
        if ($category_id) {
            $filteredQuery->where('category_id', $category_id);
        }
        
        // Calculate total expenses
        $totalExpenses = $filteredQuery->sum('amount');
        
        // Get expenses by category for the filtered data
        $expensesByCategory = (clone $filteredQuery)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
            ->whereNotNull('expenses.category_id')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('total', 'desc')
            ->get();
        
        // Get all months for the dropdown
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create(null, $i, 1)->format('F');
        }
        
        // Get years for the dropdown (from 2020 to 2028)
        $years = range(2020, 2028);
        
        // Get all categories for the dropdown
        $categories = ExpenseCategory::orderBy('name')->get();
        
        return view('admin.expenses.index', compact(
            'expenses', 
            'totalExpenses', 
            'month', 
            'year', 
            'months', 
            'years',
            'expensesByCategory',
            'categories',
            'category_id',
            'sort_amount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('admin.expenses.create', compact('categories'));
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
            'category_id' => 'nullable|exists:expense_categories,id', // Ubah validasi
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
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('admin.expenses.edit', compact('expense', 'categories'));
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
            'category_id' => 'nullable|exists:expense_categories,id', // Ubah validasi
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

    // Tambahkan method ini ke dalam class ExpenseController
    /**
     * Generate monthly default expenses.
     */
    public function generateMonthlyExpenses(Request $request)
    {
        // Authorize the action
        $this->authorize('create', Expense::class);
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);
    
        $month = $request->month;
        $year = $request->year;
        
        // Get the first day of the month
        $date = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        
        // Get all monthly default categories
        $defaultCategories = ExpenseCategory::where('is_monthly_default', true)->get();
        
        $count = 0;
        foreach ($defaultCategories as $category) {
            // Check if expense for this category already exists for this month
            $exists = Expense::where('category_id', $category->id)
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $month)
                ->exists();
                
            if (!$exists) {
                Expense::create([
                    'name' => 'Monthly Expense: ' . date('F Y', strtotime($date)),
                    'description' => 'Auto-generated monthly expense for ' . $category->name,
                    'amount' => 0, // Default amount, to be filled by user
                    'expense_date' => $date,
                    'category_id' => $category->id,
                ]);
                $count++;
            }
        }
        
        if ($count > 0) {
            return redirect()->route('expenses.index', ['month' => $month, 'year' => $year])
                ->with('success', "$count biaya bulanan default berhasil dibuat.");
        } else {
            return redirect()->route('expenses.index', ['month' => $month, 'year' => $year])
                ->with('info', 'Semua biaya bulanan default sudah ada untuk bulan ini.');
        }
    }
}
