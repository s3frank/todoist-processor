<?php

// Todoist API construct strings
define("API_BASE", "http://todoist.com/API");
define("APIS_BASE", "https://todoist.com/API");
define("LOGIN", "/login");
define("GET_PROJECTS", "/getProjects");
define("ADD_PROJECT", "/addProject");
define("ADD_TODO", "/addItem");
define("GET_OPEN_TODO", "/getUncompletedItems");
define("GET_CLOSED_TODO", "/getAllCompletedItems");

function tdInit()
{
    // parse the credentials.txt file for auth parameters for the Todoist account
    $credentials = getNameValuesFromFile("credentials.txt");
	define("AUTH_TOKEN", trim($credentials["td_api_key"]));
	return AUTH_TOKEN;
}


// Validate if a project exist or not in 
function validateProject($project, $createIfNew)
{
    if(isset($project) == false) return null;

    // does the project already exist and if not go create it?
    $the_project = projectExists($project, false);
    if (!isset($the_project) and $createIfNew == true) 
    {
        // Project doesn't exist and we are asked to created it on the fly
        $the_project = addProject($project);
    }
    
    // Return the project (which could be null if it didn't exist and $createIfNew was false)
    return $the_project;
}

// Get the array of projects from Todoist
function getProjects()
{
    $projects = null;
    $projects = json_decode(file_get_contents(API_BASE . GET_PROJECTS . "?token=" . AUTH_TOKEN));
    return $projects;
}

// Check if a project exists and return it
// Optionally if inboxOnNull is true return the inbox project
function projectExists($project, $inboxOnNull)
{
    $projects = getProjects();
    $inboxProject = null;
    if(isset($projects)) 
    {
        foreach ($projects as &$test) 
        {
            // Check for the inbox project if flag was set to true
            if (($inboxOnNull == true) and ($test->inbox_project == "1")) $inboxProject = $test;
            
            // Do we have a match with the passed in project name?
        	if(strcasecmp($test->name, $project) == 0) return $test;
        }
	}
    return $inboxProject;
}

// Add a project to Todoist
function addProject($project)
{
    $color = rand(1,22); // randomize the color for the new project
    $new_project = json_decode(file_get_contents(API_BASE . ADD_PROJECT . "?token=" . AUTH_TOKEN . "&name=" . urlencode($project) . "&color=" . $color));
    return $new_project; 
}

// Get all uncompleted items for the given project
function getUncheckedItems($project)
{
    $todoItems = null;
    $todoItems = json_decode(file_get_contents(API_BASE . GET_OPEN_TODO . "?token=" . AUTH_TOKEN . "&project_id=" . $project->id));
    return $todoItems;
}
// Get all completed items for the given project
function getCheckedItems($project)
{
    $todoItems = null;
    $todoItems = json_decode(file_get_contents(API_BASE . GET_CLOSED_TODO . "?token=" . AUTH_TOKEN . "&project_id=" . $project->id . "&limit=500"));
    return $todoItems; 
}


// Create a new todo in Todoist
function processTodo($todo)
{
    if (isset($todo) == false) return;
          
    // Validate the project was set. Create it if non existing or ignore it when creating the todo item.
    $the_project = validateProject($todo->project, true);
    
    // Do we have any labels that we should be adding in the todo item?
    $td_title = $todo->title;
    if (isset($todo->labels))
    {
        // Todoist already has server side code to parse the @xxxx from the todo item
        // So we just loop over the labels and at them at the very end of the todo title we send to Todoist
        foreach ($todo->labels as &$label) 
        {
            $td_title = $td_title . " @" . $label;
        }
        $td_title = trim($td_title);        
    }
    
    // Now call add the todo on the API
    $todo_params = "&content=" . urlencode($td_title) . "&priority=" . $todo->prio;
    if(isset($the_project)) $todo_params = $todo_params . "&project_id=" . $the_project->id;
    if (isset($todo->date_string)) $todo_params = $todo_params . "&date_string=" . urlencode($todo->date_string);
    $new_todo = json_decode(file_get_contents(API_BASE . ADD_TODO . "?token=" . AUTH_TOKEN . $todo_params));
    
    // Sometimes the date string can result in failure. Need to capture: 
    if (strcasecmp(print_r($new_todo, true), "ERROR_WRONG_DATE_SYNTAX") == 0)
    {
        // Ok we try again but without the date_string field. We will add a note to the item explaining what was done wrong
        $note = "ERROR_WRONG_DATE_SYNTAX\r\n\r\nYou entered an date format that could not be parsed correctly by Todoist.\r\n Your date text: " . $todo->date_string;
        $note = $note . "\r\n\r\nPlease take a look at the Todoist documentation and try again!\r\n\r\nhttps://todoist.com/help/datestimes";
        $todo_params = "&content=" . urlencode($td_title) . "&priority=" . $todo->prio;
        if(isset($the_project)) $todo_params = $todo_params . "&project_id=" . $the_project->id;
        $todo_params = $todo_params . "&note=" . urlencode($note);
        $new_todo = json_decode(file_get_contents(API_BASE . ADD_TODO . "?token=" . AUTH_TOKEN . $todo_params));
    }

    return $new_todo;
}

