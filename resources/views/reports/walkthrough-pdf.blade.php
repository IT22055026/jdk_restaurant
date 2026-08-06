<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expenses Module - Implementation Walkthrough</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.6;
            font-size: 13px;
            padding: 20px;
        }
        h1 {
            font-size: 22px;
            color: #0f172a;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        h2 {
            font-size: 16px;
            color: #1e40af;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        h3 {
            font-size: 14px;
            color: #334155;
            margin-top: 15px;
            margin-bottom: 6px;
        }
        p, li {
            color: #334155;
        }
        ul {
            margin-top: 5px;
            padding-left: 20px;
        }
        code {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 2px 5px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
        }
        pre {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .table-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .table-box th {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px;
            font-size: 11px;
            text-align: left;
        }
        .table-box td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            font-size: 11px;
        }
        .badge {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .formula-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <h1>Complete Educational Walkthrough: Restaurant Expenses Module</h1>
    <p><strong>Project:</strong> Restaurant POS System (JDK Restaurant)</p>
    <p><strong>Generated Date:</strong> {{ date('d M Y, H:i') }}</p>

    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;">

    <h2>1. System Architecture & Data Flow</h2>
    <p>The Expenses module follows standard Laravel MVC conventions:</p>
    <ul>
        <li><strong>User Interaction:</strong> Clicks Expenses menu in left sidebar &rarr; hits <code>GET /expenses</code>.</li>
        <li><strong>Controller Layer:</strong> <code>ExpenseController@index</code> queries paginated expenses and calculates KPI totals.</li>
        <li><strong>Model Layer:</strong> <code>Expense</code> model queries MySQL database using Eloquent ORM.</li>
        <li><strong>Financial Integration:</strong> <code>ReportsController</code> fetches total expenses and subtracts them from Gross Revenue to calculate Net Profit.</li>
    </ul>

    <h2>2. Database Layer (Migrations & Schema)</h2>
    <h3>Tables Created & Modified</h3>
    <table class="table-box">
        <thead>
            <tr>
                <th>Table Name</th>
                <th>Columns</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>expense_categories</code></td>
                <td><code>id, name, description, is_active, timestamps</code></td>
                <td>Stores operational expense categories (Utilities, Wages, Rent, etc.).</td>
            </tr>
            <tr>
                <td><code>expenses</code></td>
                <td><code>id, expense_category_id, user_id, title, amount, expense_date, notes, timestamps</code></td>
                <td>Stores individual logged expenses linked to categories & users.</td>
            </tr>
            <tr>
                <td><code>modules</code></td>
                <td><code>id, name, icon, route, sort_order</code></td>
                <td>Registers <code>Expense Management</code> with icon <code>receipt</code> and route <code>expenses.index</code>.</td>
            </tr>
        </tbody>
    </table>

    <h2>3. Eloquent Models & Relationships</h2>
    <p>Two Eloquent models were created in <code>app/Models/</code>:</p>
    <ul>
        <li><strong><code>ExpenseCategory.php</code>:</strong> Defines <code>hasMany(Expense::class)</code> relationship.</li>
        <li><strong><code>Expense.php</code>:</strong> Defines <code>belongsTo(ExpenseCategory::class)</code> and <code>belongsTo(User::class)</code> relationships with <code>$fillable</code> array containing <code>expense_category_id</code>, <code>user_id</code>, <code>title</code>, <code>amount</code>, <code>expense_date</code>, and <code>notes</code>.</li>
    </ul>

    <h2>4. Controller Actions & Web Routes</h2>
    <p>Routes registered in <code>routes/web.php</code> guarded by <code>auth</code> and <code>module.access</code> middleware:</p>
    <pre>GET|HEAD   /expenses ................... expenses.index › ExpenseController@index
POST       /expenses ................... expenses.store › ExpenseController@store
GET|HEAD   /expenses/create ............ expenses.create › ExpenseController@create
PUT|PATCH  /expenses/{expense} ......... expenses.update › ExpenseController@update
DELETE     /expenses/{expense} ......... expenses.destroy › ExpenseController@destroy
GET|HEAD   /expenses/{expense}/edit .... expenses.edit › ExpenseController@edit</pre>

    <h2>5. UI Views & Design System Alignment</h2>
    <p>Blade views styled to match Supplier Management UI tokens:</p>
    <ul>
        <li><strong><code>index.blade.php</code>:</strong> KPI summary cards (Total Filtered, This Month, Today), Category & Date filters, Data Table, Empty State & <code>+ Add Expense</code> button.</li>
        <li><strong><code>create.blade.php</code>:</strong> Top round back button (<code>&lt;</code>), full-width rounded card container, 2-column grid layout for Category dropdown, Date picker, Title text input, Amount (LKR), and Additional Notes textarea.</li>
        <li><strong><code>edit.blade.php</code>:</strong> Matching UI form pre-populated with existing record data.</li>
    </ul>

    <h2>6. Profit Deduction Formula</h2>
    <div class="formula-box">
        Net Profit = Gross Revenue - Total Expenses
    </div>
    <p>Calculated in <code>ReportsController.php</code> and rendered on the Dashboard in dedicated metric cards:</p>
    <ul>
        <li><strong>Gross Revenue:</strong> Total completed sales.</li>
        <li><strong>Total Expenses:</strong> Sum of all recorded operational expenses.</li>
        <li><strong>Net Profit:</strong> Gross Revenue minus Total Expenses.</li>
    </ul>

    <h2>7. Implementation Files List</h2>
    <ul>
        <li><code>database/migrations/2026_08_06_093544_create_expense_categories_table.php</code></li>
        <li><code>database/migrations/2026_08_06_094141_create_expenses_table.php</code></li>
        <li><code>database/migrations/2026_08_06_100000_add_expense_management_module.php</code></li>
        <li><code>database/migrations/2026_08_06_110000_drop_payment_method_and_reference_no_from_expenses_table.php</code></li>
        <li><code>app/Models/ExpenseCategory.php</code></li>
        <li><code>app/Models/Expense.php</code></li>
        <li><code>database/seeders/ExpenseCategorySeeder.php</code></li>
        <li><code>app/Http/Controllers/ExpenseController.php</code></li>
        <li><code>routes/web.php</code></li>
        <li><code>resources/views/modules/expenses/index.blade.php</code></li>
        <li><code>resources/views/modules/expenses/create.blade.php</code></li>
        <li><code>resources/views/modules/expenses/edit.blade.php</code></li>
        <li><code>app/Http/Controllers/ReportsController.php</code></li>
        <li><code>resources/views/modules/reports.blade.php</code></li>
    </ul>
</body>
</html>
