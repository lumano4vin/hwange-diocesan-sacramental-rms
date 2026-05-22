import json

transcript_path = r"C:\Users\VINCENT\.gemini\antigravity\brain\2e0c6b84-5b33-470e-9df0-bdab86cdc449\.system_generated\logs\transcript.jsonl"

with open(transcript_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            step = json.loads(line)
            if step.get("step_index") == 2110:
                tool_calls = step.get("tool_calls", [])
                for tc in tool_calls:
                    if tc.get("name") == "multi_replace_file_content":
                        args = tc.get("args", {})
                        chunks = args.get("ReplacementChunks")
                        print("Type:", type(chunks))
                        print("Raw chunks string:")
                        print(repr(chunks))
        except Exception as e:
            pass
