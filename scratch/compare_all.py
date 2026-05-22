import os
import difflib

dirs = [
    r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms",
    r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms - Copy",
    r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms (14)\hwange diocesan sacramental rms",
    r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms (17)\hwange diocesan sacramental rms"
]

files = [
    "index.php",
    "assets/css/style.css"
]

for f_name in files:
    print(f"\n=================== COMPARING {f_name} ===================")
    contents = {}
    for d in dirs:
        full_path = os.path.join(d, f_name)
        if os.path.exists(full_path):
            with open(full_path, "r", encoding="utf-8", errors="ignore") as f:
                contents[d] = f.read()
        else:
            # Try without nested folder for (14)
            alt_path = os.path.join(d.replace(r"\hwange diocesan sacramental rms", ""), f_name)
            if os.path.exists(alt_path):
                with open(alt_path, "r", encoding="utf-8", errors="ignore") as f:
                    contents[d] = f.read()
            else:
                print(f"File not found in {d}")
                
    base_dir = dirs[0]
    base_content = contents.get(base_dir)
    if base_content is None:
        print(f"Base file not found in {base_dir}!")
        continue
        
    for d, content in contents.items():
        if d == base_dir:
            continue
        if base_content == content:
            print(f"Matches base: {d}")
        else:
            print(f"DIFFERENT: {d}")
            # print a short summary of differences
            diff = list(difflib.unified_diff(
                base_content.splitlines(),
                content.splitlines(),
                fromfile=f"current_{f_name}",
                tofile=f"backup_{f_name}",
                n=1
            ))
            print(f"  Diff lines: {len(diff)}")
            # print first 5 diff lines
            for line in diff[:10]:
                print(f"    {line}")