// Parse text for Todo items
function parseTodos($parseInput)
{
    $strTest = $parseInput;
//    Keep this around for debugging and testing
//    $strTest = "This is line 1 and it's just notes Same but this is line 2 * [ ] Action item 1 * [ ] *Action item 2 * [ ] Checked item 1\n* [x] Unchecked item 3\n\n* Some more text here #\n* And some more text here    agagagd\n* [ ] Item number 4\n\n# Meyer text\n**Vette text**\n* *I test the bo";
    $re = "/(?:\\n|^)(?:\\s*)(?:(?:\\-|\\*) )(?:\\[( |x)\\] )(.+)/i"; 
    $rgx_count = preg_match_all($re, $strTest, $todos);
    if($rgx_count > 0)
    {
        // We found todo items, so lets loop over them and build an easy to use array that will be easier to process later
        $todoItems = $todos[2];
        $todoState = $todos[1];
        $results = array();
        while (list($key, $val) = each($todoItems)) 
        {
            // Create an object and set values on it by name
            $todo = new stdObject();
            $title = $val;
            
            // The title field can contain in left to right order:
            // priority: !=high, ?=low and nothing = normal
            // title: the main title
            // datestring: marked by ::
            // project: marked by //
            // labels: each marked by @
            
            // Extract priority from the title which is marked with ! at the start of of the title.
            if (substr($val,0,1) == "!")
            {
                // high
                $todo->prio = "4";
                $title = substr($val,1);  
            }
            else if (substr($val,0,1) == "?")
            {
                // low
                $todo->prio = "1";
                $title = substr($val,1);                  
            }
            else
            {
                // normal
                $todo->prio = "2";   
            }
            
            // Find all the labels using a reg expression
            $hasLabels = strpos($title, "@");
            if($hasLabels > 0)
            {
                // Find all labels using a regex.
                $re = "/((?<=@)[^@]*)/"; 
                //$str = "* [ ] some stuff is here but @lab1 @lab 2 @lab3";
                $str = $title;
                preg_match_all($re, $str, $labels);
                if (isset($labels) and is_array($labels))
                {
                    // We have labels in an array. Loop over them an trim each item before adding it to the todo object
                    $clean_labels = array();
                    foreach ($labels[0] as &$label) 
                    {
                        array_push($clean_labels, trim(preg_replace('/\s+/', '', $label)));
                    }
                    
                    // add the labels to the todo item and the cut the title for next step
                    $todo->labels = array_merge($clean_labels, array());
                    $title = trim(substr($title, 0, $hasLabels));
                }
            } 
            
            // Find the project and then cut the title from the right side
            $hasProject = strpos($title, "//");
            if ($hasProject > 0)
            {
                // We found our marker so grab the data and cut the title
                $project = substr($title, $hasProject+2);
                $todo->project = trim($project);
                $title = trim(substr($title, 0, $hasProject));
            }
            
            // Find the date string and then cut the title from the right side
            $hasDueDate = strpos($title, "::");
            if ($hasDueDate > 0)
            {
                // We found our marker so grab the data and cut the title
                $date_string = substr($title, $hasDueDate+2);
                $todo->date_string = trim($date_string);
                $title = trim(substr($title, 0, $hasDueDate));
            }

            // Now we have stripped the title to what it should be so add that property in as well
            $todo->title = $title;

            // Was the item already checked or not?
            $todo->checked = strcasecmp($todoState[$key], "x") == 0 ? true: false;
            
            // Add the new todo object to the results array at the end
            array_push($results, $todo);
        }
        return $results;   
    }
    else
    {
        // We didn't find any Todo items so lets just return null
        return null;
    }
}




// Low level Helper functions for the parser to work easily.
class stdObject {
    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
    }
    public function __call($method, $arguments) {
        $arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
}
function getNameValuesFromFile($file) 
{
    $data = file($file);
    $returnArray = array();
    foreach ($data as $line) 
    {
        $explode = explode(":", $line);
        $returnArray[$explode[0]] = $explode[1];
    }
    return $returnArray;
}

?>