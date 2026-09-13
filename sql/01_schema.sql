-- ============================================================================
-- NexaCRM: Relational Schema (MySQL / MariaDB - XAMPP Compatible)
-- Course: CSE311 Database Management Systems
-- Focus: Academic Rigor, 3NF Normalization, Referential Integrity
-- ============================================================================

DROP DATABASE IF EXISTS nexacrm_db;
CREATE DATABASE nexacrm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexacrm_db;

-- ----------------------------------------------------------------------------
-- 1. Users (Sales Reps & Managers)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('manager', 'sales_rep') NOT NULL DEFAULT 'sales_rep',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 2. Companies (Client Accounts / Organizations)
-- ----------------------------------------------------------------------------
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    industry VARCHAR(80) NOT NULL,
    website VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    annual_revenue DECIMAL(15, 2) DEFAULT 0.00 CHECK (annual_revenue >= 0),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 3. Contacts (Individual People working at Companies)
-- Cardinality: One Company has Many Contacts (1:N)
-- ----------------------------------------------------------------------------
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    job_title VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 4. Deals (Sales Opportunities)
-- Cardinality: One Company has Many Deals (1:N)
-- ----------------------------------------------------------------------------
CREATE TABLE deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    primary_contact_id INT NULL,
    assigned_user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    value DECIMAL(12, 2) NOT NULL DEFAULT 0.00 CHECK (value >= 0),
    stage ENUM('lead', 'contacted', 'proposal', 'negotiation', 'closed_won', 'closed_lost') NOT NULL DEFAULT 'lead',
    expected_close_date DATE NULL,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (primary_contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 5. Deal Stage History (Audit Trail)
-- Logs every stage transition (e.g. Lead -> Negotiation -> Closed Won)
-- ----------------------------------------------------------------------------
CREATE TABLE deal_stage_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL,
    from_stage VARCHAR(30) NULL,
    to_stage VARCHAR(30) NOT NULL,
    changed_by_user_id INT NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 6. Activities (Timeline: Calls, Meetings, Notes)
-- ----------------------------------------------------------------------------
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NULL,
    contact_id INT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('call', 'meeting', 'email', 'note') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    notes TEXT NOT NULL,
    activity_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 7. Invoices (Billing for Won Deals)
-- Cardinality: Exactly ONE Invoice per Won Deal (1:1 enforced by UNIQUE)
-- ----------------------------------------------------------------------------
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL UNIQUE, -- Enforces 1:1 relationship with Deal!
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(12, 2) NOT NULL CHECK (subtotal >= 0),
    tax_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00 CHECK (tax_amount >= 0),
    total_amount DECIMAL(12, 2) NOT NULL CHECK (total_amount >= 0),
    status ENUM('unpaid', 'paid', 'cancelled') NOT NULL DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
