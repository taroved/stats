=======================
Examples
=======================

Data sending
------------

$ curl -i -X POST -d '{"player_id":"6666","time":"1433437121","device_id":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","platform":"iPhone","data":"Event=Start, Money=15444"}' http://stats.loc/


List existed dates
------------------
$ curl -i -X GET --user user123:pass123 http://stats.loc/list


Dump data for selected date
---------------------------

$ curl -i -X GET --user user123:pass123 http://stats.loc/dump/150611.json


Delete data for selected date
-----------------------------

$ curl -i -X POST --user user123:pass123 http://stats.loc/delete/150611