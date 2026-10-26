# Hwange Diocesan Sacramental & Pastoral RMS - Comprehensive User Manual (v4.0)

Welcome to the official operations and user manual for the Catholic Diocese of Hwange - Zimbabwe. This guide provides comprehensive operational instructions for parish priests, mission administrators, chancellors, treasurers/bookkeepers, and pastoral councils.

---

## 1. System Access & Authentication

Before accessing the system, ensure the environment is running (on local machines, double-click `LAUNCH_RMS.bat` or navigate to your diocesan web address).

### How to Sign In
1. Open your browser and navigate to the system URL (`http://localhost:8000` or your diocesan cloud URL).
2. On the **Login** page:
   - **Username**: Enter your assigned username (e.g., surname, parish identifier, or `bishop_hwange` for the Bishop).
   - **Password**: Enter your secure password (default temporary password: `Hwange2026!`).
3. Click **Login**.

> [!NOTE]
> **Episcopal Access**: The Bishop of Hwange (**Rt. Rev. Raphael Mabuza Ncube**) is recognized and greeted dynamically by name upon signing in and signing out under the dedicated username `bishop_hwange`.

> [!IMPORTANT]
> First-time logins require changing the temporary password immediately for data security.

---

## 2. User Roles & Permission Hierarchy

| Role | Permitted Access | Restricted Areas |
| :--- | :--- | :--- |
| **Bishop / Chancellor** | Full diocesan oversight, episcopal visitations, audit logs, chancery financial audits, staff management. | None. |
| **Parish Priest / Mission Admin** | Sacramental registers, parish faithful, daily ledger, monthly cashbook returns, temporal goods, outstations, Mass intentions, handover hub. | Other parishes' private registers. |
| **Bookkeeper / Treasurer** | Daily cash & bank ledger, monthly financial returns, financial reports, data vault backups. | **Strictly locked out** from confidential sacramental registers (Baptisms, Matrimonial PNI, RCIA, Letters of Standing). |
| **Deacon / Parish Secretary** | Sacramental data entry (Drafts), letters of standing generation, parishioner directory. | Financial audits, chancery approval locks, user administration. |

---

## 3. Financial Stewardship & 23-Line Diocesan Cashbook

The financial module mirrors the official Roman Catholic Diocese of Hwange 23-Line Double-Entry Accounting Return, bridging day-to-day book entries with chancery canonical submissions.

### 3.1 Daily Cash & Bank Ledger (`dashboard/daily_ledger.php`)
- **Dual Account Tracking**: Log transactions into **Cash at Hand** (parish safe/box) or **Cash in Bank** (Stanbic, CBZ, BancABC, etc.).
- **Line 1 Tithe / Unsupo / Mutulo**: Explicitly captures parish tithes in Tonga, Nambya, Ndebele, and English traditions.
- **Parish Sunday Collections vs. Diocesan Collections**: Sunday collections remain local parish income.
- **Annual Diocesan Special Collections (Pass-Through Lines)**:
  - Mission Sunday Collection
  - Seminary Collection
  - Peter's Pence (Pope's Charity)
  - Holy Land (Good Friday) Collection
  - Missionary Childhood Association
  - Social Communications Sunday
  - Lenten Campaign Collection
  - Caritas Sunday Collection
- **Voucher & Receipt Uploads**: Attach scanned PDF vouchers, bank deposit slips, or camera photos (JPG, PNG up to 12MB) per transaction.
- **Outstation Tagging**: Tag collections directly to the rural outstation where they were collected.
- **Custom Categories**: Mission admins can create bespoke income and expenditure categories.

### 3.2 Monthly Financial Return (`dashboard/finance_submission.php`)
- **1-Click Auto-Sync**: Automatically aggregates all daily ledger entries for the month into the 23 standard lines.
- **Multi-Currency Support**: Native handling of **USD ($)**, **ZiG**, and **ZAR (R)**.
- **Chancery Verification Lock**: When the Diocesan Finance Office or Chancellor audits and verifies a submission (`status = 'Verified'`), the return is canonically locked to prevent retrospective alterations.
- **Export Formats**: Export verified returns to official Microsoft Word (`.docx`) or CSV spreadsheets.

---

## 4. Parish Temporal Goods & Asset Inventory (Canon 1283 §2)

Canon law mandates that administrators prepare a signed, accurate inventory of all immovable property, movable goods, and precious items belonging to the parish.

- **Location**: `dashboard/assets.php`
- **Asset Categories**:
  - **Sacred Vessels & Vestments**: Chalices, ciboria, monstrances, reliquaries, vestments (highlighted with sacred reverence badges).
  - **Mission Vehicles & Transport**: Land Cruisers, trucks, motorcycles, tractors, trailers (includes registration and engine numbers).
  - **Solar & Borehole Installations**: Solar arrays, inverters, deep-well borehole pumps, water storage tanks.
  - **Buildings & Infrastructure**: Church buildings, rectory/fathers' house, convent, parish halls, schools, clinics.
  - **Office & IT Equipment**: Computers, printers, sound systems, solar lighting.
  - **Furniture & Fixtures**: Pews, altars, confessionals, cabinets, rectory furniture.
  - **Livestock & Agricultural Assets**: Cattle, goats, mission fields, irrigation infrastructure.
