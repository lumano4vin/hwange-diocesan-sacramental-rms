import json

transcript_path = r"C:\Users\VINCENT\.gemini\antigravity\brain\2e0c6b84-5b33-470e-9df0-bdab86cdc449\.system_generated\logs\transcript.jsonl"

with open(transcript_path, "r", encoding="utf-8") as f:
    count = 0
    for line in f:
        try:
            step = json.loads(line)
            tool_calls = step.get("tool_calls", [])
            for tc in tool_calls:
                method = tc.get("method")
                args = tc.get("args", {})
                # print any file write/replace tools to understand their name
                if "file" in method or "replace" in method:
                    print(f"Step {step.get('step_index')}: {method} -> {args.get('TargetFile') or args.get('targetFile') or args.get('AbsolutePath') or list(args.keys())}")
                    count += 1
                    if count > 20:
                        break
            if count > 20:
                break
        except Exception as e:
            pass
