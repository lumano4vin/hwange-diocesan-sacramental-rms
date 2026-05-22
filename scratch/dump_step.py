import json

transcript_path = r"C:\Users\VINCENT\.gemini\antigravity\brain\2e0c6b84-5b33-470e-9df0-bdab86cdc449\.system_generated\logs\transcript.jsonl"

steps_to_dump = [1781, 2110]
with open(transcript_path, "r", encoding="utf-8") as f:
    for line in f:
        try:
            step = json.loads(line)
            if step.get("step_index") in steps_to_dump:
                print(f"\n=================== STEP {step.get('step_index')} ===================")
                print(f"Source: {step.get('source')}, Type: {step.get('type')}")
                tool_calls = step.get("tool_calls", [])
                for tc in tool_calls:
                    print(f"Tool Name: {tc.get('name')}")
                    args = tc.get("args", {})
                    for k, v in args.items():
                        if k in ["CodeContent", "ReplacementContent", "ReplacementChunks"]:
                            # print length or first 500 chars
                            val_str = str(v)
                            print(f"  {k}: length={len(val_str)}, snippet={val_str[:500]}...")
                        else:
                            print(f"  {k}: {v}")
        except Exception as e:
            pass
