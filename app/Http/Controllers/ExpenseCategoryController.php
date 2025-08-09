<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(ExpenseCategory::class, 'expenseCategory');
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('admin.expenses.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.expenses.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log request data
        \Log::info('ExpenseCategory store request:', $request->all());
        
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories',
            'is_monthly_default' => 'boolean',
        ]);

        // Get the value from the request
        $isMonthlyDefault = $request->input('is_monthly_default') == '1';
        \Log::info('Setting is_monthly_default to:', ['value' => $isMonthlyDefault, 'raw_input' => $request->input('is_monthly_default')]);
        
        $category = ExpenseCategory::create([
            'name' => $request->name,
            'is_monthly_default' => $isMonthlyDefault,
        ]);
        
        // Debug: Log created category
        \Log::info('ExpenseCategory created:', $category->toArray());

        return redirect()->route('expense-categories.index')
            ->with('success', 'Kategori expense berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('admin.expenses.categories.edit', compact('expenseCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        // Debug: Log request data
        \Log::info('ExpenseCategory update request:', $request->all());
        \Log::info('is_monthly_default present:', ['has' => $request->has('is_monthly_default'), 'exists' => $request->exists('is_monthly_default')]);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'is_monthly_default' => 'boolean',
        ]);

        // Get the value from the request
        $isMonthlyDefault = $request->input('is_monthly_default') == '1';
        \Log::info('Setting is_monthly_default to:', ['value' => $isMonthlyDefault, 'raw_input' => $request->input('is_monthly_default')]);
        
        $expenseCategory->update([
            'name' => $request->name,
            'is_monthly_default' => $isMonthlyDefault,
        ]);
        
        // Debug: Log updated category
        \Log::info('ExpenseCategory updated:', $expenseCategory->fresh()->toArray());

        return redirect()->route('expense-categories.index')
            ->with('success', 'Kategori expense berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Cek apakah kategori digunakan oleh expense
        if ($expenseCategory->expenses()->count() > 0) {
            return redirect()->route('expense-categories.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh expense.');
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', 'Kategori expense berhasil dihapus.');
    }
    
    /**
     * Toggle monthly default status for the specified category.
     */
    public function toggleMonthlyDefault(ExpenseCategory $expenseCategory)
    {
        // Authorize the action
        $this->authorize('toggleMonthlyDefault', $expenseCategory);
        
        // Debug: Log toggle action
        \Log::info('Toggling monthly default for category:', $expenseCategory->toArray());
        
        // Toggle the status
        $expenseCategory->update([
            'is_monthly_default' => !$expenseCategory->is_monthly_default
        ]);
        
        // Debug: Log updated category
        \Log::info('Category monthly default toggled:', $expenseCategory->fresh()->toArray());
        
        return redirect()->route('expense-categories.index')
            ->with('success', 'Status default bulanan berhasil diperbarui.');
    }
}