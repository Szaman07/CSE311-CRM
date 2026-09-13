-- ============================================================================
-- NexaCRM: Academic Query Workbook & Views
-- Practice queries covering core CSE311 syllabus concepts
-- ============================================================================

USE nexacrm_db;

-- ----------------------------------------------------------------------------
-- 1. INNER JOIN: List all deals with company name, primary contact, and rep
-- ----------------------------------------------------------------------------
SELECT 
    d.id AS deal_id,
    d.title AS deal_title,
    d.value,
    d.stage,
    c.name AS company_name,
    CONCAT(ct.first_name, ' ', ct.last_name) AS contact_name,
    u.full_name AS assigned_rep
FROM deals d
INNER JOIN companies c ON d.company_id = c.id
LEFT JOIN contacts ct ON d.primary_contact_id = ct.id
INNER JOIN users u ON d.assigned_user_id = u.id
ORDER BY d.value DESC;


-- ----------------------------------------------------------------------------
-- 2. LEFT JOIN: Find all companies and their deals (including companies with 0 deals!)
-- ----------------------------------------------------------------------------
SELECT 
    c.name AS company_name,
    c.industry,
    COUNT(d.id) AS total_deals,
    COALESCE(SUM(d.value), 0.00) AS total_deal_value
FROM companies c
LEFT JOIN deals d ON c.id = d.company_id
GROUP BY c.id, c.name, c.industry;


-- ----------------------------------------------------------------------------
-- 3. GROUP BY with HAVING: Pipeline summary by stage (value > $20,000)
-- ----------------------------------------------------------------------------
SELECT 
    stage,
    COUNT(id) AS deal_count,
    SUM(value) AS total_stage_value,
    ROUND(AVG(value), 2) AS avg_deal_value
FROM deals
GROUP BY stage
HAVING SUM(value) > 20000.00
ORDER BY total_stage_value DESC;


-- ----------------------------------------------------------------------------
-- 4. SUBQUERY with EXISTS: Companies that have at least one 'closed_won' deal
-- ----------------------------------------------------------------------------
SELECT name, industry, annual_revenue
FROM companies c
WHERE EXISTS (
    SELECT 1 
    FROM deals d 
    WHERE d.company_id = c.id 
      AND d.stage = 'closed_won'
);


-- ----------------------------------------------------------------------------
-- 5. VIEW: Pipeline Summary by Stage
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_pipeline_summary AS
SELECT 
    stage,
    COUNT(id) AS deal_count,
    COALESCE(SUM(value), 0.00) AS total_pipeline_value,
    ROUND(COALESCE(AVG(value), 0.00), 2) AS avg_deal_size
FROM deals
GROUP BY stage;


-- ----------------------------------------------------------------------------
-- 6. VIEW: Sales Representative Performance Leaderboard
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_sales_rep_performance AS
SELECT 
    u.id AS rep_id,
    u.full_name AS rep_name,
    u.email,
    COUNT(d.id) AS total_deals_managed,
    COUNT(CASE WHEN d.stage = 'closed_won' THEN 1 END) AS won_deals,
    COUNT(CASE WHEN d.stage = 'closed_lost' THEN 1 END) AS lost_deals,
    COALESCE(SUM(CASE WHEN d.stage = 'closed_won' THEN d.value ELSE 0 END), 0.00) AS total_won_revenue,
    CASE 
        WHEN COUNT(CASE WHEN d.stage IN ('closed_won', 'closed_lost') THEN 1 END) > 0 
        THEN ROUND(
            (COUNT(CASE WHEN d.stage = 'closed_won' THEN 1 END) / 
             COUNT(CASE WHEN d.stage IN ('closed_won', 'closed_lost') THEN 1 END)) * 100, 
            1
        )
        ELSE 0.0
    END AS win_rate_percentage
FROM users u
LEFT JOIN deals d ON u.id = d.assigned_user_id
WHERE u.role = 'sales_rep'
GROUP BY u.id, u.full_name, u.email
ORDER BY total_won_revenue DESC;
