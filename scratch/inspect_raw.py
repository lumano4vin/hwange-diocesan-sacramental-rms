import json

transcript_path = r"C:\Users\VINCENT\.gemini\antigravity\brain\2e0c6b84-5b33-470e-9df0-bdab86cdc449\.system_generated\logs\transcript.jsonl"

with open(transcript_path, "r", encoding="utf-8") as f:
    for i in range(5):
        line = f.readline()
        if not line:
            print("No more lines.")
            break
        print(f"Line {i}: {line[:300]}...")
        try:
            obj = json.loads(line)
            print(f"Keys: {list(obj.keys())}")
            if "tool_calls" in obj:
                print(f"Tool calls: {obj['tool_calls']}")
            elif "content" in obj:
                print(f"Content: {obj['content'][:200]}")
        except Exception as e:
            print("Failed to parse JSON:", e)
