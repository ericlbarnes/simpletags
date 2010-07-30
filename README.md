# Simpletags

Version 1.2

* Author: [Dan Horrigan](http://dhorrigan.com/)

## DESCRIPTION

Simpletags is exactly what it sounds like...a simple way to use tags in your PHP application.  This allows you to have tags that look like this:

    {something:other}
    {date format="m/d/Y"}
    
    {blog:entries count="5"}
    Stuff here
    {/blog:entries}

## INSTALLATION

1.  Just include the Simpletags.php file into your app

####  CodeIgniter

1.  Put Simpletags.php into your application/libraries folder
2.  Load it like normal: @$this->load->library('events');@ (or autoload it).

## USAGE

You can send a config array to the constructor with the following options (these are the defaults):

    array(
        'l_delim' => '{',
        'r_delim' => '{',
        'trigger' => '',
    );

You can also set the delimiters and triggers via the following functions:

    $simpletags = new Simpletags();
    $simpletags->set_delimitiers('{', '}');
    $simpletags->set_trigger('foo:');

To parse a string of text you simply call the parse() function.  The parse function accepts 3 parameters:

1.  $content - The content to parse
2.  [optional] $data - a keyed array of data to replace tag vars with (more below)
3.  [optional] $callback - A callback that will be called for each tag.

### Normal Return

If no callback is specified then the function will return an array.  Consider this is the content you sent:

    Hello there.
    
    rest:get url="http://example.com/api" type="json"}
    Stuff here
    {/rest:get}
    
    Bye.

Parse would return this:

    Array
    (
        [content] => Hello there.

    marker_0k0dj3j4nJHDj22j

    Bye.
        [tags] => Array
            (
                [0] => Array
                    (
                        [full_tag] => {rest:get url="http://example.com/api" type="json"}
    Stuff here
    {/rest:get}
                        [attributes] => Array
                            (
                                [url] => http://example.com/api
                                [type] => json
                            )

                        [segments] => Array
                            (
                                [0] => rest
                                [1] => get
                            )

                        [content] => 
    Stuff here

                        [marker] => marker_0k0dj3j4nJHDj22j
                    )

            )

    )

### Using the Data Array

The data array is a keyed array who's contents will replace tags with the same name.  Example:

    {foo:bar}

Would be replaced with "Hello World" when the following data array is sent to the parse function:

    $data['foo']['bar'] = "Hello World"
    
You can use tag pairs to loop through data as well:

#### Tag:

    {books}
    {title} by {author}<br />
    {/books}

#### Data

    $data = array(
        'books' => array(
            array(
                'title' => 'PHP for Dummies',
                'author' => 'John Doe'
            ),
            array(
                'title' => 'CodeIgniter for Dummies',
                'author' => 'Jane Doe'
            )
        )
    );

#### Resulting Output

    PHP for Dummies by John Doe
    CodeIgniter for Dummies by Jane Doe

### Callbacks

The callback must be in a form that is_callable() accepts (typically array(object, method)).  The callbac function should take 1 parameter (an array).

The callback will be sent the tag information in the form of an array.  Consider the following 

    {rest:get url="http://example.com/api" type="json"}
    Stuff here
    {/rest:get}

Would send the callback function the following array:

    Array
    (
        [full_tag] => {rest:get url="http://example.com/api" type="json"}
    Stuff here
    {/rest:get}
        [attributes] => Array
            (
                [url] => http://example.com/api
                [type] => json
            )

        [segments] => Array
            (
                [0] => rest
                [1] => get
            )

        [content] => 
    Stuff here

        [marker] => marker_0k0dj3j4nJHDj22j
    )

##  CodeIgniter Usage

You use it the same as above, except you would use the following:

    $this->load->library('simpletags');
    $content = $this->simpletags->parse($content, $data, array($this, 'parser_callback'));
