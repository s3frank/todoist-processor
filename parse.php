<?php

// load helpers for the main service
include('http-helper.php');
include('todoist-helper.php');

// If we are not receiving a post then simply do nothing but send a 404 to the requestor
if ($_SERVER['REQUEST_METHOD'] != 'POST') send404();

// If we do not see a parameter called api-key with a value: Ber8Ghog1vuK4cE then again we throw a 404 and be done with it.
// This is a safety measure without having to communicate the real todoist api keys over the web the whole time.
$api_key = trim(getNameValuesFromFile("credentials.txt")["parser-key"]);
if (trim($_POST['api-key']) != trim($api_key)) send404();

// Only after that will we assume the request is genuine and start processing as follows:
// Get the text payload from the post message by key: data and then parse it.
if ((isset($_POST['data']) == false) or (strlen($_POST['data']) == 0)) send404();
$todoItems = parseTodos($_POST['data']);

// If we didn't find any todo's we simply return an http OK as technically nothing went wrong.
if (!isset($todoItems)) sendOK("No Todo Items", "The text was parsed succesfully but no todo items were detected!");

// Authenticate to Todoist first. In case of errors we simply send a 404 to not give away any thing related to the uid/pwd
if (authenticate() == "LOGIN_ERROR") send404();

// Now loop and add the todo itesm
foreach ($todoItems as &$todo) 
{
    $new_todo = processTodo($todo);
}

sendOK(count($todoItems) . " Todo Items processed","Check in Todoist to see the results.");

?>