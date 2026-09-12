# Hwange Diocesan Sacramental RMS - System Walkthrough

Welcome to the newly deployed **Hwange Diocesan Sacramental Records Management System (SRMS)**. This platform has been meticulously crafted for the Catholic Diocese of Hwange to digitize, secure, and manage over a century of canonical records across all its parishes and missions.

## System Architecture & Hosting
- **Frontend & App Logic**: Hosted on **Vercel's Serverless Edge Network**. This guarantees blazing-fast load times, 24/7 uptime, and the ability to access the system from any smartphone, tablet, or computer globally.
- **Cloud Database Vault**: Data is securely stored on an **Aiven MySQL Cloud Database**. This means the diocese is no longer vulnerable to local hardware failures, power cuts, or stolen hard drives. The canonical data is redundantly backed up in the cloud.
- **Security**: Database credentials and environment variables are strictly encrypted within Vercel's secure vault and excluded from GitHub to prevent public leaks.

---

## Core Canonical Modules

### 1. Diocesan Command Center
The unified dashboard provides the Bishop and Chancery with a high-level view of all sacramental statistics (baptisms, confirmations, marriages, ordinations, and deaths) alongside a **Deanery Activity Heatmap** that tracks canonical intensity across different regions.

### 2. Sacramental Registers
Fully digitized books for:
- **Baptisms**: Tracks godparents, minister, and subsequent canonical notations (Canon 535).
- **Holy Matrimony**: Links directly to the Prenuptial Investigations and automatically flags if a baptismal parish needs to be notified.
- **Confirmations & First Holy Communion**: Batch processing and individual entries.
- **Deaths / Christian Burials**: Records of the deceased, cemetery locations, and sacraments received.
- **Ordinations & Religious Professions**: For tracking vocations within the diocese.

### 3. Multi-lingual Canonical Certificates
The system includes a premium certificate generator capable of printing official Sacramental Certificates in 7 languages:
* English, Tonga, Ndebele, Shona, Nambya, Chewa, and Traditional Latin.

### 4. Prenuptial Investigations (PNI)
A specialized canonical module built to manage the Freedom to Marry, track the Banns of Marriage (Canon 1067), document impediments, and record dispensations required before Holy Matrimony.

### 5. Clergy & Chancery Management
- **Clergy Dossiers**: Secure management of clergy profiles, dates of ordination, incardination status, and faculties.
- **Parish Assignments**: Track which priests are assigned to which parishes, and automatically handle digital sign-offs during a Parish Handover.

---

## Security & Access Control

The SRMS utilizes strict **Role-Based Access Control (RBAC)** to enforce Canon Law regarding archival access:
- **Diocesan Admin / Chancellor**: Global access to view, edit, and permanently delete records across all parishes.
- **Parish Priest / Deacon**: Can view global records (for verifying baptismal status prior to marriage), but can only add/edit records within their assigned parish. They are the only ones who can "Verify" a drafted record.
- **Parish Secretary**: Can draft records for their assigned parish, but cannot alter a record once a Priest has locked and "Verified" it.
- **Audit Trails**: Every creation, modification, or sign-in is securely logged with timestamps and the exact user's identity.

> [!IMPORTANT]
> **Data Integrity**
> Only Chancery Administrators have the power to permanently delete a canonical record. Parish-level users can only archive or flag records to prevent accidental loss of historical data.

---

## Brand and Copyright Information
- **System Name**: Hwange Diocesan Sacramental Records Management System (SRMS)
- **Copyright**: © 2026 Catholic Diocese of Hwange. All Rights Reserved.
- **Developer Attribution**: System designed, developed, and maintained by LumSystems.
- **Confidentiality Notice**: The data within this system is highly confidential and strictly governed by the Code of Canon Law and the Diocesan Privacy Policy. Unauthorized distribution is prohibited.

---

## Final Touches & System Polish (May 2026)
* **Associate Clergy Assignment**: Registered **Fr. Tarcius Munkombwe** as an Associate Priest assigned to **Our Lady of Fatima Mission** (Parish ID 20), assisting **Fr. Tendai Dube**.
* **Automatic Pastoral History Sync**: Synchronized the `parish_assignments` table across all 38 active parish priests in the database. This ensures their active parish assignments display perfectly in parish histories and directories.
* **Clergy Credential Finalization**: Reset all default user accounts to the unified default password `Hwange2026!` with a flag to require changing password upon first login, excluding **Fr. Stanislaus Lumano** (who is actively using the portal for St. Francis Xavier), **Fr. Vincent Lumano**, and the central **admin** account to preserve operational access.
* **Episcopal Portal Greetings**: Updated the database user configuration and helper functions to recognize the Bishop of Hwange by name. When the Bishop (`bishop_hwange`) signs in or out, he is greeted dynamically as **Rt. Rev. Raphael Mabuza Ncube**.
* **User Manuals Re-alignment**: Updated both the offline markdown manual (`USER_MANUAL.md`) and the in-app interactive database manual (`user_manual.php`) to document the Bishop's account credentials, default password protocols, and the custom Episcopal recognition logic.
* **Translation Audit & Health Validation**: Resolved outdated translation keys in `actions/system_health_check.php` to align with the active Nambya language engine. Verified that the pre-flight integrity check compiles with **100% SUCCESS and 0 Warnings**.

