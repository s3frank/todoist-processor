# Introduction
Simple set of PHP code that can be deployed for example on Google Apps to parse a plaintext body for embedded todo items and send them into a Todoist account. Deploy this on a public URL and you can post data from any app on any platform that can do a HTTP POST and have it parsed for specific todo items that you want to get extracted and into your Todoist account. I created this because I have been trying to find a way to mimic what I do on paper....write stuff in meetings and tag / mark things for follow up later using symbols like exclamation marks or underlining etc. I sometimes take notes on iPad using Drafts4 and other days on my Macbook Air using nvALT or another form of plain text. Using this parser service I can now mix random notes with action / todo items using some simple markers and constructs to streamline my workflow.

This code should work on any PHP5 and upwards environment. How you gain access or set this up is not relevant to this readme, it is assumed that you know how to do that. This code has been tested on a variety of systems including cloud services such as:
* Microsoft Azure
* Google App Engine

If you don't have any public php server at your disposal then I suggest you take a look at Google App Engine which is free unless you generate a very high number of Todo's on a regular basis.

# How to Use once Deployed
Throughout this example we will assume the following:
- Fake Main URL: http://td.parser.com/parse.php
- All other files are inaccessible by the outside world

### POST Data to parse.php
You can only send text data that is to be parse to service via a HTTP POST. Any other method will be ignored and no response will be given other then a 404 message.

There are two parameters that must be included in a form-urlencoded POST:
* api-key: matched against the value of parser-key attribute in the credentials.txt file
* data: the actual payload that needs to be parsed for Todo items

You can make api-key / parser-key anything you want it to be as long as it doesn't contain spaces. The idea with the key is to put in an extra layer of security that you can easily change should you think your service is compromised. It also means that your Todoist Authentication information is not send around with each call nor can it be caught with man in the middle things etc.


#### A basic parsing example
Suppose you are in a meeting and taking notes in plain text form using whatever device of your choosing. There is some background stuff you might need later and then in the meeting some items get discussed that require you to follow up and take action. Your notes might look like this:

```
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi finibus tempor orci eu elementum. Integer vehicula ligula quis lorem aliquet gravida. Fusce commodo nec sapien eget sollicitudin. 
* [ ] ?Lookup the BS Generator App
* [ ] Buy more ink for the printer
Morbi varius felis sed nunc dapibus posuere. Maecenas fermentum, massa nec cursus cursus, nisi ipsum aliquet turpis, vel vehicula urna justo eu justo. Curabitur urna nibh, mattis vitae augue vitae, vulputate gravida justo. In nec nibh malesuada, commodo tortor eu, rhoncus metus.
* [ ] !Get lotery tickets and retire asap
Morbi vehicula in velit id congue. Sed eu quam tincidunt, congue leo sit amet, aliquam purus. Ut eget tincidunt quam. Integer facilisis risus purus, id ornare leo laoreet in. Suspendisse tortor nibh, ornare congue maximus at, tincidunt at enim.
```

There are three action items inside of the text above each is processed by marking them with:
```* [ ]```

Apps like Drafts 4 and 1Writer for iPad understand this Markdown and will auto insert it for you etc. But you can change your marker of a todo to anything you like by editing the regex in the todoist-helper.php file. Look for the function named ```parseTodos()```

For each item that is found the following will happen:

1. Check the priority by verifying if the first character after the marker is either an exclamation, question or neither. The todo item created in Todoist has priority mapped accordingly (!=high, ?=low, anything else=normal).
2. A REST call is made to Todoist for each todo item to be added to your Inbox



#### Adding Due date and time
The Todoist API exposes a separate field for passing a "natural language" due date and time string at creation time of a todo item. You can optionally take advantage of this by adding to the todo item a double colon followed by the date-time string. You can find out more about the various possible structures (can even do funky recurring things) of this date time string from this link: https://todoist.com/help/datestimes

So if we would modify the 2nd todo item in example above and set a due date and time for next friday at 2pm it would look like this:

```* [ ] Buy more ink for the printer :: next friday at 2pm```

The double colon and date-time string will not end up in the todo item content.


#### Adding Todo items to a particular project
Sometimes you may not want to have todo items ending up in Inbox but instead into a specific project. This can be done by adding to the Todo item a double forward slash followed by the project name you would like to add the todo item to. If the Project does not exist yet it will be created automagically. So if we would modify the 2nd todo item in example above to have it go into the "Work" project it would look like this:

```* [ ] Buy more ink for the printer :: next friday at 2pm //Work```

or

```* [ ] Buy more ink for the printer :: next friday at 2pm // Work```

Notice the whitespace between the double forward slashes and the Project? It doesn't matter how many spaces etc you put between them, it's trimmed out both left and right.
* You can only have one project in each line item. 
* The project and the forward slashes will not end up in the todo item content. 



#### Adding labels / tags


            // The title field can contain in left to right order:
            // priority: !=high, ?=low and nothing = normal
            // title: the main title
            // datestring: marked by ::
            // project: marked by //
            // labels: each marked by @
