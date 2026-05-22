import difflib

path_current = r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms\index.php"
path_backup = r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms (17)\hwange diocesan sacramental rms\index.php"

with open(path_current, "r", encoding="utf-8") as f:
    current_lines = f.readlines()

with open(path_backup, "r", encoding="utf-8") as f:
    backup_lines = f.readlines()

diff = difflib.unified_diff(
    backup_lines,
    current_lines,
    fromfile="backup_index.php",
    tofile="current_index.php",
    n=3
)

print("Unified Diff:")
for line in diff:
    # Print only lines starting with + or - (excluding +++ and ---)
    if (line.startswith("+") or line.startswith("-")) and not (line.startswith("+++") or line.startswith("---")):
        print(line.strip())
