<?
#require_once('xorc/simpletest/simpletest.php');
#require_once('xorc/simpletest/web_tester.php');
require_once('xorc/simpletest/unit_tester.php');
require_once('xorc/simpletest/reporter.php');
include_once("xorc/db/xorcstore_fixtures.class.php");

$test = &new GroupTest('All Tests');

$testdir=dirname($mypath)."/tests/test_*.php";
print "TESTS in $testdir\n";

foreach(glob($testdir) as $tf){
   print "adding $tf\n";
   $test->addTestFile($tf);
}

XorcStore_Connector::set("_db", array('dsn'=>$opts['db'], 'prefix'=>$opts['prefix']));

exit ($test->run(new TextReporter()) ? 0 : 1);
    
?>