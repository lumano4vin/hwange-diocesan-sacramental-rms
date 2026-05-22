import os

p_index = r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms - Copy\index.php"
p_css = r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms - Copy\assets\css\style.css"

if os.path.exists(p_index):
    print("=== INDEX.PHP IN BACKUP COPY ===")
    print("Size:", os.path.getsize(p_index))
    with open(p_index, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()
    lines = content.splitlines()
    print("Total lines:", len(lines))
    # Print the title, some body parts, and see if it is a login page
    print("Title tag line:")
    for line in lines:
        if "<title>" in line.lower():
            print("  ", line.strip())
        if "login" in line.lower() and "<div" in line.lower():
            print("   Login tag:", line.strip())
else:
    print("index.php does not exist in backup copy")

if os.path.exists(p_css):
    print("\n=== STYLE.CSS IN BACKUP COPY ===")
    print("Size:", os.path.getsize(p_css))
    with open(p_css, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()
    lines = content.splitlines()
    print("Total lines:", len(lines))
else:
    print("style.css does not exist in backup copy")
