# Walkthrough - Localhost & HTTPS Configuration Synchronization

This walkthrough documents the auditing and configuration updates performed to ensure clean parity between local development (`localhost`) and production/cloud environments (`https`) for both the **St. Francis Primary School ERP (SFX Portal)** and the **Hwange Diocesan Sacramental Records Management System (RMS)**.

## Work Accomplished

### 1. St. Francis Xavier Primary School ERP (`sfx-portal`)
* **Environment Variable Realignment (`.env`)**:
  * Uncommented `NEXTAUTH_URL="http://localhost:3000"` to prevent authentication warnings locally and ensure correct auth redirects during local testing.
  * Added `NEXT_PUBLIC_APP_URL="http://localhost:3000"` to expose the local host address to the client-side bundle.
* **Dynamic QR Code Verification (`ReportCard.tsx`)**:
  * Refactored the hardcoded verification endpoint from the static production URL (`https://sfxportal.edu.zw/verify/...`) to dynamically utilize the configured application URL:
    ```typescript
    const verificationUrl = `${process.env.NEXT_PUBLIC_APP_URL || "https://sfxportal.edu.zw"}/verify/${student.id}`;
    ```
  * Now, scanning the QR code when testing locally will direct the reviewer to the local server, whereas production deployments will automatically fallback to the live domain.
* **Next.js Dev Server Verification**:
  * Verified that the active Next.js development server successfully detected and reloaded the modified `.env` file without compile errors or crashes.
* **UAT Testing Kit Update (`UAT_TESTING_KIT.md` & `UAT_TESTING_KIT.pdf`)**:
  * Updated the UAT guide markdown file `UAT_TESTING_KIT.md` to document both production and localhost environments for testers.
  * Modified the PDF compiler script `scratch/compile-pdf.js` to include the localhost URL option in the generated output.
  * Successfully compiled `UAT_TESTING_KIT.pdf` using the headless Chromium-based Edge driver.
  * Verified the file size is updated and includes the new local environment instruction.

---

### 2. Hwange Diocesan Sacramental RMS
* **Database Host Autodetection (`includes/db.php`)**:
  * Verified that the PHP database utility dynamically detects the host setting.
  * If `.env` defines a `DB_HOST` (e.g. pointing to the remote Aiven MySQL cluster `mysql-3614de40-lumano4vin-1607.f.aivencloud.com`), it initiates an SSL connection to the production cloud.
  * If no `DB_HOST` is defined or if `database.sqlite` is present locally, the system automatically falls back to portable mode using the local SQLite file.
* **Relative Redirects & Public Links**:
  * Verified that public actions, login controllers, and password resets (`forgot_password.php`, `reset_password.php`, etc.) use relative paths (e.g. `redirect("../forgot_password.php")`) instead of hardcoding root domains. This ensures the app is fully host-agnostic and functions identically on `127.0.0.1:8000` and Vercel.
* **QR Codes (`sacraments/*_certificate.php`)**:
  * Confirmed that sacramental certificates print QR codes containing raw canonical database metadata (GUID and entry reference numbers) rather than hardcoded URLs, ensuring they remain offline-first and database-agnostic.

---

### 3. Mixed Marriage & Unregistered PNI Candidate Support
* **Backend Auto-Registration Logic (`marriage_pni_add.php`)**:
  * Implemented interception of `groom_registration_type` and `bride_registration_type` values (`registered` or `unregistered`).
  * If a candidate is unregistered, validates their biographical inputs (First Name, Last Name, DOB) and programmatically creates a new active record in the `parishioners` SQLite/MySQL database table.
  * Captures the newly generated `person_id` and seamlessly links it to the prenuptial investigation statement record.
* **Frontend Toggles and Responsive Layout (`marriage_pni_add.php`)**:
  * Separated the Groom and Bride sections in the HTML form to provide breathing room and support side-by-side or stacked responsive displays.
  * Added beautiful sliding toggle radio buttons styled with HSL tokens matching the global system aesthetic.
  * Created dynamic detail containers (`groom-unregistered-group` and `bride-unregistered-group`) holding text inputs for First/Last/Other Names, DOB, place of birth, parents' names, and baptismal place/denomination.
* **Dynamic Interactivity (JavaScript)**:
  * Wrote event-driven JS handlers that show/hide biographical detail inputs and toggle `required` attributes depending on the selected registration status.
  * Integrated visibility logic for sacrament preview boxes, showing them only when a registered parishioner is selected.
* **Database Self-Healing Schema (`includes/db.php`)**:
  * Added self-healing schema migration checks inside `includes/db.php` to automatically alter `prenuptial_investigations` and add missing banns-related columns (`banns_date_1`, `banns_date_2`, `banns_date_3`, `banns_parish_id`) when running on local SQLite databases.
* **Verification and Simulation Script (`scratch/test_pni_simulation.php`)**:
  * Created an automated CLI simulation test script to run form submissions with mock sessions and mock POST requests.
  * Successfully verified that submitting an unregistered candidate registers them as an active parishioner, links them to a new PNI statement record, and persists all canonical attributes without warnings or errors.

---

### 4. RMS Final Polish & Credential Finalization (May 2026)
* **Associate Clergy Assignment**: Added **Fr. Tarcius Munkombwe** as an Associate Priest assigned to **Our Lady of Fatima Mission** (Parish ID 20), assisting **Fr. Tendai Dube**.
* **Automatic Pastoral History Sync**: Synchronized the `parish_assignments` table across all 38 active parish priests in the database to guarantee correct layout displaying in all parochial lists and clergy management dashboards.
* **Clergy Credential Finalization**: Reset all default user accounts to the unified default password `Hwange2026!` with a flag to require changing password upon first login, excluding **Fr. Stanislaus Lumano** (active at St. Francis Xavier), **Fr. Vincent Lumano**, and the central **admin** account.
* **Episcopal Portal Greetings**: Configured the database profiles and template greeting handlers to dynamically welcome and sign out the Bishop of Hwange by name: **Rt. Rev. Raphael Mabuza Ncube**.
* **User Help Manuals Update**: Updated `USER_MANUAL.md` and `user_manual.php` to detail the Bishop's dedicated login credentials, password change protocols, and the custom Episcopal recognition logic.
* **Translation Audit & Health Validation**: Resolved outdated translation keys in `actions/system_health_check.php` to align with the active Nambya language engine. Verified that the pre-flight integrity check compiles with **100% SUCCESS and 0 Warnings**.


