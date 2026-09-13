"""Portable current-CRM SUM fanout reproduction; not a PHP/MySQL test."""
from pathlib import Path
import json
import re
import sqlite3
ROOT=Path(__file__).resolve().parents[1]
source=(ROOT/"app/companies.php").read_text(encoding="utf-8-sig")
match=re.search(r'\$sql\s*=\s*"([\s\S]*?)";',source)
if not match: raise RuntimeError("Company query not found; inspect current source.")
db=sqlite3.connect(":memory:")
db.row_factory=sqlite3.Row
db.executescript("""
CREATE TABLE companies(id INTEGER PRIMARY KEY,name TEXT,industry TEXT,website TEXT,phone TEXT,annual_revenue NUMERIC);
CREATE TABLE contacts(id INTEGER PRIMARY KEY,company_id INTEGER);
CREATE TABLE deals(id INTEGER PRIMARY KEY,company_id INTEGER,value NUMERIC);
INSERT INTO companies VALUES(1,'FinCorp Global','Financial Services',NULL,NULL,8500000);
INSERT INTO contacts VALUES(1,1),(2,1);
INSERT INTO deals VALUES(1,1,75000),(5,1,12000);
""")
actual=dict(db.execute(match.group(1)).fetchone())
expected=db.execute("SELECT SUM(value) FROM deals WHERE company_id=1").fetchone()[0]
assert actual["total_deal_value"]==174000 and expected==87000
print(json.dumps({"source":"app/companies.php","query":"extracted verbatim from PHP source",
 "actual_join_sum":actual["total_deal_value"],"correct_sum":expected,
 "contacts":actual["total_contacts"],"deals":actual["total_deals"],
 "finding":"Independent child joins multiply amounts despite DISTINCT counts.",
 "limits":"SQLite in-memory counterexample only; no PHP/MySQL/concurrency execution."},indent=2))
