<?php
namespace Ds\Foundations\Commands\CronJob;

use Ds\Foundations\Commands\Console;
use Ds\Foundations\Commands\Runner;
use Ds\Foundations\Common\Func;

class Worker extends Runner {
  public function run(){
    $length = count($this->options);
    
    if($length == 0){
      foreach (glob(ROOT.'cronjob'.SLASH.'*.job.php') as $filename) {
        $clearFilename = substr($filename, strrpos($filename,SLASH) + 1);
        $className = substr($clearFilename,0, strpos($clearFilename, '.'));
        $this->doJob($className);
      }
    }else{
      foreach ($this->options as $jobName) {
        $this->doJob($jobName);
      }
    }
  }
  private function doJob($jobName){
    require ROOT.'cronjob'.SLASH.$jobName.'.job.php';
    Console::writeln($jobName.' Started !', Console::BLUE);
    $job = new $jobName();
    $job->run();
    Console::writeln("\t".$jobName . ' Job Completed!', Console::GREEN);
    Console::writeln(" ");
  }
}