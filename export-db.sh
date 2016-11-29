#!/bin/bash
cd "$(dirname "$0")"

echo "running..." > privatedata/status-$1-db
mysqldump -u root -p$2 $1 | gzip > $3
echo "done" > privatedata/status-$1-db

