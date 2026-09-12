# Hwange Diocesan Sacramental Database System
## Comprehensive Operations Manual (Heavy Duty Edition)
**Version 3.1 • Diocesan Gold Standard**

---

## Table of Contents
1. [Introduction & Vision](#1-introduction--vision)
2. [System Architecture & Requirements](#2-system-architecture--requirements)
3. [Authentication & Access Control](#3-authentication--access-control)
4. [Parishioner Management (The Faithful Registry)](#4-parishioner-management-the-faithful-registry)
5. [The Sacramental Hub (Canonical Modules)](#5-the-sacramental-hub-canonical-modules)
6. [Clergy & Mission Management](#6-clergy--mission-management)
7. [Communication Hub & Support Tickets](#7-communication-hub--support-tickets)
8. [The Canonical Handshake (Handover Protocol)](#8-the-canonical-handshake-handover-protocol)
9. [Reporting & Data Analytics](#9-reporting--data-analytics)
10. [Advanced Troubleshooting & System Health](#10-advanced-troubleshooting--system-health)

---

## 1. Introduction & Vision
The Hwange Diocesan Records Management System (RMS) is a custom-built digital ecosystem designed to unify the sacramental life of the Catholic Diocese of Hwange - Zimbabwe. Its primary goal is to ensure the **integrity, availability, and confidentiality** of the canonical records of the faithful, from Baptism to the Order of Christian Funerals.

---

## 2. System Architecture & Requirements
### Technology Stack
*   **Backend**: PHP 8.x with SQLite 3 high-performance database.
*   **Frontend**: Vanilla HTML5/CSS3 with a "Glassmorphism" design system.
*   **Deployment**: Portable local server environment (Zero-config).

### Local Environment Setup
To ensure the system runs at peak performance:
1.  **LAUNCH_RMS.bat**: The main entry point. Double-click this to start the PHP server.
2.  **SYSTEM_HEALTH_CHECK.bat**: Run this if the dashboard feels sluggish. It clears temporary caches and verifies database integrity.

---

## 3. Authentication & Access Control
The system uses a **Role-Based Access Control (RBAC)** model to protect sensitive data.

### User Roles
| Role | Permissions |
| :--- | :--- |
| **Administrator** | Full system access, user management, and global data correction. |
| **Parish Priest** | Access to mission-specific faithful, sacramental entry, and handover tools. |
| **Bishop / Chancery** | Diocesan oversight, analytics, and final approval of mission transitions. |

### Password Security
Passwords are encrypted using the `PASSWORD_ARGON2ID` algorithm. If you forget your password, the **Forgot Password** portal uses time-limited reset tokens sent to your registered diocesan email.

---

## 4. Parishioner Management (The Faithful Registry)
The **Faithful Registry** is the backbone of the system. Every sacrament must be linked to a valid person in this registry.

### Search & Filtering
The system uses an **Intelligent Search Engine** that scans:
*   Legal First and Last Names.
*   Baptismal/Other Names.
*   Parish of Registry.

### Status Indicators
*   `[ACTIVE]`: Currently registered in the local mission.
*   `[MIGRANT]`: Transferred out to another mission (Canonical move).
*   `[IMMIGRANT]`: Transferred into the mission (Pending records update).
*   `[DECEASED]`: Final rest (Locked for sacramental changes).

---

## 5. The Sacramental Hub (Canonical Modules)
Each module is governed by the Code of Canon Law (CIC 1983).

### 5.1 Baptismal Registry (Canon 849)
*   **Fields**: Name, Parents, Godparents, Minister, Date, and Location.
*   **Note**: All other sacraments (Confirmation, Marriage) are cross-referenced to the Baptismal record for validity.

### 5.2 First Holy Communion (Canon 897)
*   Registry of the faithful who have reached the age of reason and received the Eucharist.

### 5.3 Confirmation Registry (Canon 879)
*   **Fields**: Sponsor, Minister (usually the Bishop), and Confirmation Name.

### 5.4 Matrimonial Registry (Canon 1055)
*   **Fields**: Groom, Bride, Witnesses, Dispensations (if any), and Presiding Minister.
*   **Certificates**: One-click generation of the Official Matrimonial Certificate.

### 5.5 Death & Burial Registry (Canon 1176)
*   Records the final rites and ecclesiastical funeral of the faithful.

---

## 6. Clergy & Mission Management
The **Clergy Command Center** allows the Chancery to manage the movement of priests within the Diocese.
*   **Deanery Oversight**: Parishes are grouped by Deanery (e.g., Hwange, Binga, Victoria Falls).
*   **Mission Transitions**: Tracking the formal assignment dates of priests.

---

## 7. Communication Hub & Support Tickets
To replace fragmented WhatsApp/Email communication, the **Communication Hub** provides a centralized platform.
*   **Categories**: Registry Correction, Dispensation Request, Canonical Query, Technical Support.
*   **Priority Levels**: Low, Medium, High, URGENT.
*   **Tracking**: View the full history of communication between the Parish and the Chancery.

---

## 8. The Canonical Handshake (Handover Protocol)
When a priest moves missions, the system enforces a strict "Handshake":
1.  **Outgoing Priest**: Submits a "Handover Report" summarizing the current state of the parish registry and physical books.
2.  **Incoming Priest**: Reviews the digital report and accepts the data.
3.  **Audit**: The Chancery receives a notification when the handshake is complete, finalizing the transfer of digital access.

---

## 9. Reporting & Data Analytics
The **Oversight Portal** (Bishop's View) provides data-driven pastoral insights:
*   **Growth Trends**: See which missions are seeing more Baptisms.
*   **Vocation Monitor**: Track seminarians and aspirants across the Diocese.
*   **Statistical Returns**: Automated generation of the annual status animarum report.

---

## 10. Advanced Troubleshooting & System Health
### Database Backups
Run `BACKUP_DATABASE.bat` every Friday. This creates a timestamped copy of `database.sqlite` in the `/backups` directory.

### Common Error Resolutions
*   **"Database Locked"**: Usually caused by another script accessing the file. Run `SYSTEM_HEALTH_CHECK.bat` to clear locks.
*   **"Session Expired"**: For security, sessions last 30 minutes. Refresh the page to log back in.
*   **"Blank Page"**: Check that the PHP server is actually running in the terminal window.

---

*Ad Majorem Dei Gloriam*
**Catholic Diocese of Hwange - Zimbabwe**
