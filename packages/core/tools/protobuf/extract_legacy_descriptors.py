#!/usr/bin/env python3
"""Safely extract AddSerializedFile byte literals from generated *_pb2.py files.

The generated module is parsed as Python syntax but never imported or executed.
Requires the `protobuf` Python package only for descriptor decoding.
"""
from __future__ import annotations
import argparse, ast, json
from pathlib import Path
from google.protobuf import descriptor_pb2


def byte_literals(path: Path) -> list[bytes]:
    tree = ast.parse(path.read_text(encoding="utf-8"), filename=str(path))
    found: list[bytes] = []
    for node in ast.walk(tree):
        if not isinstance(node, ast.Call) or not node.args:
            continue
        fn = node.func
        if isinstance(fn, ast.Attribute) and fn.attr == "AddSerializedFile":
            value = ast.literal_eval(node.args[0])
            if isinstance(value, bytes):
                found.append(value)
    return found


def normalized(fd: descriptor_pb2.FileDescriptorProto) -> dict:
    def message(m, prefix=""):
        full = f"{prefix}.{m.name}" if prefix else m.name
        return {
            "name": full,
            "fields": [{
                "name": f.name, "number": f.number, "label": f.label, "type": f.type,
                "type_name": f.type_name, "oneof_index": f.oneof_index if f.HasField("oneof_index") else None,
                "proto3_optional": f.proto3_optional,
            } for f in m.field],
            "nested": [message(n, full) for n in m.nested_type],
            "enums": [{"name": e.name, "values": [{"name": v.name, "number": v.number} for v in e.value]} for e in m.enum_type],
        }
    return {
        "name": fd.name, "package": fd.package, "syntax": fd.syntax,
        "dependencies": list(fd.dependency),
        "messages": [message(m) for m in fd.message_type],
        "enums": [{"name": e.name, "values": [{"name": v.name, "number": v.number} for v in e.value]} for e in fd.enum_type],
    }


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("inputs", nargs="+", type=Path)
    ap.add_argument("--out", required=True, type=Path)
    args = ap.parse_args()
    results = []
    for path in args.inputs:
        literals = byte_literals(path)
        if not literals:
            raise SystemExit(f"No AddSerializedFile descriptor found in {path}")
        for raw in literals:
            fd = descriptor_pb2.FileDescriptorProto.FromString(raw)
            results.append(normalized(fd))
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(results, indent=2, sort_keys=True), encoding="utf-8")
    print(f"Wrote {len(results)} descriptor map(s) to {args.out}")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
