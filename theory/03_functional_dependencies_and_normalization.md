# 3. Functional dependencies and normalization

## Definitions and method

X -> Y means every legal pair of tuples agreeing on X also agrees on Y. It is a semantic claim about all valid instances. A small seed that happens to have distinct names proves no FD.

Armstrong's axioms: reflexivity (Y subset X implies X -> Y), augmentation (X -> Y implies XZ -> YZ), transitivity (X -> Y and Y -> Z imply X -> Z). Union/decomposition/pseudotransitivity follow.

Compute X+ by starting with X and repeatedly adding RHS attributes whose LHS is already included. X is a superkey if X+ is the full schema; it is a candidate key only if no proper subset is a superkey.

To find a minimal cover: split RHS attributes; remove extraneous LHS attributes using closure checks; remove redundant FDs. Recompute closure against the appropriate remaining FDs rather than deleting assumptions by intuition.

## Worked normalization

Teaching relation R(ProductID, CategoryID, CategoryName), F:
ProductID -> CategoryID; CategoryID -> CategoryName.

ProductID+ = all three; it is the only candidate key under F. ProductID is prime; the other attributes are nonprime. This atomic relation is already 1NF and 2NF: no nonprime attribute depends on a proper part of its singleton candidate key (under this F, no empty-set dependency). It violates 3NF because CategoryID -> CategoryName has a non-superkey determinant and nonprime RHS.

Decompose into Products(ProductID,CategoryID) and Categories(CategoryID,CategoryName). Intersection = CategoryID; it determines all Categories attributes, so the binary decomposition is lossless under F. Each original FD is enforceable within one component, so it is dependency-preserving. Each component is BCNF under its projected FDs.

Do not call every denormalized atomic table “UNF,” and don't claim every singleton-key table is 3NF.

## Normal forms

| Form | Test |
|---|---|
| 1NF | Attributes have atomic values in the chosen relational model; no repeating groups |
| 2NF | 1NF and no nonprime attribute partially dependent on any candidate key |
| 3NF | For every nontrivial X -> A in F+, X is a superkey or A is prime |
| BCNF | For every nontrivial X -> Y in F+, X is a superkey |
| 4NF, if in syllabus | Every nontrivial multivalued dependency has a superkey determinant |

Prime means belonging to **some candidate key**, not just the selected primary key. F+ matters even if a dependency was not explicitly listed.

## Why BCNF can lose dependency preservation

Teaching R(Student,Course,Instructor), F:
(Student,Course) -> Instructor; Instructor -> Course.

Candidate keys: (Student,Course) and (Student,Instructor). All attributes are prime, so R meets 3NF under F. Instructor isn't a superkey, so Instructor -> Course violates BCNF.

Decompose into (Instructor,Course) and (Student,Instructor). It is lossless because common Instructor determines the first relation. But (Student,Course) -> Instructor is not enforceable from the two local FD sets alone. A joined check could be needed. Losslessness and dependency preservation are different properties.

## Apply to CRM

ProductID does not determine a historical sale item's unit_price across different sales. Treating current price and historical price as one fact creates incorrect FDs. The movement model deliberately repeats product_id for linked items and caches resulting balances; see [data-design tradeoffs](../docs/DATABASE_DESIGN.md). Never claim every table is universally 3NF just because fields look separated.

## Practice with answers

R(A,B,C,D), F={A -> B, B -> C, AC -> D}. A+ = ABCD, so A is a candidate key. C is extraneous in AC -> D because A -> C follows already; a minimal cover is {A -> B, B -> C, A -> D}. One lossless dependency-preserving 3NF decomposition is AB, BC, AD. AB joined with BC is lossless on B; that result joined with AD is lossless on A.

Try changing A -> B to AB -> C and recalculate; don't reuse the previous key mechanically. Gate: justify every removal in a minimal cover and prove a decomposition, not just name its normal form.
