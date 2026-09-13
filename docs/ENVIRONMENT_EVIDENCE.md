# Measured environment evidence — 12 September 2026

| Item | Observed result |
|---|---|
| OS | Windows, project at `E:/study/Projects/CSE311` |
| XAMPP | 8.2.12 installed at `C:/xampp` from the signed official installer; SHA-256 `12E818CE5AEC79FE646606DF3A80B35DA865EC0213646AD7C92044DCFCEC7535` |
| PHP | 8.2.12, x64, thread-safe, CLI at `C:/xampp/php/php.exe`; Apache bundle uses the same XAMPP PHP tree |
| Required extensions | bcmath, ctype, curl, DOM, fileinfo, filter, hash, mbstring, OpenSSL, PDO/MySQL, session, tokenizer, XML; ZIP loaded for dependency resolution |
| Apache | 2.4.58 |
| Database | `10.4.32-MariaDB`, InnoDB default/transactional |
| SQL behavior | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; REPEATABLE-READ; server default latin1/swedish, while CRM databases/connections explicitly use utf8mb4/utf8mb4_bin and UTC |
| Composer | 2.10.3, installer SHA-384 verified from Composer's official checksum |
| Node/npm | Node 24.x and npm 11.13.0 available in the local toolchain |
| Application | Laravel 12.69.2 selected because mandatory PHP is 8.2; Composer resolved 111 packages and reported no advisories at install |

Laravel 12 accepts PHP 8.2, but its documented bug-fix window ended 13 August 2026 and security-fix window ends 24 February 2027. Upgrade PHP/framework before that deadline. Compatibility does not establish continued vendor security support for the bundled XAMPP PHP/MariaDB components. Course approval of Laravel remains an instructor acceptance gate; installation is not proof of approval.

Databases `nexastock_dev` and isolated `nexastock_test` were created. Development migrations and the synthetic service-driven seed ran on the measured engine. The normal runtime account successfully read the schema and was denied DELETE on sale_items, UPDATE on stock_movements, DROP VIEW, and CREATE TABLE with MariaDB error 1142. Secrets are only in ignored local environment files and are omitted here.
