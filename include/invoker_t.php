<?php

final class invoker_t
{ public function __invoke($command)
  { $r = $command();
    $j = json_encode($r);
    $GLOBALS['log']->info('to exit with JSON output: '.$j);
    echo($j);
    exit(0);
  }
  private function try_invoke($command, $parameters)
  {
    try
    { $r = $command();
    }
    catch(Exception $e)
    { $a = new command_error_t;
      $a->what       = 'exception: '.$e->getMessage();
      $a->command    = $command;
      $a->parameters = $parameters;
      $a->time       = date('c');
      $r = new command_return_t;
      $r->error = $a;
    }
    return $r;
  }
}

