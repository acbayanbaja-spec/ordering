# PawBowl — Pet Food Ordering System

PHP + MySQL (XAMPP) app with three role-based dashboards: **Admin**, **Cashier**, and **Customer**.

## Folder structure

```
ordering system/
  config/db.php              Database connection + base URL
  includes/auth.php          Sessions, login, role checks
  includes/helpers.php       CSRF, queries, order transactions
  includes/layout.php        Shared sidebar layout
  assets/css/style.css
  assets/js/app.js
  sql/schema.sql             CREATE TABLE + sample pet-food data
  install.php                One-click database installer
  index.php                  Staff / customer login
  register.php               Customer registration
  logout.php
  admin/                     Dashboard, CRUD, orders, reports
  cashier/                   POS, orders, receipt, stock list
  customer/                  Shop, cart, checkout, orders, profile
```

## Setup (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open [http://localhost/ordering%20system/install.php](http://localhost/ordering%20system/install.php) and click **Run SQL install**.
   - Or import `sql/schema.sql` in phpMyAdmin.
3. Open [http://localhost/ordering%20system/](http://localhost/ordering%20system/).
4. If your MySQL root password is not empty, edit `config/db.php` and the credentials at the top of `install.php`.

Default DB name: `petfood_ordering` · user: `root` · password: *(empty)*.

## Default logins

Staff accounts are seeded. **Customers are NOT pre-seeded** — they must
[self-register](http://localhost/ordering%20system/register.php) or be added by a
cashier at the POS (walk-in customer).

| Role | Username | Password |
|------|----------|----------|
| Admin | Employee ID **1** (James Rivera) | `Admin@123` |
| Cashier | Employee ID **2** (Maria Santos) | `Cashier@123` |

Inactive employee **10** (Sofia Lim) uses `Cashier@123` and is blocked from login.

Staff sign in with **Employee ID**. Customers sign in with **email**.
Job title **Manager** or **IT** maps to Admin. **Sales Rep** maps to Cashier.

## Business rules

- `TotalAmount` = sum of `UnitPrice * Quantity` on `OrderDetails` (price frozen at purchase).
- Stock decreases inside a **database transaction** when an order is created.
- Stock is restored when status changes from Completed → Cancelled or Returned.
- Customers cannot open admin/cashier pages; cashiers cannot open admin pages.
- Inactive employees cannot log in.
- Quantity cannot exceed available stock.

Online customer checkout sets `EmployeeID` to `NULL` and `Status` to `Completed` (the schema only allows Completed / Cancelled / Returned).

## ER relationships

```
Departments 1──* Employees
Employees    1──* Employees (ManagerID self-reference, nullable)
Customers    1──* Orders
Employees    1──* Orders (nullable cashier)
Categories   1──* Products
Orders       1──* OrderDetails
Products     1──* OrderDetails
```

Columns match the assignment. Added only what login needs:

- `Employees.Password`, `Employees.Role` (`Admin` / `Cashier`)
- `Customers.Password`
- `OrderDetails.UnitPrice`

## Reports

Admin → Reports: sales by date range, inventory, employees per department. Export **CSV** or **Print / Save as PDF** (browser print).
