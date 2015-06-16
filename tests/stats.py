import requests
import json

url = "http://stats.loc" #"http://ec2-52-24-133-14.us-west-2.compute.amazonaws.com/"
data = {"player_id":"6666","time":"1433437121","device_id":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","platform":"iPhone","data":"Event=Start, Money=15444"}
headers = {'Content-type': 'application/json', 'Accept': 'text/plain'}

for i in range(10000):
    r = requests.post(url, data=json.dumps(data), headers=headers)

print r.status_code
print r.text