- **Valuation & Condition**: Tracks acquisition date, estimated replacement value, condition status (Good, Fair, Poor, Under Repair, Condemned), and storage location.
- **Photo Attachments**: Upload photographs of high-value items for insurance and handover audits.
- **Printable Inventory**: 1-click printable report formatted for canonical parish transitions.

---

## 5. Mission Outstations & Rural Mass Centres Directory

Rural parishes in Hwange Diocese oversee extensive mission networks spanning tens to hundreds of kilometers.

- **Location**: `dashboard/outstations.php`
- **Key Record Fields**:
  - Outstation / Mass Centre Name & Patron Saint (Feast Day)
  - Road distance from the Mission Centre (in kilometers)
  - Head Catechist Name & Mobile Contact
  - Parish Pastoral Council Chairperson Name & Mobile Contact
  - Mass Celebration Schedule (e.g. 1st & 3rd Sundays of the month)
  - Active Catholic Families / Households Count
  - Church Infrastructure status (Dedicated church building vs. school/tree shelter)
- **Pastoral Impact**: Provides immediate contact info for catechists during pastoral emergencies and enables geographic tracking of faithful.

---

## 6. Canonical Mass Intentions & Stipends Book (Canons 945–958)

Fulfills the canonical requirement (Canon 958) that pastors maintain an accurate register of Mass intentions received, the stipend offering, and the fulfillment of the celebrations.

- **Location**: `dashboard/mass_intentions.php`
- **Intention Classifications**:
  - **Repose of the Soul / Deceased**: For the faithful departed.
  - **Thanksgiving**: In gratitude for blessings, anniversaries, graduations.
  - **Healing / Sick**: Prayers for physical or spiritual recovery.
  - **Special Intention**: Private or personal intentions.
  - **Pro Populo**: Obligatory Mass applied for the people of the parish by the pastor (Canon 534).
- **Celebrant Assignment**: Assign intentions to parish priests or visiting clergy.
- **Status Tracking**: 1-click toggling between **Pending**, **Celebrated**, or **Transferred** (sent to Chancery or other missions).
- **Sacristy Noticeboard Printout**: Generates a clean, liturgical schedule for the sacristy board.

---

## 7. Sacramental Hub & Certificates

The core spiritual registries of the Church:
- **Holy Baptism Register (Canon 877)**: Primary root record for every Catholic. Includes parental details, sponsors, and margins for subsequent sacraments.
- **Holy Confirmation Register (Canon 895)**: Confirmation date, minister, and confirmation sponsor.
- **Holy Matrimony & Prenuptial Inquiries (Canons 1067 & 1122)**: Complete PNI investigation tracking, dispensations, and marriage witness entries.
- **Letters of Standing**: Rapid generation of official canonical suitability letters for godparents, sponsors, and marital banns.
- **Heirloom Certificates**: 1-click generation of beautifully formatted, printable certificates with dynamic QR code verification.

---

## 8. The Canonical Handshake (Mission Handover Protocol)

When a Parish Priest or Mission Administrator is transferred (Canon 535 §5):
1. **Outgoing Priest Sign-Off**: Reviews sacramental register tallies, temporal goods assets, and financial balances in `dashboard/handover_hub.php`.
2. **Succession Acceptance**: The incoming priest conducts a joint physical verification, inspects sacred vessels, and signs off digitally.
3. **Chancery Verification**: The Chancellor or Bishop receives the signed digital audit report for episcopal archives.

---

## 9. Data Vault, Backups & CSV Export

- **Location**: `dashboard/backup_vault.php`
- **One-Click CSV Spreadsheets**: Export raw data for Baptisms, Marriages, Confirmations, Parishioners, Daily Ledger, Monthly Cashbooks, Temporal Goods Assets, Outstations, and Mass Intentions.
- **Cloud Vault Persistence**: Automatic synchronization with encrypted GitHub Cloud Vault guarantees zero record loss across serverless redeployments.

---

## 10. Troubleshooting & Support

- **Forgotten Password**: Click "Forgot password?" on the login page to initiate recovery via your registered email.
- **Account Locked**: Accounts lock temporarily after 5 failed password attempts. Contact the Chancery IT Admin to unlock.
- **Hard Refresh**: If dashboard styling looks outdated after an update, press `Ctrl + F5` to clear your browser cache.
- **Canonical Support**: Use the **Communication Hub** (`communication_hub.php`) to send official queries directly to the Chancery Office.

*Ad Majorem Dei Gloriam — Diocese of Hwange*
