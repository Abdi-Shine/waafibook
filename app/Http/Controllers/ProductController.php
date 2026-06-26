<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Account;
use App\Models\Company;
use App\Models\Branch;
use App\Models\ProductStock;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\AuditLog;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $userBranchId = Auth::user()->getAssignedBranchId();

        $query = Product::query()->with(['category', 'stocks' => function($q) use ($userBranchId) {
            /** @var \Illuminate\Database\Eloquent\Relations\HasMany $q */
            if ($userBranchId) $q->where('branch_id', $userBranchId);
        }, 'stocks.branch']);

        if ($userBranchId) {
            $query->whereHas('stocks', function($q) use ($userBranchId) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('branch_id', $userBranchId);
            });
        }

        if ($request->search) {
            $search = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->withSum(['stocks' => function($q) use ($userBranchId) {
            /** @var \Illuminate\Database\Eloquent\Relations\HasMany $q */
            if ($userBranchId) $q->where('branch_id', $userBranchId);
        }], 'quantity')->latest()->paginate(10);

        $totalProducts = (clone $query)->count();

        // Pulled from the Inventory GL account itself (same source the Balance
        // Sheet/Trial Balance use) rather than recalculated as qty * today's
        // purchase_price. A product's purchase_price reflects the cost of its
        // *latest* purchase, so once a product has been bought at more than
        // one price, qty * current price no longer matches what's actually
        // on the books for the units still on hand. Reading the ledger
        // directly keeps this card always consistent with the Chart of
        // Accounts. Not branch-scoped — the ledger doesn't track inventory
        // value per branch.
        $inventoryAccount = Account::query()->where('code', '1150')->first()
            ?: Account::query()->where('type', 'inventory')->first()
            ?: Account::query()->where('name', 'like', '%Inventory%')->first();
        $totalStockValue = $inventoryAccount
            ? JournalItem::query()->where('account_id', $inventoryAccount->id)
                ->selectRaw('SUM(debit) - SUM(credit) as balance')->value('balance') ?? 0
            : 0;

        $lowStockItems = DB::table('products')
            ->when($userBranchId, fn($q) => $q->join('product_stocks', fn($j) => $j->on('products.id', '=', 'product_stocks.product_id')->where('product_stocks.branch_id', $userBranchId)),
                                  fn($q) => $q->leftJoin('product_stocks', 'products.id', '=', 'product_stocks.product_id'))
            ->select('products.id', 'products.low_stock_threshold')
            ->groupBy('products.id', 'products.low_stock_threshold')
            ->havingRaw('COALESCE(SUM(product_stocks.quantity), 0) <= products.low_stock_threshold')
            ->get()->count();

        $outOfStock = DB::table('products')
            ->when($userBranchId, fn($q) => $q->join('product_stocks', fn($j) => $j->on('products.id', '=', 'product_stocks.product_id')->where('product_stocks.branch_id', $userBranchId)),
                                  fn($q) => $q->leftJoin('product_stocks', 'products.id', '=', 'product_stocks.product_id'))
            ->select('products.id')
            ->groupBy('products.id')
            ->havingRaw('COALESCE(SUM(product_stocks.quantity), 0) <= 0')
            ->get()->count();

        $categories = Category::query()->orderBy('name')->get();
        $brands     = Brand::query()->orderBy('name')->get();
        $units      = Unit::query()->orderBy('name')->get();
        $accounts   = Account::query()->where('is_active', 1)->orderBy('code')->get();
        $company    = Company::find(auth()->user()->company_id);
        $branches   = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->orderBy('name')->get();

        return view('frontend.product.add_product', compact(
            'products', 'totalProducts', 'totalStockValue', 'lowStockItems',
            'outOfStock', 'categories', 'brands', 'units', 'company', 'branches'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name'  => [
                'required', 'string', 'max:255',
                Rule::unique('products')->where('company_id', Auth::user()->company_id),
            ],
            'product_code'  => 'nullable|string|unique:products,product_code',
            'category_id'   => 'nullable|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'purchase_price'=> 'nullable|numeric|min:0',
            'stock_products'=> 'nullable|numeric|min:0',
            'branch_id'     => 'nullable|exists:branches,id',
            'product_type'  => 'required|in:product,service',
        ], [
            'product_name.unique' => 'A product with this name already exists.',
        ]);

        try {
            return DB::transaction(function () use ($request) {
            $data = $request->all();

            // fields not in products table
            $stockQuantity = $data['stock_products'] ?? 0;
            $branchId      = $data['branch_id'] ?? null;

            // Fall back to the company's first branch if none selected
            if (!$branchId) {
                $branchId = Branch::where('company_id', Auth::user()->company_id)->value('id');
            }

            unset($data['stock_products'], $data['location_type'], $data['branch_id'], $data['store_id'], $data['status'], $data['brand_id'], $data['secondary_unit'], $data['account_type']);
            
            $data['created_by'] = Auth::id();
            $data['company_id'] = Auth::user()->company_id;

            if ($request->file('image')) {
                $image = $request->file('image');
                $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/products'), $name_gen);
                $save_url = 'uploads/products/' . $name_gen;
                $data['image'] = $save_url;
            }

            $product = Product::query()->create($data);

            // Create initial stock record
            if ($stockQuantity > 0) {
                ProductStock::query()->create([
                    'product_id' => $product->id,
                    'branch_id'  => $branchId,
                    'quantity'   => $stockQuantity,
                    'company_id' => Auth::user()->company_id,
                ]);
            }

            // Accounting - Journal Entry for Initial Stock
            if ($stockQuantity > 0 && ($product->purchase_price ?? 0) > 0) {
                $this->createInitialInventoryEntry($product, $stockQuantity);
            } elseif ($stockQuantity > 0) {
                session()->flash('warning', 'Product created with stock, but no journal entry was recorded because purchase price is $0.');
            }

            AuditLog::log('Products', "Created new product: {$product->product_name}", 'CREATE');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'product' => $product]);
            }

            return redirect()->back()->with('success', 'Product added and inventory recorded.');
            });
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Product could not be saved. Please try again.'], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Product could not be saved. Please try again.');
        }
    }

    public function quickStore(Request $request)
    {
        $request->validate([
            'product_name'  => [
                'required', 'string', 'max:255',
                Rule::unique('products')->where('company_id', Auth::user()->company_id),
            ],
            'category_id'   => 'required|exists:categories,id',
            'selling_price' => 'nullable|numeric|min:0',
        ], [
            'product_name.unique' => 'A product with this name already exists.',
        ]);

        $product = Product::query()->create([
            'product_name'   => $request->product_name,
            'category_id'    => $request->category_id,
            'selling_price'  => $request->selling_price ?? 0,
            'purchase_price' => 0,
            'unit'           => 'Piece',
            'created_by'     => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->product_name,
                'code' => $product->product_code,
                'selling_price' => (float)$product->selling_price,
                'purchase_price' => (float)$product->purchase_price,
                'category_id' => $product->category_id,
                'unit' => $product->unit,
                'stock' => 0
            ]
        ]);
    }

    private function createInitialInventoryEntry($product, $stockQuantity)
    {
        $totalValue = $stockQuantity * $product->purchase_price;
        
        $companyId = Auth::user()->company_id;

        // 1. Auto-resolve Inventory Account (1150 per COA seeder)
        $inventoryAccount = Account::query()->where('company_id', $companyId)->where('code', '1150')->first()
                         ?: Account::query()->where('company_id', $companyId)->where('type', 'inventory')->first()
                         ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Inventory%')->first();

        // 2. Auto-resolve Opening Balance Equity (3300 per COA seeder; 3200 = Retained Earnings)
        /** @var Account|null $equityAccount */
        $equityAccount = Account::query()->where('company_id', $companyId)->where('code', '3300')->first()
                      ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Opening Balance%')->first();

        if (!$inventoryAccount || !$equityAccount) {
            $missing = [];
            if (!$inventoryAccount) $missing[] = 'Inventory Asset account (code 1400 or 1140)';
            if (!$equityAccount)    $missing[] = 'Opening Balance Equity account (code 3200)';
            throw new \RuntimeException(
                'Opening stock journal entry could not be created. Missing: ' . implode(', ', $missing) . '. Please set up these accounts in the Chart of Accounts first.'
            );
        }

        if ($inventoryAccount && $equityAccount) {
            $entry = JournalEntry::query()->create([
                'entry_number' => 'JE-INV-' . date('Ymd') . '-' . str_pad($product->id, 5, '0', STR_PAD_LEFT),
                'date' => now()->toDateString(),
                'reference' => $product->product_code,
                'description' => 'Initial stock for ' . $product->product_name,
                'status' => 'posted',
                'total_amount' => $totalValue,
                'created_by' => Auth::id(),
                'company_id' => Auth::user()->company_id,
            ]);

            // 3. Create Journal Items
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit' => $totalValue,
                'credit' => 0,
                'description' => 'Opening stock recorded for ' . $product->product_name,
                'company_id' => Auth::user()->company_id,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $equityAccount->id,
                'debit' => 0,
                'credit' => $totalValue,
                'description' => 'Opening balance recorded',
                'company_id' => Auth::user()->company_id,
            ]);
        } else {
            Log::error("Accounting failed for Product {$product->id}: Inventory or Equity account missing.");
        }
    }

    public function update(Request $request, $id)
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);
        
        $request->validate([
            'product_name'  => [
                'required', 'string', 'max:255',
                Rule::unique('products')->where('company_id', Auth::user()->company_id)->ignore($id),
            ],
            'product_code'  => 'required|string|unique:products,product_code,' . $id,
            'category_id'   => 'nullable|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'branch_id'    => 'nullable|exists:branches,id',
            'product_type' => 'required|in:product,service',
        ], [
            'product_name.unique' => 'A product with this name already exists.',
        ]);

        $branchId = $request->input('branch_id') ?: null;

        $data = $request->all();
        unset($data['stock_products'], $data['location_type'], $data['branch_id'], $data['store_id'], $data['status'], $data['brand_id'], $data['secondary_unit'], $data['account_type']);

        if ($request->file('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            $image = $request->file('image');
            $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $name_gen);
            $save_url = 'uploads/products/' . $name_gen;
            $data['image'] = $save_url;
        }

        $product->update($data);

        // Update primary stock record (or create one if missing). Quantity is
        // only touched when the form actually sent one, so editing a product
        // without changing its stock count doesn't accidentally zero it out.
        $stockRecord = ProductStock::query()->where('product_id', $product->id)->first();
        $stockUpdate = ['branch_id' => $branchId];
        if ($request->filled('stock_products')) {
            $stockUpdate['quantity'] = $request->input('stock_products');
        }
        if ($stockRecord) {
            $stockRecord->update($stockUpdate);
        } else {
            ProductStock::query()->create(array_merge([
                'product_id' => $product->id,
                'quantity'   => 0,
            ], $stockUpdate));
        }

        AuditLog::log('Products', "Updated product details: {$product->product_name}", 'UPDATE');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'product' => $product]);
        }

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);

        // product_stocks cascade-deletes with the product, so its stock value
        // has to be read and reversed in the books *before* that happens —
        // otherwise the original "opening stock" debit to Inventory stays on
        // the books forever with nothing left to account for it, permanently
        // inflating Inventory by however much stock this product was
        // carrying at the time it was deleted.
        $stockValue = ProductStock::query()->where('product_id', $product->id)->sum('quantity') * $product->purchase_price;

        try {
            DB::transaction(function () use ($product, $stockValue) {
                if ($stockValue != 0) {
                    $this->reverseInventoryValue($product, $stockValue);
                }

                if ($product->image && file_exists(public_path($product->image))) {
                    unlink(public_path($product->image));
                }
                $product->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()->with('error', 'This product can\'t be deleted because it\'s still referenced by other records.');
        }

        AuditLog::log('Products', "Deleted product: {$product->product_name}", 'DELETE', 'warning');

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    // Reverses whatever stock value this product was still carrying on the
    // books when it's deleted — Cr Inventory / Dr Opening Balance Equity,
    // the mirror image of createInitialInventoryEntry's Dr Inventory / Cr
    // Equity. Doesn't touch/delete the original entries (there could be
    // several, from opening stock plus any purchase bills); just nets the
    // remaining value back out in one entry.
    private function reverseInventoryValue(Product $product, float $stockValue): void
    {
        $companyId = Auth::user()->company_id;

        $inventoryAccount = Account::query()->where('company_id', $companyId)->where('code', '1150')->first()
                         ?: Account::query()->where('company_id', $companyId)->where('type', 'inventory')->first()
                         ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Inventory%')->first();
        $equityAccount = Account::query()->where('company_id', $companyId)->where('code', '3300')->first()
                      ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Opening Balance%')->first();

        if (!$inventoryAccount || !$equityAccount) {
            return;
        }

        $entry = JournalEntry::query()->create([
            'entry_number' => 'JE-INV-DEL-' . date('Ymd') . '-' . $product->id . '-' . microtime(true),
            'date'         => now()->toDateString(),
            'reference'    => $product->product_code,
            'description'  => 'Inventory value removed on deletion of ' . $product->product_name,
            'status'       => 'posted',
            'total_amount' => abs($stockValue),
            'created_by'   => Auth::id(),
            'company_id'   => $companyId,
        ]);
        $entry->update(['entry_number' => 'JE-INV-DEL-' . date('Ymd') . '-' . str_pad($entry->id, 6, '0', STR_PAD_LEFT)]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $equityAccount->id,
            'company_id'       => $companyId,
            'description'      => 'Inventory removed: ' . $product->product_name,
            'debit'            => $stockValue > 0 ? $stockValue : 0,
            'credit'           => $stockValue < 0 ? abs($stockValue) : 0,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $inventoryAccount->id,
            'company_id'       => $companyId,
            'description'      => 'Inventory removed: ' . $product->product_name,
            'debit'            => $stockValue < 0 ? abs($stockValue) : 0,
            'credit'           => $stockValue > 0 ? $stockValue : 0,
        ]);
    }

    public function ledgerView(Request $request)
    {
        $userBranchId = Auth::user()->getAssignedBranchId();

        $products = Product::query()
            ->withSum(['stocks' => function($q) use ($userBranchId) {
                if ($userBranchId) $q->where('branch_id', $userBranchId);
            }], 'quantity')
            ->orderBy('product_name')
            ->get();

        $selectedId = $request->input('product_id', $products->first()->id ?? null);
        $ledger = $selectedId ? $this->buildLedgerData($selectedId) : null;

        return view('frontend.product.ledger', compact('products', 'selectedId', 'ledger'));
    }

    public function ledgerData($id)
    {
        return response()->json($this->buildLedgerData($id));
    }

    private function buildLedgerData($id)
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);
        $userBranchId = Auth::user()->getAssignedBranchId();

        $statusLabels = ['paid' => 'Paid', 'partial' => 'Partial', 'pending' => 'Unpaid', 'completed' => 'Paid'];

        $sales = \App\Models\SalesOrderItem::query()
            ->where('product_id', $id)
            ->with(['order.customer'])
            ->whereHas('order', function($q) use ($userBranchId) {
                if ($userBranchId) $q->where('branch_id', $userBranchId);
            })
            ->get()
            ->map(function($item) use ($statusLabels) {
                $order = $item->order;
                return [
                    'type'       => 'Sale',
                    'type_color' => 'bg-emerald-500',
                    'ref'        => $order->id ?? null,
                    'name'       => $order->customer->name ?? 'Walk-in Customer',
                    'date'       => $order->invoice_date ? $order->invoice_date->format('d/m/Y') : '-',
                    'sort_date'  => $order->invoice_date ? $order->invoice_date->timestamp : 0,
                    'quantity'   => $item->quantity,
                    'price'      => $item->unit_price,
                    'status'     => $statusLabels[$order->status ?? ''] ?? ucfirst($order->status ?? ''),
                ];
            });

        $purchases = \App\Models\PurchaseBillItem::query()
            ->where('product_id', $id)
            ->with(['bill.supplier'])
            ->whereHas('bill', function($q) use ($userBranchId) {
                if ($userBranchId) $q->where('branch_id', $userBranchId);
            })
            ->get()
            ->map(function($item) use ($statusLabels) {
                $bill = $item->bill;
                $billDate = $bill->bill_date ? \Illuminate\Support\Carbon::parse($bill->bill_date) : null;
                return [
                    'type'       => 'Purchase Order',
                    'type_color' => 'bg-orange-500',
                    'ref'        => $bill->id ?? null,
                    'name'       => $bill->supplier->name ?? '-',
                    'date'       => $billDate ? $billDate->format('d/m/Y') : '-',
                    'sort_date'  => $billDate ? $billDate->timestamp : 0,
                    'quantity'   => $item->quantity,
                    'price'      => $item->unit_price,
                    'status'     => $statusLabels[$bill->status ?? ''] ?? ucfirst($bill->status ?? ''),
                ];
            });

        $openingEntries = JournalEntry::query()
            ->where('reference', $product->product_code)
            ->where('description', 'like', 'Initial stock for%')
            ->get()
            ->map(function($entry) use ($product) {
                return [
                    'type'       => 'Opening Stock',
                    'type_color' => 'bg-gray-800',
                    'ref'        => null,
                    'name'       => 'Opening Stock',
                    'date'       => $entry->date ? $entry->date->format('d/m/Y') : '-',
                    'sort_date'  => $entry->date ? $entry->date->timestamp : 0,
                    'quantity'   => $product->purchase_price > 0 ? round($entry->total_amount / $product->purchase_price, 2) : 0,
                    'price'      => (float) $product->purchase_price,
                    'status'     => ucfirst($entry->status ?? 'Posted'),
                ];
            });

        $transactions = $sales->concat($purchases)->concat($openingEntries)
            ->sortByDesc('sort_date')
            ->values();

        $stockQuantity = (float) $product->stocks()
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->sum('quantity');

        return [
            'product' => [
                'id'             => $product->id,
                'name'           => $product->product_name,
                'unit'           => $product->unit ?? 'pcs',
                'selling_price'  => (float) $product->selling_price,
                'purchase_price' => (float) $product->purchase_price,
                'stock_quantity' => $stockQuantity,
                'stock_value'    => $stockQuantity * (float) $product->purchase_price,
            ],
            'transactions' => $transactions,
        ];
    }

    public function updateStatus(Request $request, $id)
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($id);
        $product->status = $request->status;
        $product->save();

        return response()->json(['success' => true]);
    }

    public function export()
    {
        $fileName = 'products_export_' . date('Y-m-d_H:i:s') . '.csv';
        // stock_products isn't a real product attribute (see import()'s
        // comment) — the actual stock lives on ProductStock, summed here
        // across branches, same as the product list page.
        $products = Product::query()->with('category')->withSum('stocks', 'quantity')->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Product Name', 'Product Code', 'Category', 'Unit', 'Purchase Price', 'Selling Price', 'Stock', 'Description');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $product) {
                fputcsv($file, array(
                    $product->product_name,
                    $product->product_code,
                    $product->category->name ?? 'General',
                    $product->unit ?? 'Piece',
                    $product->purchase_price,
                    $product->selling_price,
                    $product->stocks_sum_quantity ?? 0,
                    $product->description ?? ''
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:10240',
        ]);

        $file = $request->file('csv_file');
        
        try {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            
            // Handle UTF-8 BOM
            if (strpos($content, "\xEF\xBB\xBF") === 0) {
                $content = substr($content, 3);
            }

            $lines = explode("\n", str_replace("\r", "", $content));
            if (empty($lines)) {
                return redirect()->back()->with('error', 'The file is empty.');
            }

            // Detect delimiter from header
            $headerLine = $lines[0];
            $delimiter = (strpos($headerLine, ';') !== false) ? ';' : ',';
            
            // Remove header
            array_shift($lines);

            $importedCount = 0;
            $errors = [];
            $rowNum = 1;

            DB::beginTransaction();
            
            foreach ($lines as $line) {
                $rowNum++;
                $data = str_getcsv($line, $delimiter);
                
                if (empty($data) || !isset($data[0]) || empty(trim($data[0]))) continue;

                try {
                    $name = trim($data[0]);
                    $code = trim($data[1] ?? '');
                    
                    if (!empty($code) && Product::query()->where('product_code', $code)->exists()) {
                        throw new \Exception("Code '{$code}' already exists.");
                    }

                    $categoryName = trim($data[2] ?? 'General');
                    $unit = trim($data[3] ?? 'Piece');
                    
                    $cleanPurchase = preg_replace('/[^-0-9.]/', '', $data[4] ?? '0');
                    $cleanSelling = preg_replace('/[^-0-9.]/', '', $data[5] ?? '0');
                    $cleanStock = preg_replace('/[^0-9]/', '', $data[6] ?? '0');

                    $category = Category::query()->firstOrCreate(['name' => $categoryName]);
                    $stockQty = (int) $cleanStock;

                    // stock_products isn't a real column on products — it's a
                    // transient form field that store()/update() translate
                    // into a separate ProductStock row. Passing it straight
                    // into Product::create() (as this previously did) throws
                    // "Unknown column 'stock_products'" since the column
                    // doesn't exist on this table.
                    // 'status' also isn't a column on products (same class of
                    // bug as stock_products above — it doesn't exist on this
                    // table at all).
                    $product = Product::query()->create([
                        'product_name' => $name,
                        'product_code' => $code,
                        'category_id' => $category->id,
                        'unit' => $unit,
                        'purchase_price' => (float)$cleanPurchase,
                        'selling_price' => (float)$cleanSelling,
                        'description' => trim($data[7] ?? ''),
                    ]);

                    if ($stockQty > 0) {
                        $branchId = Branch::query()->where('company_id', Auth::user()->company_id)->value('id');
                        ProductStock::query()->create([
                            'product_id' => $product->id,
                            'branch_id'  => $branchId,
                            'quantity'   => $stockQty,
                        ]);

                        if ($product->purchase_price > 0) {
                            $this->createInitialInventoryEntry($product, $stockQty);
                        }
                    }

                    $importedCount++;
                } catch (\Exception $rowException) {
                    $errors[] = "Row {$rowNum}: " . $rowException->getMessage();
                }
            }

            if ($importedCount == 0 && !empty($errors)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Import failed: ' . $errors[0]);
            }

            DB::commit();
            
            $msg = "Successfully imported {$importedCount} products.";
            if (!empty($errors)) {
                $msg .= " Note: " . count($errors) . " rows were skipped due to errors.";
            }
            
            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $fileName = 'sample_products_template.csv';
        $columns = array('Name', 'Code', 'Category', 'Unit', 'Purchase Price', 'Selling Price', 'Stock', 'Description');

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Add a sample row
            fputcsv($file, array('Example Product', 'PRD-001', 'Electronics', 'Piece', '100.00', '150.00', '10', 'Sample description'));
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStockDetails($id)
    {
        /** @var Product|null $product */
        $product = Product::query()->with(['stocks.branch'])->find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate total stock
        $totalStock = $product->stocks->sum('quantity');

        // Prepare stock details for response
        $stockDetails = $product->stocks->map(function ($stock) {
            return [
                'branch_name' => $stock->branch->name ?? 'N/A',
                'quantity'    => $stock->quantity,
            ];
        });

        return response()->json([
            'product_name' => $product->product_name,
            'product_code' => $product->product_code,
            'total_stock'  => $totalStock,
            'details'      => $stockDetails,
        ]);
    }

    public function lowStockView(\Illuminate\Http\Request $request)
    {
        $query = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->leftJoin('branches', 'product_stocks.branch_id', '=', 'branches.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.product_name',
                'products.product_code',
                'product_stocks.quantity',
                'branches.name as branch_name',
                'product_stocks.branch_id',
                'products.id as product_id',
                'products.low_stock_threshold',
                'categories.name as category_name'
            )
            ->where('products.company_id', auth()->user()->company_id)
            ->whereColumn('product_stocks.quantity', '<=', 'products.low_stock_threshold');

        if ($request->branch_id) {
            $query->where('product_stocks.branch_id', $request->branch_id);
        }

        if ($request->category_id) {
            $query->where('products.category_id', $request->category_id);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('products.product_name', 'like', '%' . $search . '%')
                  ->orWhere('products.product_code', 'like', '%' . $search . '%');
            });
        }

        if ($request->threat_level) {
            if ($request->threat_level === 'critical') {
                $query->where('product_stocks.quantity', '<=', 5);
            } else {
                $query->where('product_stocks.quantity', '>', 5);
            }
        }

        // Global stats for the cards (ignoring pagination)
        $statsQuery = clone $query;
        $allAlerts = $statsQuery->get();
        
        $stats = [
            'total'    => $allAlerts->count(),
            'critical' => $allAlerts->where('quantity', '<=', 5)->count(),
            'warning'  => $allAlerts->where('quantity', '>', 5)->count(),
            'branches' => $allAlerts->pluck('branch_id')->unique()->count()
        ];

        $lowStockProducts = $query->paginate(10);

        $branches = \App\Models\Branch::all();
        $categories = \App\Models\Category::all();

        return view('frontend.product.Low_alerts', compact('lowStockProducts', 'branches', 'categories', 'stats'));
    }

    public function stockSummaryView(Request $request)
    {
        $userBranchId = Auth::user()->getAssignedBranchId();

        $query = Product::query()->with(['category', 'stocks' => function($q) use ($userBranchId) {
            /** @var \Illuminate\Database\Eloquent\Relations\HasMany $q */
            if ($userBranchId) $q->where('branch_id', $userBranchId);
        }]);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $search = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        // Fetch received and issued quantities from StockMovement
        $movements = DB::table('stock_movements')
            ->select('product_id',
                DB::raw('SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as received'),
                DB::raw('SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as issued')
            )
            ->where('company_id', auth()->user()->company_id)
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $products = $query->get()->map(function(Product $product) use ($userBranchId, $movements) {
            $mv = $movements->get($product->id);
            $product->received_qty = $mv ? (float)$mv->received : 0;
            $product->issued_qty = $mv ? (float)$mv->issued : 0;
            $product->closing_qty = (float)$product->stocks->sum('quantity');
            $product->opening_qty = $product->closing_qty - $product->received_qty + $product->issued_qty;
            $product->total_value = $product->closing_qty * $product->purchase_price;
            
            if ($product->closing_qty <= 0) {
                $product->status = 'Out of Stock';
                $product->status_class = 'out-stock';
            } elseif ($product->closing_qty <= $product->low_stock_threshold) {
                $product->status = 'Low Stock';
                $product->status_class = 'low-stock';
            } else {
                $product->status = 'In Stock';
                $product->status_class = 'in-stock';
            }
            
            return $product;
        });

        // Store aggregate stats BEFORE status filter is applied
        $totalItems    = $products->count();
        $totalValue    = $products->sum('total_value');
        $inStockData   = $products->where('status', 'In Stock')->count();
        $lowStockData  = $products->where('status', 'Low Stock')->count();
        $outOfStockData = $products->where('status', 'Out of Stock')->count();

        // Apply status filter (post-map, on the collection)
        if ($request->status) {
            $statusFilter = strtolower($request->status);
            $products = $products->filter(function($product) use ($statusFilter) {
                return strtolower($product->status) === $statusFilter;
            })->values();
        }

        $categories = Category::query()->get();
        $branches = Branch::query()->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currency = $company->currency ?? 'SAR';

        return view('frontend.product.stock_summary', [
            'products' => $products,
            'totalItems' => $totalItems,
            'totalValue' => $totalValue,
            'inStock' => $inStockData,
            'lowStock' => $lowStockData,
            'outOfStock' => $outOfStockData,
            'categories' => $categories,
            'branches' => $branches,
            'currency' => $currency,
            'company' => $company
        ]);
    }

    public function stockAdjustmentView(Request $request)
    {
        $query = ProductStock::query()->with(['product', 'branch'])
            ->whereHas('product')
            ->latest();

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        // Stats should be calculated on all matching records, not just paged ones
        $allMatching = $query->get();
        $stats = [
            'sku_inventory' => $allMatching->count(),
            'aggregate_units' => $allMatching->sum('quantity'),
            'critical_stocks' => $allMatching->where('quantity', '<=', 10)->count(),
            'active_storage' => $allMatching->pluck('branch_id')->unique()->filter()->count()
        ];

        $stocks = $query->paginate(10)->withQueryString();
        $products = Product::query()->get();
        $branches = Branch::query()->get();

        return view('frontend.product.stock_adjustment', compact('stocks', 'products', 'branches', 'stats'));
    }

    public function stockAdjustmentStore(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id'  => 'required|exists:branches,id',
            'method'     => 'required|in:addition,deduction,physical',
            'quantity'   => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Scoped to the specific branch being adjusted — matching only on
            // product_id (as before) silently read/wrote an arbitrary branch's
            // row on any multi-branch product, corrupting whichever branch
            // happened to be picked instead of the one actually being counted.
            $stock = ProductStock::query()->where('product_id', $request->product_id)
                ->where('branch_id', $request->branch_id)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = ProductStock::query()->create([
                    'product_id' => $request->product_id,
                    'branch_id'  => $request->branch_id,
                    'quantity'   => 0,
                ]);
            }

            $before = (float) $stock->quantity;

            if ($request->input('method') === 'addition') {
                $stock->increment('quantity', $request->quantity);
            } elseif ($request->input('method') === 'deduction') {
                $stock->decrement('quantity', $request->quantity);
            } else { // physical reset
                $stock->update(['quantity' => $request->quantity]);
            }

            $stock->refresh();

            // Audit trail — without this, a stock adjustment was invisible to
            // any future reconciliation: it changed the number with no record
            // of who/when/why/by-how-much, indistinguishable from a real
            // purchase/sale movement.
            StockMovement::query()->create([
                'product_id'     => $request->product_id,
                'branch_id'      => $request->branch_id,
                'quantity'       => $stock->quantity - $before,
                'type'           => 'adjustment',
                'reference_id'   => null,
                'reference_type' => null,
                'balance_after'  => $stock->quantity,
                'created_by'     => Auth::id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Stock level reconciled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Reconciliation Failed: ' . $e->getMessage());
        }
    }
}
