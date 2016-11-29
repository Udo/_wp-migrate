#!/bin/bash
cd "$(dirname "$0")"

echo "running..." > privatedata/status-$1
tar czf "privatedata/$2" $3 2>/dev/null >/dev/null 
echo "done" > privatedata/status-$1

