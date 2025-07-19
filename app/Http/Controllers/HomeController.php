<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Models\Expense;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if (view()->exists($request->path())) {
            // Get total expenses
            $totalExpenses = Expense::sum('amount');
            
            // Get monthly expenses for current year
            $currentYear = date('Y');
            $monthlyExpenses = [];
            $monthlyLabels = [];
            
            for ($i = 1; $i <= 12; $i++) {
                $month = Carbon::create($currentYear, $i, 1);
                $monthlyLabels[] = $month->format('M'); // Jan, Feb, etc.
                
                $amount = Expense::whereYear('expense_date', $currentYear)
                    ->whereMonth('expense_date', $i)
                    ->sum('amount');
                    
                $monthlyExpenses[] = $amount;
            }
            
            // Get expenses by category
            $expensesByCategory = Expense::selectRaw('category, SUM(amount) as total')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
            
            return view($request->path(), compact(
                'totalExpenses', 
                'monthlyExpenses', 
                'monthlyLabels',
                'expensesByCategory'
            ));
        }
        return abort(404);
    }

    public function root()
    {
        // Get total expenses
        $totalExpenses = Expense::sum('amount');
        
        // Get monthly expenses for current year
        $currentYear = date('Y');
        $monthlyExpenses = [];
        $monthlyLabels = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create($currentYear, $i, 1);
            $monthlyLabels[] = $month->format('M'); // Jan, Feb, etc.
            
            $amount = Expense::whereYear('expense_date', $currentYear)
                ->whereMonth('expense_date', $i)
                ->sum('amount');
                
            $monthlyExpenses[] = $amount;
        }
        
        // Get expenses by category
        $expensesByCategory = Expense::selectRaw('category, SUM(amount) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
        
        return view('index', compact(
            'totalExpenses', 
            'monthlyExpenses', 
            'monthlyLabels',
            'expensesByCategory'
        ));
    }

    /*Language Translation*/
    public function lang($locale)
    {
        if ($locale) {
            App::setLocale($locale);
            Session::put('lang', $locale);
            Session::save();
            return redirect()->back()->with('locale', $locale);
        } else {
            return redirect()->back();
        }
    }

    public function FormSubmit(Request $request)
    {
        return view('form-repeater');
    }
}
