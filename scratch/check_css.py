with open(r"C:\Users\VINCENT\Documents\hwange diocesan sacramental rms\assets\css\style.css", "r", encoding="utf-8") as f:
    content = f.read()

import re
matches = re.findall(r"\.login-container[^{]*\{[^}]*\}", content)
print(f"Matches count: {len(matches)}")
for match in matches:
    print(match)
