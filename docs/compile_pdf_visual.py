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
# Convert > [!IMPORTANT] and similar callouts to beautiful parchment-style blockquotes
def replace_callout(match):
    type_str = match.group(1).lower()
    content = match.group(2)
    border_color = "#b91c1c" if type_str in ["important", "warning", "caution"] else "#854d0e"
    bg_color = "#fef2f2" if type_str in ["important", "warning", "caution"] else "#fefcbf"
    return f'<div style="border-left: 3pt solid {border_color}; background-color: {bg_color}; padding: 6pt 10pt; margin-bottom: 8pt; font-family: \'Times-Italic\', serif; font-style: italic; color: #334155;"><strong>{type_str.upper()}:</strong> {content}</div>'

md_text = re.sub(r'>\s*\[!(IMPORTANT|NOTE|TIP|WARNING|CAUTION)\]\s*\n((?:>\s*.*\n?)*)', replace_callout, md_text)
# Clean up any remaining blockquote markers
md_text = re.sub(r'^>\s*', '', md_text, flags=re.MULTILINE)

# Convert Markdown to HTML
print("Converting markdown to HTML...")
html_body = markdown.markdown(md_text, extensions=['tables', 'fenced_code'])

# Fix image sources to absolute local paths
html_body = html_body.replace('src="images/', 'src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/')

# Dynamically parse standard markdown image tags and replace with explicit inline-scaled centered wrappers
# This solves the xhtml2pdf limitation where max-height is ignored and raw images overflow pages
print("Polishing and scaling body screenshots dynamically...")
html_body = re.sub(
    r'<img\s+alt="([^"]*)"\s+src="([^"]*)"\s*/>',
    r'<p style="text-align: center; margin: 4px auto;"><img alt="\1" src="\2" style="height: 1.6in; width: auto; border: 1px solid #cbd5e1; border-radius: 4px;" /></p>',
    html_body
)

# Split HTML body into front-matter (TOC & Executive Summary) and body-matter (Chapter 3 onward)
print("Splitting document sections for page numbering logic...")
parts = re.split(r'(<h2[^>]*>\s*3\.)', html_body, maxsplit=1)
if len(parts) == 3:
    front_matter_html = parts[0]
    body_matter_html = parts[1] + parts[2]
else:
    front_matter_html = html_body
    body_matter_html = ""

# Split front_matter_html into 3 distinct pages:
# Page 2 (Roman i): Introduction & Architecture Diagram
# Page 3 (Roman ii): Executive Summary Pillars
# Page 4 (Roman iii): Table of Contents
print("Splitting front matter into three pages (Roman i, ii, iii)...")
part1_parts = re.split(r'(<h2[^>]*>\s*1\.)', front_matter_html, maxsplit=1)
if len(part1_parts) == 3:
    intro_html = part1_parts[0]
    rest_html = part1_parts[1] + part1_parts[2]
else:
    intro_html = front_matter_html
    rest_html = ""

part2_parts = re.split(r'(<h2[^>]*>\s*2\.)', rest_html, maxsplit=1)
if len(part2_parts) == 3:
    exec_summary_html = part2_parts[0]
    toc_html = part2_parts[1] + part2_parts[2]
else:
    exec_summary_html = rest_html
    toc_html = ""

# Build complete HTML with page setup and stylesheet
html_document = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page cover_layout {{
            size: A4;
            margin: 2cm;
            margin-bottom: 2.5cm;
        }}
        @page roman_i_layout {{
            size: A4;
            @frame header_frame {{
                -pdf-frame-content: header_content;
                left: 2cm; width: 17cm; top: 1cm; height: 1.0cm;
            }}
            @frame content_frame {{
                left: 2cm; width: 17cm; top: 2.2cm; height: 23.8cm;
            }}
            @frame footer_frame {{
                -pdf-frame-content: footer_roman_i;
                left: 2cm; width: 17cm; top: 26.5cm; height: 1.5cm;
            }}
        }}
        @page roman_ii_layout {{
            size: A4;
            @frame header_frame {{
                -pdf-frame-content: header_content;
                left: 2cm; width: 17cm; top: 1cm; height: 1.0cm;
            }}
            @frame content_frame {{
                left: 2cm; width: 17cm; top: 2.2cm; height: 23.8cm;
            }}
            @frame footer_frame {{
                -pdf-frame-content: footer_roman_ii;
                left: 2cm; width: 17cm; top: 26.5cm; height: 1.5cm;
            }}
        }}
        @page standard_layout {{
            size: A4;
            @frame header_frame {{
                -pdf-frame-content: header_content;
                left: 2cm; width: 17cm; top: 1cm; height: 1.0cm;
            }}
            @frame content_frame {{
                left: 2cm; width: 17cm; top: 2.2cm; height: 23.8cm;
            }}
            @frame footer_frame {{
                -pdf-frame-content: footer_numeric;
                left: 2cm; width: 17cm; top: 26.5cm; height: 1.5cm;
            }}
        }}
        @page back_layout {{
            size: A4;
            margin: 2cm;
            margin-bottom: 2.5cm;
        }}
        
        body {{
            font-family: 'Times-Roman', 'Georgia', serif;
            color: #1e293b;
            line-height: 1.4;
            font-size: 10.5pt;
        }}
        h1, h2, h3, h4 {{
            color: #0f172a;
            font-family: 'Times-Bold', serif;
            margin-top: 12pt;
            margin-bottom: 6pt;
        }}
        h1 {{
            font-size: 22pt;
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 5pt;
            margin-bottom: 18pt;
        }}
        h2 {{
            font-size: 14pt;
            page-break-before: always;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3pt;
            margin-top: 14pt;
        }}
        h3 {{
            font-size: 11pt;
            margin-top: 8pt;
            margin-bottom: 4pt;
        }}
        
        /* Suppress page breaks for h2 headings inside the front-matter block to prevent gaps */
        .front-matter h2 {{
            page-break-before: avoid;
            margin-top: 8pt;
            margin-bottom: 4pt;
        }}
        
        p {{
            margin-bottom: 6pt;
            text-align: justify;
        }}
        ul, ol {{
            margin-bottom: 8pt;
            padding-left: 18pt;
        }}
        li {{
            margin-bottom: 3pt;
        }}
        code {{
            font-family: 'Courier', monospace;
            background-color: #f1f5f9;
            font-size: 9pt;
            padding: 1pt 3pt;
        }}
        pre {{
            background-color: #f8fafc;
            border-left: 3px solid #0ea5e9;
            padding: 6pt;
            font-family: 'Courier', monospace;
            font-size: 8pt;
            margin-bottom: 10pt;
        }}
        table {{
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12pt;
        }}
        th, td {{
            border: 0.5pt solid #cbd5e1;
            padding: 5pt;
            text-align: left;
            font-size: 9.5pt;
        }}
        th {{
            background-color: #f8fafc;
            font-family: 'Times-Bold', serif;
            border-bottom: 1.5pt solid #475569;
        }}
    </style>
