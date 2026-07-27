#!/usr/bin/env python3
from __future__ import annotations
import argparse, json
from pathlib import Path


def flatten(doc):
    out = {}
    def walk(m, package):
        name = f"{package}.{m['name']}" if package else m['name']
        out[name] = {(f['number'], f['name'], f['label'], f['type'], f.get('type_name', '')) for f in m['fields']}
        for nested in m.get('nested', []): walk(nested, package)
    for fd in doc:
        for m in fd.get('messages', []): walk(m, fd.get('package', ''))
    return out


def main():
    ap=argparse.ArgumentParser();ap.add_argument('expected',type=Path);ap.add_argument('candidate',type=Path);args=ap.parse_args()
    a=flatten(json.loads(args.expected.read_text()));b=flatten(json.loads(args.candidate.read_text()))
    failed=False
    for name in sorted(set(a)|set(b)):
        if a.get(name)!=b.get(name):
            failed=True;print(f"DIFF {name}")
            for item in sorted(a.get(name,set())-b.get(name,set())): print('  - expected',item)
            for item in sorted(b.get(name,set())-a.get(name,set())): print('  + candidate',item)
    if failed: raise SystemExit(1)
    print('Descriptor maps match.')
if __name__=='__main__':main()
