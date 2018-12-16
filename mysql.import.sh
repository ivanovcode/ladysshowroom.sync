#!/bin/bash
rm ./dumps/ladysshowroom.sql
sshpass -psp1@rt2@necmsk ssh domenick@78.107.235.183 -p2323 "mysqldump ladysshowroom -uladysshowroom -pY1l1I3h7" > ./dumps/ladysshowroom.sql
mysql -uroot -pSP@RT@NEC -D ladysshowroom -e "DROP DATABASE ladysshowroom"
mysql -uroot -pSP@RT@NEC -e "CREATE DATABASE ladysshowroom"
mysql -uroot -pSP@RT@NEC ladysshowroom < ./dumps/ladysshowroom.sql