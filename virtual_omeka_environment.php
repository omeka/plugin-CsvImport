<?php
/*
(12:51:51 PM) jimchnm: hey kris, i see that you modified the bootstrap process a bit
(12:51:55 PM) jimchnm: quite nice
(01:36:19 PM) chnmkris: thanks
(01:36:41 PM) chnmkris: i modified the shit outta that shit
(01:36:50 PM) jimchnm: yes, yes you did
(01:36:53 PM) chnmkris: shit
(01:37:11 PM) chnmkris: the trick is to change it often enough that no one can figure it out
(01:37:24 PM) chnmkris: i'm joking, actually
(01:37:27 PM) chnmkris: i hope its easier to read
(01:37:29 PM) jimchnm: anything to confuse open collection
(01:37:36 PM) chnmkris: yeah get them off the trail
(01:37:42 PM) chnmkris: i'm thinking about coding portions in lolcode
(01:37:44 PM) jimchnm: oh hell yes, much better
(01:38:06 PM) jimchnm: hmmm, is it capable of "phased" loading, like we were talking about?
(01:38:15 PM) chnmkris: more than it was
(01:38:30 PM) chnmkris: the order in which they are called is still important
(01:38:42 PM) chnmkris: but basically you could just initialize an Omeka_Core instance
(01:38:48 PM) chnmkris: and then run initializeDb() on it
(01:38:52 PM) chnmkris: and i think you'd be good to go
(01:38:57 PM) jimchnm: but you can't instanciate the class without initializing everthing
(01:39:13 PM) chnmkris: require_once 'Omeka/Core.php'
(01:39:18 PM) chnmkris: assuming its in your path
(01:39:38 PM) chnmkris: the database connection itself doesn't use any plugin hooks
(01:39:40 PM) jimchnm: ahhh! i see now
(01:39:43 PM) chnmkris: so you don't need to initialize that part
(01:39:59 PM) chnmkris: i think it should work, but i'm not totally sure
(01:40:12 PM) chnmkris: if you want to test it, feel free, then it would be possible to fix it more
(01:40:14 PM) jimchnm: i'ma gonna try it our, yo
(01:40:22 PM) chnmkris: ok cool
(01:45:17 PM) jimchnm: i don't think it'll work... Omeka_Core is in /application/core/core.php and it's bundled with the full initialize() method
(01:45:29 PM) chnmkris: ahhhhhh
(01:45:37 PM) chnmkris: ok I can fix that no problem
(01:45:46 PM) jimchnm: you smell like god
(01:45:49 PM) chnmkris: haha
(01:46:09 PM) chnmkris: i'll probably just move that class to its own file so it follows Zend conventions
(01:46:25 PM) chnmkris: then move those other lines to another file and get rid of core.php entirely
(01:46:37 PM) jimchnm: who needs it anyway
(01:46:40 PM) chnmkris: right
(01:46:43 PM) chnmkris: its stupid and retarded
(01:46:59 PM) jimchnm: durrrr
(03:41:41 PM) chnmkris: hmm
(03:41:56 PM) jimchnm: i agree
(03:41:56 PM) chnmkris: i'm having problems getting it to work perfectly
(03:42:09 PM) chnmkris: but i have gotten it to work better
(03:42:23 PM) jimchnm: and that's all we can ask of you
(03:42:28 PM) chnmkris: right now i have it so that this is all you have to do:
(03:42:31 PM) chnmkris: require_once 'omeka/paths.php';
require_once 'omeka/application/libraries/Omeka/Core.php';

$core = new Omeka_Core;
$core->initialize();

$db = $core->getDb();
(03:42:42 PM) chnmkris: assuming you are outside the 'omeka' directory
(03:43:09 PM) jimchnm: have you committed it?
(03:43:12 PM) chnmkris: not yet
(03:43:17 PM) chnmkris: going to test it a bit more
(03:43:36 PM) jimchnm: if nothing else you are thorough
(03:43:45 PM) chnmkris: thanks
(04:19:10 PM) chnmkris: k i committed some stuff
(04:19:18 PM) chnmkris: you should be able to run that snippet i gave you above
(04:19:28 PM) jimchnm: rock
(04:35:28 PM) chnmkris: ok i found a couple more problems
(04:35:44 PM) chnmkris: at least, with running commandline scripts
(04:36:25 PM) chnmkris: 1) code utilizing sessions will be messed up
(04:36:42 PM) chnmkris: 2) code using web paths (such as url generation) will be messed up good
(04:36:56 PM) chnmkris: but stuff that just interacts with the db is fine
(04:37:05 PM) jimchnm: yes, sessions are not permitted on the command line
(04:38:14 PM) jimchnm: and don't worry about functionality whose sole purpose is web output... these will be useless to most if not all of the command line scripts
(04:38:39 PM) jimchnm: most will only need the db anyway
(04:38:47 PM) chnmkris: that is good
(04:39:04 PM) jimchnm: but it would be wonderful if we had access to the models
(04:39:09 PM) chnmkris: yes you do
(04:39:28 PM) chnmkris: this works
(04:39:29 PM) chnmkris: :
(04:39:35 PM) chnmkris: require_once 'omeka/paths.php';
require_once 'omeka/application/libraries/Omeka/Core.php';

$core = new Omeka_Core;
$core->initialize();

$db = $core->getDb();

$items = $db->getTable('Item')->findBy(array('public'=>true));

foreach ($items as $item) {
    echo $item->title;
}
(04:40:03 PM) jimchnm: my god that's elegant
(04:40:09 PM) chnmkris: why thank you
(04:42:09 PM) chnmkris: does that count as phased loading, or did you have something more complex in mind?
(04:42:21 PM) chnmkris: i saw the drupal code, it looks pretty complicated
(04:42:26 PM) chnmkris: but i'm going to avoid that for now, i think
(04:42:47 PM) jimchnm: for my purposes this is phased enough
(04:42:58 PM) chnmkris: ok cool
*/

