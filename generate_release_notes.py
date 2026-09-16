import glob
import re

out_lines = ["# 🚀 Release Notes\n"]

readmes = ["README.md"] + sorted([f for f in glob.glob("README_*.md")])

for filepath in readmes:
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()
    
    lang_name = filepath.replace("README_", "").replace(".md", "")
    if lang_name == "README": lang_name = "English"
    
    out_lines.append(f"\n<details><summary><b>🌐 {lang_name} Changelog</b></summary>\n\n")
    
    start_idx = -1
    end_idx = -1
    lines = content.split("\n")
    for i, line in enumerate(lines):
        if line.startswith("#") and "🔄" in line:
            start_idx = i + 1
        elif line.startswith("#") and "🙏" in line and start_idx != -1:
            end_idx = i
            break
    
    if start_idx != -1 and end_idx != -1:
        changelog = "\n".join(lines[start_idx:end_idx]).strip()
        out_lines.append(changelog)
    else:
        out_lines.append("_Changelog not found._")
    
    out_lines.append("\n\n</details>")

with open("RELEASE_NOTES.md", "w", encoding="utf-8") as f:
    f.write("\n".join(out_lines))
