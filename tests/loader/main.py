from pprint import pformat
import json

from twisted.internet import reactor
from twisted.internet.defer import Deferred
from twisted.internet.protocol import Protocol
from twisted.web.client import Agent
from twisted.web.http_headers import Headers

from stringprod import StringProducer

class BeginningPrinter(Protocol):
    j = None
    def __init__(self, finished, j):
        self.finished = finished
        self.remaining = 1024 * 10
        self.j = j

    def dataReceived(self, bytes):
        if self.remaining:
            display = bytes[:self.remaining]
            print self.j, 'Some data received:', display
            self.remaining -= len(display)

    def connectionLost(self, reason):
        #print 'Finished receiving body:', reason.getErrorMessage()
        self.finished.callback(None)

agent = Agent(reactor)
body = StringProducer(json.dumps({"player_id":"6666","time":"1433437121","device_id":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","platform":"iPhone","data":"Event=Start, Money=15444"}))

def cbRequest(response, j):
    #print 'Response version:', response.version
    #print 'Response code:', response.code
    #print 'Response phrase:', response.phrase
    #print 'Response headers:'
    #print pformat(list(response.headers.getAllRawHeaders()))
    finished = Deferred()
    response.deliverBody(BeginningPrinter(finished, j))
    return finished

def cbRequestErrback(param):
    print param

def cbShutdown(ignored, j):
    if j==99:
        reactor.stop()
    pass

for j in range(1000):
    d = agent.request(
        'POST',
        'http://ec2-52-24-133-14.us-west-2.compute.amazonaws.com',
        Headers({'Content-type': ['application/json'], 'Accept': ['text/plain']}),
        body)

    d.addCallback(cbRequest, j)
    d.addErrback(cbRequestErrback)

    d.addBoth(cbShutdown, j)

reactor.run()

