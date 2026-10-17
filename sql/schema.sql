-- Pet Food Ordering System
-- MySQL 5.7+ / MariaDB 10.4+
-- Import via phpMyAdmin or: mysql -u root < sql/schema.sql

CREATE DATABASE IF NOT EXISTS petfood_ordering
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE petfood_ordering;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS OrderDetails;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Customers;
DROP TABLE IF EXISTS Employees;
DROP TABLE IF EXISTS Departments;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Departments (
  DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
  DepartmentName VARCHAR(100) NOT NULL,
  Location VARCHAR(120) NOT NULL,
  Budget DECIMAL(12, 2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB;

CREATE TABLE Employees (
  EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
  FirstName VARCHAR(80) NOT NULL,
  LastName VARCHAR(80) NOT NULL,
  DepartmentID INT NOT NULL,
  Salary DECIMAL(10, 2) NOT NULL,
  HireDate DATE NOT NULL,
  JobTitle VARCHAR(80) NOT NULL,
  ManagerID INT NULL,
  Status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  Password VARCHAR(255) NOT NULL,
  Role ENUM('Admin', 'Cashier') NOT NULL,
  CONSTRAINT fk_emp_dept FOREIGN KEY (DepartmentID) REFERENCES Departments(DepartmentID),
  CONSTRAINT fk_emp_manager FOREIGN KEY (ManagerID) REFERENCES Employees(EmployeeID)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE Customers (
  CustomerID INT AUTO_INCREMENT PRIMARY KEY,
  CustomerName VARCHAR(120) NOT NULL,
  City VARCHAR(80) NOT NULL,
  Country VARCHAR(80) NOT NULL,
  Email VARCHAR(150) NOT NULL UNIQUE,
  Password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Categories (
  CategoryID INT AUTO_INCREMENT PRIMARY KEY,
  CategoryName VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Products (
  ProductID INT AUTO_INCREMENT PRIMARY KEY,
  ProductName VARCHAR(160) NOT NULL,
  CategoryID INT NOT NULL,
  Price DECIMAL(10, 2) NOT NULL,
  Stock INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_prod_cat FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID),
  CONSTRAINT chk_price CHECK (Price >= 0),
  CONSTRAINT chk_stock CHECK (Stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE Orders (
  OrderID INT AUTO_INCREMENT PRIMARY KEY,
  CustomerID INT NOT NULL,
  EmployeeID INT NULL,
  OrderDate DATETIME NOT NULL,
  TotalAmount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  Status ENUM('Completed', 'Cancelled', 'Returned') NOT NULL DEFAULT 'Completed',
  PaymentMethod VARCHAR(30) NOT NULL DEFAULT 'Cash',
  CONSTRAINT fk_ord_cust FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID),
  CONSTRAINT fk_ord_emp FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE OrderDetails (
  OrderDetailID INT AUTO_INCREMENT PRIMARY KEY,
  OrderID INT NOT NULL,
  ProductID INT NOT NULL,
  Quantity INT NOT NULL,
  UnitPrice DECIMAL(10, 2) NOT NULL,
  CONSTRAINT fk_od_ord FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
  CONSTRAINT fk_od_prod FOREIGN KEY (ProductID) REFERENCES Products(ProductID),
  CONSTRAINT chk_qty CHECK (Quantity > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Sample data (passwords hashed with password_hash / bcrypt)
-- Admin@123   Cashier@123   Customer@123
-- ---------------------------------------------------------------------------

INSERT INTO Departments (DepartmentID, DepartmentName, Location, Budget) VALUES
  (1, 'Sales', 'Ground Floor - POS Hall', 450000.00),
  (2, 'Engineering', '2nd Floor - IT Room', 320000.00),
  (3, 'HR', '2nd Floor - Admin Wing', 180000.00),
  (4, 'Marketing', '3rd Floor - Studio', 210000.00);

INSERT INTO Employees
  (EmployeeID, FirstName, LastName, DepartmentID, Salary, HireDate, JobTitle, ManagerID, Status, Password, Role)
VALUES
  (1, 'James', 'Rivera', 1, 62000.00, '2019-03-12', 'Manager', NULL, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (2, 'Maria', 'Santos', 1, 28000.00, '2021-06-08', 'Sales Rep', 1, 'Active',
   '$2y$10$4n8mkAEEFOdB0VBtiw.b/.Ey43HlcMWVPCXBcJgRvsVyQv8ViO8CG', 'Cashier'),
  (3, 'David', 'Chen', 2, 58000.00, '2020-01-20', 'IT', NULL, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (4, 'Ana', 'Cruz', 1, 26500.00, '2022-11-02', 'Sales Rep', 1, 'Active',
   '$2y$10$4n8mkAEEFOdB0VBtiw.b/.Ey43HlcMWVPCXBcJgRvsVyQv8ViO8CG', 'Cashier'),
  (5, 'Robert', 'Kim', 3, 51000.00, '2018-09-15', 'Manager', NULL, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (6, 'Lisa', 'Wong', 4, 49500.00, '2019-12-01', 'Manager', NULL, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (7, 'Tom', 'Garcia', 2, 42000.00, '2023-04-18', 'IT', 3, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (8, 'Nina', 'Patel', 3, 36000.00, '2022-02-28', 'Manager', 5, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (9, 'Carlos', 'Reyes', 4, 34000.00, '2024-01-10', 'Manager', 6, 'Active',
   '$2y$10$L2gFDAIzcREOFk7knaVMD.ztqPlE7c9TCwBYf0FvWSnlViB4iyVEu', 'Admin'),
  (10, 'Sofia', 'Lim', 1, 25000.00, '2023-08-21', 'Sales Rep', 1, 'Inactive',
   '$2y$10$4n8mkAEEFOdB0VBtiw.b/.Ey43HlcMWVPCXBcJgRvsVyQv8ViO8CG', 'Cashier');

-- Customers are NOT pre-seeded. They are created through self-registration
-- (register.php) or by a cashier at the POS (walk-in customer).

INSERT INTO Categories (CategoryID, CategoryName) VALUES
  (1, 'Dog Food'),
  (2, 'Cat Food'),
  (3, 'Bird Food'),
  (4, 'Fish Food'),
  (5, 'Pet Treats'),
  (6, 'Pet Supplies');

INSERT INTO Products (ProductID, ProductName, CategoryID, Price, Stock) VALUES
  (1,  'Royal Canin Adult Dog 2kg',        1, 1850.00, 40),
  (2,  'Pedigree Puppy 3kg',               1,  980.00, 25),
  (3,  'Whiskas Tuna 1kg',                 2,  420.00,  8),
  (4,  'Me-O Adult Cat 1.2kg',             2,  310.00,  0),
  (5,  'Kaytee Bird Seed 1kg',             3,  250.00, 30),
  (6,  'Tetra Fish Flakes 200g',           4,  180.00,  5),
  (7,  'Chew Bones 6-pack',                5,  150.00, 50),
  (8,  'Cat Litter 10kg',                  6,  390.00, 12),
  (9,  'Dog Shampoo 500ml',                6,  220.00,  0),
  (10, 'Royal Canin Kitten 2kg',           2, 1650.00, 18),
  (11, 'Pedigree Dentastix 7-pack',        5,  199.00, 60),
  (12, 'Hikari Goldfish Pellets 300g',     4,  275.00, 15),
  (13, 'Bird Calcium Block',               3,   95.00,  7),
  (14, 'Fancy Feast Variety 12-pack',      2,  540.00, 22),
  (15, 'Hill''s Science Diet Dog 3kg',     1, 2100.00,  9);

-- Orders and OrderDetails are NOT pre-seeded because they depend on customers,
-- and customers start empty. They are created at the POS (cashier) or at checkout (customer).

ALTER TABLE Employees AUTO_INCREMENT = 11;
ALTER TABLE Customers AUTO_INCREMENT = 1;
ALTER TABLE Categories AUTO_INCREMENT = 7;
ALTER TABLE Products AUTO_INCREMENT = 16;
ALTER TABLE Orders AUTO_INCREMENT = 1;
ALTER TABLE OrderDetails AUTO_INCREMENT = 1;
ALTER TABLE Departments AUTO_INCREMENT = 5;
