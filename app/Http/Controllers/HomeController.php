<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Expense;
use App\Models\Transaksi;
use App\Models\Additional;
use App\Models\Balance;
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
            $totalExpenses = 0;
            $chartData = [];
            $chartLabels = [];
            $expensesByCategory = collect();
            $totalIncome = 0;
            $printingCosts = 0;
            $totalCustomers = 0;
            $unpaidDebts = 0;
            $paidDebts = 0;
            $currentBalance = 0;
            $currentYear = date('Y');
            $period = $request->input('period', 'monthly'); // Default to monthly
            $chartType = $period; // For passing to view

            if (auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isKasir())) {

                $totalExpenses = Expense::where('type', 'expense')->sum('amount');


                $totalIncome = Expense::where('type', 'income')->sum('amount');


                $printingCosts = Transaksi::join('additional_transaksi', 'transaksi.transaction_id', '=', 'additional_transaksi.transaksi_id')
                    ->join('additionals', 'additional_transaksi.additional_id', '=', 'additionals.id')
                    ->where('additionals.name', 'LIKE', '%Cetak%')
                    ->sum('additional_transaksi.price');

                // Get total unique customers by phone number
                $totalCustomers = Transaksi::distinct('phone_number')->count('phone_number');

                // Get transaction payment statistics
                $unpaidDebts = Transaksi::where('status', 'unpaid')->count();

                $paidDebts = Transaksi::where('status', 'paid')->count();

                // Get current balance from expenses
                $currentBalance = Expense::where('type', 'income')->sum('amount') - Expense::where('type', 'expense')->sum('amount');

                $isKasir = auth()->user()->isKasir();


                if ($isKasir) {
                    $period = 'daily';
                    $chartType = 'daily';
                }

                // Get chart data based on period
                $chartData = $this->getChartData($period, $isKasir);
                $chartLabels = $chartData['labels'];
                $monthlyExpenses = $chartData['expenses'];
                $monthlyIncome = $chartData['income'];

                // Get expenses by category
                $expensesByCategory = Expense::join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                    ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
                    ->whereNotNull('expenses.category_id')
                    ->groupBy('expense_categories.id', 'expense_categories.name')
                    ->orderBy('total', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                // Initialize empty data for non-authorized users
                $monthlyExpenses = [];
                $monthlyIncome = [];

                if ($period == 'daily') {
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $day = Carbon::today()->subDays($i);
                        $chartLabels[] = $day->format('d M');
                        $monthlyExpenses[] = 0;
                        $monthlyIncome[] = 0;
                    }
                } elseif ($period == 'yearly') {
                    // Last 5 years
                    for ($i = 4; $i >= 0; $i--) {
                        $year = Carbon::now()->subYears($i)->year;
                        $chartLabels[] = $year;
                        $monthlyExpenses[] = 0;
                        $monthlyIncome[] = 0;
                    }
                } else {
                    // Monthly (default)
                    for ($i = 1; $i <= 12; $i++) {
                        $month = Carbon::create($currentYear, $i, 1);
                        $chartLabels[] = $month->format('M');
                        $monthlyExpenses[] = 0;
                        $monthlyIncome[] = 0;
                    }
                }
            }

            return view($request->path(), compact(
                'totalExpenses',
                'monthlyExpenses',
                'chartLabels',
                'expensesByCategory',
                'totalIncome',
                'printingCosts',
                'totalCustomers',
                'unpaidDebts',
                'paidDebts',
                'currentBalance',
                'monthlyIncome',
                'chartType',
                'period'
            ));
        }
        return abort(404);
    }

    /**
     * Get chart data based on period
     *
     * @param string $period
     * @param bool $isKasir
     * @return array
     */
    private function getChartData($period, $isKasir)
    {
        $labels = [];
        $expenses = [];
        $income = [];
        $currentYear = date('Y');

        if ($period == 'daily') {
            // Last 7 days data
            for ($i = 6; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $labels[] = $day->format('d M');

                $expenseAmount = Expense::where('type', 'expense')
                    ->whereDate('expense_date', $day)
                    ->sum('amount');

                $incomeAmount = Expense::where('type', 'income')
                    ->whereDate('expense_date', $day)
                    ->sum('amount');

                $expenses[] = $expenseAmount;
                $income[] = $incomeAmount;
            }
        } elseif ($period == 'yearly' && !$isKasir) {
            // Last 5 years data (only for superadmin)
            for ($i = 4; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->year;
                $labels[] = $year;

                $expenseAmount = Expense::where('type', 'expense')
                    ->whereYear('expense_date', $year)
                    ->sum('amount');

                $incomeAmount = Expense::where('type', 'income')
                    ->whereYear('expense_date', $year)
                    ->sum('amount');

                $expenses[] = $expenseAmount;
                $income[] = $incomeAmount;
            }
        } else {
            // Monthly data (default)
            for ($i = 1; $i <= 12; $i++) {
                $month = Carbon::create($currentYear, $i, 1);
                $labels[] = $month->format('M'); // Jan, Feb, etc.

                if ($isKasir) {
                    // Today's data only for kasir
                    $today = Carbon::today();
                    if ($today->month == $i && $today->year == $currentYear) {
                        $expenseAmount = Expense::where('type', 'expense')
                            ->whereDate('expense_date', $today)
                            ->sum('amount');

                        $incomeAmount = Expense::where('type', 'income')
                            ->whereDate('expense_date', $today)
                            ->sum('amount');
                    } else {
                        $expenseAmount = 0;
                        $incomeAmount = 0;
                    }
                } else {
                    // Monthly data for superadmin
                    $expenseAmount = Expense::where('type', 'expense')
                        ->whereYear('expense_date', $currentYear)
                        ->whereMonth('expense_date', $i)
                        ->sum('amount');

                    $incomeAmount = Expense::where('type', 'income')
                        ->whereYear('expense_date', $currentYear)
                        ->whereMonth('expense_date', $i)
                        ->sum('amount');
                }

                $expenses[] = $expenseAmount;
                $income[] = $incomeAmount;
            }
        }

        return [
            'labels' => $labels,
            'expenses' => $expenses,
            'income' => $income
        ];
    }

    public function root()
    {
        if(Auth::user()->isUser()){
            return redirect('transaksi');
        }
        // Initialize variables
        $totalExpenses = 0;
        $monthlyExpenses = [];
        $monthlyLabels = [];
        $expensesByCategory = collect();
        $totalIncome = 0;
        $printingCosts = 0;
        $totalCustomers = 0;
        $unpaidDebts = 0;
        $paidDebts = 0;
        $currentBalance = 0;
        $currentYear = date('Y');

        // Only show data if user is authorized
        if (auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isKasir())) {
            // Get total expenses from expenses with type 'expense'
            $totalExpenses = Expense::where('type', 'expense')->sum('amount');

            // Get total income from expenses with type 'income'
            $totalIncome = Expense::where('type', 'income')->sum('amount');

            // Get printing costs from additionals that contain 'Cetak'
            $printingCosts = Transaksi::join('additional_transaksi', 'transaksi.transaction_id', '=', 'additional_transaksi.transaksi_id')
                ->join('additionals', 'additional_transaksi.additional_id', '=', 'additionals.id')
                ->where('additionals.name', 'LIKE', '%Cetak%')
                ->sum('additional_transaksi.price');

            // Get total unique customers by phone number
            $totalCustomers = Transaksi::distinct('phone_number')->count('phone_number');

            // Get transaction payment statistics
            $unpaidDebts = Transaksi::where('status', 'unpaid')->count();

            $paidDebts = Transaksi::where('status', 'paid')->count();

            // Get current balance from expenses
            $currentBalance = Expense::where('type', 'income')->sum('amount') - Expense::where('type', 'expense')->sum('amount');

            // Get monthly expenses and income for current year
            $monthlyIncome = [];
            $isKasir = auth()->user()->isKasir();

            for ($i = 1; $i <= 12; $i++) {
                $month = Carbon::create($currentYear, $i, 1);
                $monthlyLabels[] = $month->format('M'); // Jan, Feb, etc.


                if ($isKasir) {
                    // Today's data only
                    $today = Carbon::today();
                    if ($today->month == $i && $today->year == $currentYear) {
                        $expenseAmount = Expense::where('type', 'expense')
                            ->whereDate('expense_date', $today)
                            ->sum('amount');

                        $incomeAmount = Expense::where('type', 'income')
                            ->whereDate('expense_date', $today)
                            ->sum('amount');
                    } else {
                        $expenseAmount = 0;
                        $incomeAmount = 0;
                    }
                } else {
                    // Monthly data for superadmin
                    $expenseAmount = Expense::where('type', 'expense')
                        ->whereYear('expense_date', $currentYear)
                        ->whereMonth('expense_date', $i)
                        ->sum('amount');

                    $incomeAmount = Expense::where('type', 'income')
                        ->whereYear('expense_date', $currentYear)
                        ->whereMonth('expense_date', $i)
                        ->sum('amount');
                }

                $monthlyExpenses[] = $expenseAmount;
                $monthlyIncome[] = $incomeAmount;
            }

            // Get expenses by category
            $expensesByCategory = Expense::join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
                ->whereNotNull('expenses.category_id')
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
        } else {
            // Initialize empty data for non-authorized users
            for ($i = 1; $i <= 12; $i++) {
                $month = Carbon::create($currentYear, $i, 1);
                $monthlyLabels[] = $month->format('M');
                $monthlyExpenses[] = 0;
            }
        }

        return view('index', compact(
            'totalExpenses',
            'monthlyExpenses',
            'monthlyLabels',
            'expensesByCategory',
            'totalIncome',
            'printingCosts',
            'totalCustomers',
            'unpaidDebts',
            'paidDebts',
            'currentBalance',
            'monthlyIncome'
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
