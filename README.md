# Introduction
Simple set of PHP code that can be deployed for example on Google Apps to parse a plaintext body for embedded todo items and send them into a Todoist account. Deploy this on a public URL and you can post data from any app on any platform that can do a HTTP POST and have it parsed for specific todo items that you want to get extracted and into your Todoist account. I created this because I have been trying to find a way to mimic what I do on paper....write stuff in meetings and tag / mark things for follow up later using symbols like exclamation marks or underlining etc. I sometimes take notes on iPad using Drafts4 and other days on my Macbook Air using nvALT or another form of plain text. Using this parser service I can now mix random notes with action / todo items using some simple markers and constructs to streamline my workflow.

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
* api-key: 
...matched against the value of parser-key attribute in the credentials.txt file
* data: 
...the actual payload that needs to be parsed for Todo items

You can make api-key / parser-key anything you want it to be as long as it doesn't contain spaces. The idea with the key is to put in an extra layer of security that you can easily change should you think your service is compromised. It also means that your Todoist Authentication information is not send around with each call nor can it be caught with man in the middle things etc.

#### A basic parsing example
Suppose you are in a meeting and taking notes in plain text form using whatever device of your choosing. There is some background stuff you might need later and then in the meeting some items get discussed that require you to follow up and take action. Your notes might look like this:

```Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi finibus tempor orci eu elementum. Integer vehicula ligula quis lorem aliquet gravida. Fusce commodo nec sapien eget sollicitudin. Nulla ultrices pretium nisi vitae suscipit. Mauris non elementum diam, et sollicitudin augue. Aenean tincidunt odio eu dignissim semper. Duis sed nunc non urna rutrum finibus. Donec accumsan eleifend libero quis pellentesque. Ut ultricies odio quis sem euismod aliquam.

* [ ] ?Lookup the BS Generator App
* [ ] Buy more ink for the printer

Morbi varius felis sed nunc dapibus posuere. Maecenas fermentum, massa nec cursus cursus, nisi ipsum aliquet turpis, vel vehicula urna justo eu justo. Curabitur urna nibh, mattis vitae augue vitae, vulputate gravida justo. Pellentesque interdum aliquam ullamcorper. Curabitur enim orci, fermentum at ultrices ac, rhoncus ac nisl. Duis at lorem eget neque elementum tempus eu at massa. Proin magna ipsum, luctus nec ligula quis, accumsan luctus nibh. In nec nibh malesuada, commodo tortor eu, rhoncus metus.

* [ ] !Get lotery tickets and retire asap

Morbi vehicula in velit id congue. Sed eu quam tincidunt, congue leo sit amet, aliquam purus. Ut eget tincidunt quam. Integer facilisis risus purus, id ornare leo laoreet in. Suspendisse tortor nibh, ornare congue maximus at, tincidunt at enim. Vivamus fermentum pulvinar mi. Duis mauris quam, congue id felis vel, fermentum tincidunt felis.```



