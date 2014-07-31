SpindleConnector
README
@author Rob Little <rlittle@spindle.com>
@copyright Spindle Inc. 2013-2014

------------------------------------------------------------------------------

General Information
This class allows PHP developers to connecto to Spindle for billing purposes.

It emulates the functionality available in the C# connector. Using this class,
you will be able to call the same functionality in PHP.

Because this class emulates the C# functionality, any questions about
parameter order or function return objects, please refer to the C# implemen-
tation available at: http://wiki.spindlehq.com/display/API/API+Call+List .

How to Use
Using this class is very straightforward and simple. Include the class file,
create a local variable and assign it to a new SpindleConnector.

Example:

/* 
 * Setup Variables
 * Instantiate the connector
 */

define('PRIVATE_KEY', 'SOME+PRIVATE+KEY+PROVIDED+BY\SPINDLE');
define('CID', 'SPINDLE-PROVIDED-CID');
define('SID', 'SPINDLE-PROVIDED-SID');
define('USERNAME', 'SPINDLE-USERNAME');
define('PASSWORD', 'SPINDLE-PASSOWRD');


 $spc = new Spindle\SpindleConnector(PRIVATE_KEY, CID, SID, USERNAME, PASSWORD);


 /* 
  * Get a session response from the service.
  * There are no parameters required for this function.
  */
  $sesssion_id = $spc->CreateSession();




