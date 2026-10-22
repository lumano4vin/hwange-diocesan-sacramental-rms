import base64
import os

pdf_path = "Hwange_Diocesan_Sacramental_RMS_Manual.pdf"
out_path = "includes/manual_pdf_data.php"

with open(pdf_path, "rb") as f:
    b64 = base64.b64encode(f.read()).decode("ascii")

with open(out_path, "w", encoding="utf-8") as out:
    out.write('<?php\n')
    out.write('// Auto-generated embedded PDF binary for Vercel serverless resilience\n')
    out.write('function get_embedded_manual_pdf_bytes() {\n')
    out.write('    return base64_decode("' + b64 + '");\n')
    out.write('}\n')

print(f"Generated {out_path} ({os.path.getsize(out_path):,} bytes)")
