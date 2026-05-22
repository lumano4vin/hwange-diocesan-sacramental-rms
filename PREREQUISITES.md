# Hwange Diocesan Sacramental Database System - Setup Guide

The Sacramental Database System is designed to be **portable** and **zero-install**. It comes with its own built-in PHP engine, so you don't necessarily need to install any background software to get started.

## 1. Quick Launch (Recommended)

To start the system immediately:
1.  Locate the **`LAUNCH_RMS.bat`** file in the project folder.
2.  Double-click it.
3.  A terminal window will open, and a few seconds later, your web browser will automatically open the login page.

---

## 2. Troubleshooting (If it fails to start)

If the system does not start automatically, please check the following:

### Visual C++ Redistributable
The built-in PHP engine requires the "Visual C++ Redistributable for Visual Studio 2015-2022". Most modern Windows computers already have this. If you see an error about a missing `.dll` file:
1.  Download the installer from [Microsoft](https://aka.ms/vs/17/release/vc_redist.x64.exe).
2.  Run the installer and restart your computer.

### Using XAMPP (Alternative)
If you prefer to use your own XAMPP installation instead of the built-in portable version:
1.  Install XAMPP to `C:\xampp`.
2.  Ensure you have **Apache** and **MySQL** services running (though MySQL is optional as we use **SQLite** for portability).
3.  The `RUN_PORTABLE.ps1` script will automatically detect and use your XAMPP installation if you delete the bundled `php/` folder.

---

## 3. Support
If you encounter any issues with the platform, please contact the Diocesan Information Office.
