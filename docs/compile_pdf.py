import os
import re
import markdown
from xhtml2pdf import pisa

# Input/Output paths
md_path = r"c:\Users\VINCENT\Documents\hwange diocesan sacramental rms\docs\USER_MANUAL_VISUAL.md"
pdf_path = r"c:\Users\VINCENT\Documents\hwange diocesan sacramental rms\docs\pdfs\USER_MANUAL_VISUAL.pdf"

print("Reading markdown...")
with open(md_path, "r", encoding="utf-8") as f:
    md_text = f.read()

# Preprocess markdown to remove/convert mermaid diagrams and clean up syntax
print("Preprocessing markdown...")
# Convert > [!IMPORTANT] and similar callouts to beautiful blockquotes
def replace_callout(match):
    type_str = match.group(1).lower()
    content = match.group(2)
    border_color = "#b91c1c" if "important" in type_str or "warning" in type_str or "caution" in type_str else "#38bdf8"
    bg_color = "#fef2f2" if "important" in type_str or "warning" in type_str or "caution" in type_str else "#f0f9ff"
    return f'<div style="border-left: 4px solid {border_color}; background-color: {bg_color}; padding: 8pt 12pt; margin-bottom: 10pt;"><strong>{type_str.upper()}:</strong> {content}</div>'

md_text = re.sub(r'>\s*\[!(IMPORTANT|NOTE|TIP|WARNING|CAUTION)\]\s*\n((?:>\s*.*\n?)*)', replace_callout, md_text)
# Clean up any remaining blockquote markers
md_text = re.sub(r'^>\s*', '', md_text, flags=re.MULTILINE)

# Convert Markdown to HTML
print("Converting markdown to HTML...")
html_body = markdown.markdown(md_text, extensions=['tables', 'fenced_code'])

# Fix image sources to absolute local paths
# Example: src="images/1_login_page.png" -> src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/1_login_page.png"
html_body = html_body.replace('src="images/', 'src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/')

# Build complete HTML with page setup and stylesheet
html_document = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {{
            size: A4;
            margin: 2cm;
        }}
        body {{
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            font-size: 10pt;
        }}
        h1, h2, h3, h4 {{
            color: #0f172a;
            font-family: 'Helvetica-Bold', sans-serif;
            margin-top: 15pt;
            margin-bottom: 8pt;
        }}
        h1 {{
            font-size: 22pt;
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 5pt;
            margin-bottom: 20pt;
        }}
        h2 {{
            font-size: 14pt;
            page-break-before: always;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3pt;
        }}
        h3 {{
            font-size: 11pt;
        }}
        p {{
            margin-bottom: 8pt;
        }}
        ul, ol {{
            margin-bottom: 10pt;
            padding-left: 20pt;
        }}
        li {{
            margin-bottom: 4pt;
        }}
        code {{
            font-family: 'Courier', monospace;
            background-color: #f1f5f9;
            font-size: 9pt;
        }}
        pre {{
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8pt;
            font-family: 'Courier', monospace;
            font-size: 8pt;
            margin-bottom: 10pt;
        }}
        table {{
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15pt;
        }}
        th, td {{
            border: 1px solid #cbd5e1;
            padding: 6pt;
            text-align: left;
            font-size: 9pt;
        }}
        th {{
            background-color: #f1f5f9;
            font-family: 'Helvetica-Bold', sans-serif;
        }}
        img {{
            max-width: 100%;
            height: auto;
            margin: 15pt auto;
            display: block;
        }}
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 50pt; margin-top: 50pt;">
        <h1 style="border: none; font-size: 26pt; color: #0f172a; margin-bottom: 10pt;">Sacramental Records Management System</h1>
        <h2 style="border: none; font-size: 16pt; color: #b91c1c; margin-bottom: 30pt; page-break-before: avoid;">Illustrated ERP Operations Manual</h2>
        <p style="font-size: 12pt; font-weight: bold; color: #64748b;">Roman Catholic Diocese of Hwange</p>
        <p style="font-size: 10pt; color: #94a3b8; margin-top: 5pt;">Version 3.0 • Gold Standard Academic, Canonical, & Technical Guide</p>
    </div>
    
    <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 30pt;" />
    
    {html_body}
</body>
</html>
"""

print("Compiling PDF...")
os.makedirs(os.path.dirname(pdf_path), exist_ok=True)
with open(pdf_path, "wb") as f_out:
    pisa_status = pisa.CreatePDF(html_document, dest=f_out)

if pisa_status.err:
    print(f"Error compiling PDF: {pisa_status.err}")
else:
    print(f"Successfully compiled PDF to: {pdf_path}")
