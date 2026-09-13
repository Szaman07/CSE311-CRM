-- ============================================================================
-- NexaCRM: Seed Data
-- Realistic sample records for testing queries, joins, and reports
-- ============================================================================

USE nexacrm_db;

-- 1. Insert Users
INSERT INTO users (id, full_name, email, role) VALUES
(1, 'Sarah Connor', 'sarah.c@nexacrm.io', 'manager'),
(2, 'Alex Chen', 'alex.chen@nexacrm.io', 'sales_rep'),
(3, 'Maria Garcia', 'maria.g@nexacrm.io', 'sales_rep');

-- 2. Insert Companies
INSERT INTO companies (id, name, industry, website, phone, annual_revenue) VALUES
(1, 'FinCorp Global', 'Financial Services', 'https://fincorp.com', '+1-555-0101', 8500000.00),
(2, 'HealthPulse Labs', 'Healthcare SaaS', 'https://healthpulse.io', '+1-555-0202', 3200000.00),
(3, 'Vanguard Logistics', 'Supply Chain', 'https://vanguardlog.com', '+1-555-0303', 12000000.00),
(4, 'NovaTech Solutions', 'Cloud Infrastructure', 'https://novatech.cloud', '+1-555-0404', 1500000.00);

-- 3. Insert Contacts
INSERT INTO contacts (id, company_id, first_name, last_name, email, phone, job_title) VALUES
(1, 1, 'David', 'Sterling', 'david.s@fincorp.com', '+1-555-1011', 'Chief Technology Officer'),
(2, 1, 'Rachel', 'Adams', 'rachel.a@fincorp.com', '+1-555-1012', 'VP Procurement'),
(3, 2, 'Emily', 'Vance', 'emily.v@healthpulse.io', '+1-555-1021', 'Director of Clinical Ops'),
(4, 3, 'Marcus', 'Briggs', 'mbriggs@vanguardlog.com', '+1-555-1031', 'VP Operations'),
(5, 4, 'Sophia', 'Taylor', 'sophia.t@novatech.cloud', '+1-555-1041', 'Lead Architect');

-- 4. Insert Deals
INSERT INTO deals (id, company_id, primary_contact_id, assigned_user_id, title, value, stage, expected_close_date, closed_at) VALUES
(1, 1, 1, 2, 'FinCorp Core Cloud Migration', 75000.00, 'closed_won', '2026-08-15', '2026-08-20 14:30:00'),
(2, 2, 3, 2, 'HealthPulse Enterprise Compliance Suite', 42000.00, 'negotiation', '2026-09-30', NULL),
(3, 3, 4, 3, 'Vanguard Fleet Telematics Integration', 95000.00, 'proposal', '2026-10-15', NULL),
(4, 4, 5, 3, 'NovaTech Security Audit & Hardening', 18000.00, 'lead', '2026-11-01', NULL),
(5, 1, 2, 2, 'FinCorp Auxiliary Backup System', 12000.00, 'closed_lost', '2026-07-10', '2026-07-12 11:00:00');

-- 5. Insert Deal Stage History (Audit Log)
INSERT INTO deal_stage_history (deal_id, from_stage, to_stage, changed_by_user_id, changed_at) VALUES
(1, 'proposal', 'negotiation', 2, '2026-08-01 09:00:00'),
(1, 'negotiation', 'closed_won', 2, '2026-08-20 14:30:00'),
(2, 'lead', 'contacted', 2, '2026-08-10 10:15:00'),
(2, 'contacted', 'proposal', 2, '2026-08-25 15:45:00'),
(2, 'proposal', 'negotiation', 2, '2026-09-02 11:30:00'),
(5, 'proposal', 'closed_lost', 2, '2026-07-12 11:00:00');

-- 6. Insert Activities
INSERT INTO activities (deal_id, contact_id, user_id, activity_type, subject, notes, activity_date) VALUES
(1, 1, 2, 'meeting', 'Final Contract & SLA Sign-off', 'David confirmed all technical requirements were met. Deal closed!', '2026-08-20 13:00:00'),
(2, 3, 2, 'call', 'Security & HIPAA Review Call', 'Emily had questions about data encryption at rest. Shared whitepaper.', '2026-09-01 16:00:00'),
(3, 4, 3, 'meeting', 'Onsite Logistics Discovery Session', 'Walked through Chicago facility. Preparing custom proposal.', '2026-08-28 11:00:00'),
(4, 5, 3, 'email', 'Introductory Capabilities Deck', 'Sent initial cloud security audit package.', '2026-09-05 09:30:00');

-- 7. Insert Invoice for the Closed Won Deal
INSERT INTO invoices (deal_id, invoice_number, subtotal, tax_amount, total_amount, status, due_date, paid_at) VALUES
(1, 'INV-2026-001', 75000.00, 3750.00, 78750.00, 'paid', '2026-09-20', '2026-08-25 16:00:00');
