#!/bin/bash
cd "$(dirname "$0")"

echo "running..." > privatedata/status-export

echo "- source path: $1" > privatedata/export-report.txt 
echo "- destination path: $2" >> privatedata/export-report.txt
echo "- db password: *****" >> privatedata/export-report.txt
echo "- db source: $4" >> privatedata/export-report.txt
echo "- db destination: $5" >> privatedata/export-report.txt
echo "- exclude: $6" >> privatedata/export-report.txt

echo "rsync -a $6 $1 $2" >> privatedata/export-report.txt 
rsync -av $6 $1 $2 >> privatedata/export-report.txt 2>&1

echo "mysqldump -u root $4 > privatedata/export-current-in.sql" >> privatedata/export-report.txt
mysqldump -u root -p$3 $4 > privatedata/export-current-in.sql

echo "strict-replace.php..." >> privatedata/export-report.txt
./strict-replace.php privatedata/export-current-in.sql privatedata/export-current-out.sql privatedata/export-repl-in.txt privatedata/export-repl-with.txt >> privatedata/export-report.txt 2>&1

echo "mysql -u root $5 < privatedata/export-current-in.sql" >> privatedata/export-report.txt 
mysql -u root -p$3 $5 < privatedata/export-current-in.sql

rm privatedata/export-current-out.sql
rm privatedata/export-current-in.sql

echo "done" > privatedata/status-export