// Attempting to create a limited Omeka environment in order to establish a db connection, gain access to the libraries, etc., etc.

// Set the base directory by removing the path to the CsvImport directory from the Omeka root.
$baseDirectory = str_replace(DIRECTORY_SEPARATOR . 'plugins'. DIRECTORY_SEPARATOR .'CsvImport', '', dirname(__FILE__));

// Require the paths script from the base directory. This defines the necessary directory and web paths.
require_once $baseDirectory . DIRECTORY_SEPARATOR. 'paths.php';

// Set an include path to the application libraries directory.
set_include_path($baseDirectory . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'libraries');

// Require the globals script from the libraries directory. This contains useful global library functions. get_db() is here.
require_once 'globals.php';

// Include the Omeka script from the libraries directory. This contains the Omeka class.
require_once 'Omeka.php';

// Register the autoload method in the Omeka class as __autoload. See: http://us.php.net/function.spl_autoload_register
spl_autoload_register(array('Omeka', 'autoload'));

// Require the Zend/Registry script from the libraries directory. This contains the Zend_Registry class.
require_once 'Zend/Registry.php';

// Register the various path names so they can be accessed by the app
Zend_Registry::set('path_names', $site);

// Require the Zend/Config/Ini script fomr the libraries directory. This contains the Zend_Config_Ini class.
require_once 'Zend/Config/Ini.php';

// Set the database configuration file path.
$db_file = CONFIG_DIR . DIRECTORY_SEPARATOR . 'db.ini';

// Perform checks on the database configuration file.
if (!file_exists($db_file)) {
    throw new Exception('Your Omeka database configuration file is missing.');
}
if (!is_readable($db_file)) {
    throw new Exception('Your Omeka database configuration file cannot be read by the application.');
}

// Extract the settings from the database configuration file.
$db = new Zend_Config_Ini($db_file, 'database');

// Register the database configuration settings.
Zend_Registry::set('db_ini', $db);

//Fail on improperly configured db.ini file
if (!isset($db->host) or ($db->host == 'XXXXXXX')) {
    throw new Exception('Your Omeka database configuration file has not been set up properly.  Please edit the configuration and reload this page.');
}

// Begin building the Data Source Name (DSN).
$dsn = 'mysql:host='.$db->host.';dbname='.$db->name;

// Add the port to the DSN if available.
if (isset($db->port)) {
    $dsn .= "port=" . $db->port;
}

$dbh = Zend_Db::factory('Mysqli', array(
    'host'     => $db->host,
    'username' => $db->username,
    'password' => $db->password,
    'dbname'   => $db->name
));

$db_obj = new Omeka_Db($dbh, $db->prefix);

Zend_Registry::set('db', $dbh);

// SUCCESS! GOT THE DB OBJECT INDEPENDENT FROM THE OMEKA SYSTEM!
//print_r(get_db());