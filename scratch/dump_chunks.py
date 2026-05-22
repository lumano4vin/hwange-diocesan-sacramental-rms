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
                        chunks = args.get("ReplacementChunks", [])
                        if isinstance(chunks, str):
                            chunks = json.loads(chunks, strict=False)
                        print(f"Number of chunks: {len(chunks)}")
                        for idx, chunk in enumerate(chunks):
                            print(f"\n--- Chunk {idx} (Lines {chunk.get('StartLine')} to {chunk.get('EndLine')}) ---")
                            print("TARGET CONTENT:")
                            print(chunk.get("TargetContent"))
                            print("REPLACEMENT CONTENT:")
                            print(chunk.get("ReplacementContent"))
        except Exception as e:
            print("Error parsing step:", e)