</head>
<body>
    <!-- Define static header content -->
    <div id="header_content" style="font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; margin-bottom: 5pt;">
        <table style="width: 100%; border: none; margin: 0; padding: 0;">
            <tr>
                <td style="border: none; padding: 0; font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; text-align: left; font-weight: bold;">Catholic Diocese of Hwange - Zimbabwe</td>
                <td style="border: none; padding: 0; font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; text-align: right; font-style: italic;">Sacramental ERP v3.0 Operations Manual</td>
            </tr>
        </table>
        <div style="border-top: 0.5pt solid #cbd5e1; margin-top: 3pt;"></div>
    </div>

    <!-- Define static footer contents (automatically extracted by xhtml2pdf) -->
    <div id="footer_roman_i" style="text-align: center; font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; border-top: 0.5pt solid #cbd5e1; padding-top: 4pt;">
        Catholic Diocese of Hwange - Zimbabwe &bull; Sacramental ERP v3.0 &bull; Page i
    </div>
    <div id="footer_roman_ii" style="text-align: center; font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; border-top: 0.5pt solid #cbd5e1; padding-top: 4pt;">
        Catholic Diocese of Hwange - Zimbabwe &bull; Sacramental ERP v3.0 &bull; Page ii
    </div>
    <div id="footer_numeric" style="text-align: center; font-family: 'Times-Roman', serif; font-size: 8.5pt; color: #64748b; border-top: 0.5pt solid #cbd5e1; padding-top: 4pt;">
        Catholic Diocese of Hwange - Zimbabwe &bull; Powered by LumSystems &bull; Page <pdf:pagenumber/> of <pdf:pagecount/>
    </div>

    <!-- FRONT COVER (NO RUNNING FOOTER, cover_layout active by default) -->
    <div class="cover-matter">
        <div style="text-align: center; margin-top: 10pt; font-family: 'Times-Roman', serif;">
            <h1 style="border: none; font-size: 21pt; color: #0f172a; margin-bottom: 3pt; line-height: 1.2; font-family: 'Times-Bold', serif;">
                Integrated Sacramental Records &amp;<br/>Ecclesiastical Resources Planning (ERP) System
            </h1>
            <h2 style="border: none; font-size: 14pt; color: #b91c1c; margin-bottom: 3pt; page-break-before: avoid; font-family: 'Times-Bold', serif;">
                Illustrated Operations Manual &amp; Help Guide
            </h2>
            <p style="font-size: 11pt; font-weight: bold; color: #475569; margin-bottom: 1pt; text-align: center;">Catholic Diocese of Hwange - Zimbabwe</p>
            <p style="font-size: 9pt; color: #64748b; margin-bottom: 5pt; text-align: center;">Version 3.0 • Gold Standard Academic, Canonical, &amp; Technical Guide</p>
            
            <hr style="border: none; border-top: 1px solid #e2e8f0; width: 30%; margin: 8pt auto;" />
            
            <!-- Custom ERP Infographic Image (Centering Wrapper) -->
            <p style="text-align: center; margin: 5pt auto;">
                <img src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/srms_erp_infographic.png" style="width: 250pt; height: auto; border-radius: 8px; border: 1px solid #e2e8f0;" />
            </p>
            
            <!-- Theological Motto Block -->
            <div style="background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 8pt; border-radius: 4px; margin-top: 10pt; margin-bottom: 10pt;">
                <p style="font-size: 9.5pt; font-style: italic; color: #166534; font-weight: bold; margin: 0; text-align: center; line-height: 1.4; font-family: 'Times-Italic', serif;">
                    "A Database of Souls, A Network of Grace.<br/>Preserving the sacred heartbeat of Hwange through digital devotion."
                </p>
            </div>
            
            <!-- Centered LumSystems Brand Block at Bottom of Front Cover -->
            <div style="text-align: center; margin-top: 15pt;">
                <!-- Centered Logo Image Wrapper -->
                <p style="text-align: center; margin: 0 auto 3pt;">
                    <img src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/lumsystems_logo.png" style="width: 40pt; height: 40pt;" />
                </p>
                <p style="font-size: 12pt; font-weight: bold; color: #0f172a; margin: 0; letter-spacing: 2px; font-family: 'Times-Bold', serif; text-align: center;">LUMSYSTEMS</p>
                <p style="font-size: 8.5pt; color: #b91c1c; font-weight: bold; margin-top: 1pt; margin-bottom: 1pt; letter-spacing: 0.5px; text-align: center;">Honoring Legacy, Illuminating Excellence, Engineering the Future</p>
                <p style="font-size: 8.5pt; color: #64748b; margin-top: 0; margin-bottom: 0; text-align: center;">Lead Architect: <strong>Rev. Fr. Vincent Lumano</strong></p>
            </div>
        </div>
    </div>
    
    <!-- PAGE 2: INTRODUCTION & EXECUTIVE SUMMARY (Roman i) -->
    <pdf:nexttemplate name="roman_i_layout" />
    <pdf:nextpage />
    <div class="front-matter">
        {intro_html}
        {exec_summary_html}
    </div>
    
    <!-- PAGE 3: TABLE OF CONTENTS (Roman ii) -->
    <pdf:nexttemplate name="roman_ii_layout" />
    <pdf:nextpage />
    <div class="front-matter">
        {toc_html}
    </div>
    
    <!-- PAGE 4+: STANDARD CORE CHAPTERS (Arabic) -->
    <pdf:nexttemplate name="standard_layout" />
    <div class="body-matter">
        {body_matter_html}
    </div>
    
    <!-- BACK COVER / BACKMATTER (NO RUNNING FOOTER) -->
    <pdf:nexttemplate name="back_layout" />
    <pdf:nextpage />
    <div class="back-matter">
        <div style="text-align: center; margin-top: 80pt; margin-bottom: 80pt; font-family: 'Times-Roman', serif;">
            <!-- Centered Logo Image Wrapper -->
            <p style="text-align: center; margin-bottom: 20pt;">
                <img src="c:/Users/VINCENT/Documents/hwange diocesan sacramental rms/docs/images/lumsystems_logo.png" style="width: 70pt; height: 70pt;" />
            </p>
            
            <h1 style="border: none; font-size: 20pt; color: #0f172a; margin-bottom: 5pt; page-break-before: avoid; font-family: 'Times-Bold', serif;">Preserving Hwange's Legacy</h1>
            
            <p style="font-size: 10pt; color: #475569; width: 80%; margin: 10pt auto 20pt; line-height: 1.6; text-align: center;">
                The Hwange Diocesan Sacramental Records Management System (SRMS) stands as a monument to the combination of holy canonical tradition and state-of-the-art database technology. By digitally securing historical registers, simplifying mission statistics, and verifying canonical lifecycles, the system preserves the spiritual heritage of Hwange for generations to come.
            </p>
            
            <div style="background-color: #fdf2f8; border-left: 4px solid #db2777; padding: 12pt; border-radius: 4px; width: 80%; margin: 15pt auto; text-align: center;">
                <p style="font-size: 10pt; font-style: italic; color: #9d174d; font-weight: bold; margin: 0; font-family: 'Times-Italic', serif; text-align: center;">
                    "A Database of Souls, A Network of Grace."
                </p>
            </div>
            
            <hr style="border: none; border-top: 1px solid #e2e8f0; width: 30%; margin: 35pt auto 20pt;" />
            
            <p style="font-size: 12pt; font-weight: bold; color: #0f172a; margin-top: 10pt; letter-spacing: 2px; font-family: 'Times-Bold', serif; text-align: center;">LUMSYSTEMS</p>
            <p style="font-size: 9.5pt; color: #b91c1c; font-weight: bold; margin-top: 2pt; letter-spacing: 0.5px; text-align: center;">Honoring Legacy, Illuminating Excellence, Engineering the Future</p>
            <p style="font-size: 8.5pt; color: #64748b; margin-top: 4pt; line-height: 1.4; text-align: center;">For support, inquiries, or registry assistance, contact the Hwange Diocesan Information Office.</p>
            
            <p style="font-size: 8pt; color: #94a3b8; margin-top: 40pt; text-align: center;">&copy; 2026 Catholic Diocese of Hwange - Zimbabwe &bull; Engineered by Rev. Fr. Vincent Lumano</p>
        </div>
    </div>
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
