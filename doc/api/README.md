# API for communction between BE and FE

## http

Static access to resources in assets directory

Web root - where?

## WS

RPC-JSON

Version 2.0

request:
{
    "jsonrpc": "2.0", 
    "method": "subtract", 
    "params": {
        "minuend": 42, 
        "subtrahend": 23
    },
    "id": 3
}

response:
{
    "jsonrpc": "2.0",
    "result": 19,
    "id": 3
}

notification-request:
{
    "jsonrpc": "2.0",
    "method": "update",
    "params": [1,2,3,4,5]
}

