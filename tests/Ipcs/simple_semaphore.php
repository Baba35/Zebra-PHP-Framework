<?php
/**
 * Created by PhpStorm.
 * User: huyanping
 * Date: 14-8-23
 * Time: 上午11:53
 */
define('ZEBRA_ROOT', dirname(dirname(dirname(__FILE__))));
require ZEBRA_ROOT . DIRECTORY_SEPARATOR . 'Zebra.php';



declare(ticks=1); // This part is critical, be sure to include it
$manager = new \Zebra\MultiProcess\ProcessManager();
$manager->allocateSHMPerChildren(1000); // allocate 1000 bytes for each forked process
for($i=0;$i<4;$i++)
{
    $manager->fork(new \Zebra\MultiProcess\Process(function(Process $currentProcess) {
        $currentProcess->getShmSegment()->save('status', 'Processing data...');
        sleep(5);
        $currentProcess->getShmSegment()->save('status', 'Connecting to the satellite...');
        sleep(5);
    }, $i));
}
$manager->cleanupOnShutdown(); // Register shutdown function that will release allocated shared memory;
// It is important to call this after all fork() calls, as we don't want
// to release it when child process exits

do
{
    foreach($manager->getChildren() as $process)
    {
        $iid = $process->getInternalId();
        if($process->isAlive())
        {
            echo sprintf('Process %s is running with status "%s"', $iid, $process->getShmSegment()->fetch('status'));
        } else if($process->isFinished()) {
            echo sprintf('Process %s finished execution', $iid);
        }
        echo "\n";
    }
    sleep(1);
} while($manager->countAliveChildren());

$manager->cleanup(); // You can also call cleanup() manually if you want to