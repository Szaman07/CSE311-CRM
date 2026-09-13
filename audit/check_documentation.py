"""Read-only documentation checks; no database, PHP or framework execution."""
from pathlib import Path
import copy
import json
import re
import sys
from decimal import Decimal
ROOT = Path(__file__).resolve().parents[1]
errors = []
def check(condition, message):
    if not condition: errors.append(message)
files = [ROOT / "README.md", ROOT / "AUDIT_VERDICT_AND_12_WEEK_PLAN.md"]
files += sorted((ROOT / "docs").rglob("*.md")) + sorted((ROOT / "theory").rglob("*.md"))
files += [ROOT / "archive/README.md"]
link_count = 0
fence = chr(96) * 3
for path in files:
    body = path.read_text(encoding="utf-8-sig")
    check(body.count("\n" + fence) % 2 == 0, f"Unbalanced fences: {path.name}")
    prose = re.sub(fence + r"[\s\S]*?" + fence, "", body)
    for raw in re.findall(r"\[[^\]]+\]\(([^)]+)\)", prose):
        target = raw.strip("<>").split("#")[0]
        if not target or re.match(r"https?://|mailto:", target): continue
        link_count += 1
        check((path.parent / target).resolve().exists(), f"Missing link: {path.relative_to(ROOT)} -> {target}")
    check("\ufffd" not in body, f"Replacement character: {path.name}")
srs = (ROOT / "docs/SRS.md").read_text(encoding="utf-8")
tests = (ROOT / "docs/TEST_PLAN.md").read_text(encoding="utf-8")
reqs = set(re.findall(r"\b(?:NFR|FR)-\d{2}\b", srs))
traced = set(re.findall(r"\b(?:NFR|FR)-\d{2}\b", tests))
check(reqs == traced, f"Requirement mismatch: {reqs ^ traced}")
check(len(reqs) == 24, "Expected 16 FR + 8 NFR")
brs = set(re.findall(r"\bBR-\d{2}\b", srs))
check(len(brs) == 20, "Expected 20 business rules")
ddl = (ROOT / "docs/schema/inventory_reference.mysql.sql").read_text(encoding="utf-8")
tables = re.findall(r"CREATE TABLE\s+(\w+)", ddl, re.I)
expected = {"users","categories","products","customers","sales","sale_items","stock_movements"}
check(set(tables) == expected and len(tables) == 7, "Business table set mismatch")
sql = re.sub(r"--[^\n]*", "", ddl)
check(not re.search(r"\b(DROP|TRUNCATE|USE)\b|CREATE\s+DATABASE", sql, re.I), "Destructive/database-switching reference SQL")
for name in re.findall(r"REFERENCES\s+(\w+)", sql, re.I):
    check(name in expected, f"Unknown referenced table: {name}")
names = re.findall(r"CONSTRAINT\s+(\w+)", sql, re.I)
check(len(names) == len(set(names)), "Duplicate constraint names")
check(sql.count("ENGINE=InnoDB") == 7, "Every table must use InnoDB")
# Limited evaluator for the keywords in this artifact; not a full JSON Schema validator.
schema = json.loads((ROOT / "docs/contracts/record-sale.schema.json").read_text())
example = json.loads((ROOT / "docs/contracts/record-sale.example.json").read_text())
def valid(value, spec):
    if "$ref" in spec: spec = schema["$defs"][spec["$ref"].split("/")[-1]]
    if "anyOf" in spec: return any(valid(value, b) for b in spec["anyOf"])
    kind = spec.get("type")
    if kind == "object":
        if not isinstance(value, dict): return False
        if not set(spec.get("required", [])).issubset(value): return False
        props = spec.get("properties", {})
        if spec.get("additionalProperties") is False and not set(value).issubset(props): return False
        return all(valid(v, props[k]) for k,v in value.items() if k in props)
    if kind == "array":
        return isinstance(value,list) and spec.get("minItems",0)<=len(value)<=spec.get("maxItems",float("inf")) and all(valid(v,spec["items"]) for v in value)
    if kind == "string":
        return isinstance(value,str) and ("pattern" not in spec or re.search(spec["pattern"],value) is not None)
    if kind == "integer":
        return type(value) is int and spec.get("minimum",-float("inf"))<=value<=spec.get("maximum",float("inf"))
    if kind == "null": return value is None
    raise ValueError(f"Unsupported type: {spec}")
check(valid(example,schema), "Sale example does not fit schema")
cases = []
for field,value in [("quantity",0),("quantity",1000001),("quantity",True),("expected_unit_price","1e2"),("expected_unit_price","1.001"),("expected_unit_price",10),("product_id",1)]:
    bad=copy.deepcopy(example); bad["items"][0][field]=value; cases.append(bad)
bad=copy.deepcopy(example); bad["actor_id"]="1"; cases.append(bad)
bad=copy.deepcopy(example); bad["items"]=[]; cases.append(bad)
for i,bad in enumerate(cases): check(not valid(bad,schema),f"Invalid fixture accepted: {i}")
check(Decimal("99999999.99")*1000000*100 == Decimal("9999999999000000.00"), "Cart maximum calculation")
print(json.dumps({"status":"FAIL" if errors else "PASS","markdown_files":len(files),"local_links":link_count,
 "requirements_traced":len(reqs),"business_rules":len(brs),"business_tables":tables,
 "contract_positive_cases":1,"contract_negative_cases":len(cases),"errors":errors,
 "limits":"Static documentation/schema-fixture checks only; no SQL runtime/browser validation."},indent=2))
sys.exit(1 if errors else 0)
