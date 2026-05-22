import json

transcript_path = r"C:\Users\VINCENT\.gemini\antigravity\brain\2e0c6b84-5b33-470e-9df0-bdab86cdc449\.system_generated\logs\transcript.jsonl"

modifications = []
with open(transcript_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            step = json.loads(line)
            tool_calls = step.get("tool_calls", [])
            for tc in tool_calls:
                name = tc.get("name", "")
                args = tc.get("args", {})
                if any(x in name for x in ["write_to_file", "replace_file_content", "multi_replace_file_content"]):
                    # Look at TargetFile in args
                    # It might be nested in quotes or direct
                    target = str(args.get("TargetFile", ""))
                    if "index.php" in target:
                        modifications.append({
                            "step_index": step.get("step_index"),
                            "name": name,
                            "args": args
                        })
        except Exception as e:
            pass

print(f"Found {len(modifications)} modifications to index.php:")
for mod in modifications:
    print(f"Step {mod['step_index']}: {mod['name']}")
    args = mod["args"]
    if "CodeContent" in args:
        print(f"  CodeContent length: {len(str(args['CodeContent']))}")
    elif "ReplacementChunks" in args:
        print(f"  ReplacementChunks count: {len(args['ReplacementChunks'])}")
    elif "ReplacementContent" in args:
        print(f"  ReplacementContent: lines {args.get('StartLine')}-{args.get('EndLine')}")
