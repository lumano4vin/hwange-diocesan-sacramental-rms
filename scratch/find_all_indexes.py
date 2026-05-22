import os

search_dir = r"C:\Users\VINCENT\Documents"
for root, dirs, files in os.walk(search_dir):
    # skip node_modules and vendor
    if any(p in root for p in ["node_modules", "vendor", ".git", ".vercel"]):
        continue
    for file in files:
        if file == "index.php" or "index_backup" in file or "login" in file:
            full_path = os.path.join(root, file)
            print(f"File: {full_path}, Size: {os.path.getsize(full_path)} bytes")
