# Introduction
Simple set of PHP code that can be deployed for example on Google Apps to parse a plaintext body for embedded todo items and send them into a Todoist account.

This code should work on any PHP5 and upwards environment. How you gain access or set this up is not relevant to this readme, it is assumed that you know how to do that. This code has been tested on a variety of systems including cloud services such as:
* Microsoft Azure
* Google App Engine

If you don't have any public php server at your disposal then I suggest you take a look at Google App Engine which is free unless you generate a very high number of Todo's on a regular basis.

# How to Use once Deployed
Throughout this example we will assume the following:
- Main URL: http://td.parser.com/parse.php
- All other files are inaccessible by the outside world

### POST Data to parse.php
You can only send text data that is to be parse to service via a HTTP POST. Any other method will be ignored and no response will be given other then a 404 message.

There are two parameters that must be included in a form-urlencoded POST:
* api-key
** this matched against the value of parser-key in the credentials.txt file
* data
** this the actual payload that needs to be parsed

