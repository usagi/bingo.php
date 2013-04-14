<?php

require_once('include/invoker_t.php');

new include_commands_t;

final class reciever_t
{ public function __invoke($command_json)
  { $GLOBALS['log']->info('reciever::__invoke');
    $this->try_invoke($command_json);
  }
  private function try_invoke($command_json)
  { global $log;
    $log->info('reciever::try_invoke');
    try
    { $j = json_decode($command_json);
      
      $f = function($a)
      { foreach($a as $k => $v)
          $r[$k] = $v;
        return $r;
      };
      
      $j = $f($j);
      $j_c  = 'c_'.$j[conf::command_json_command_key].'_t';
      
      if( ! class_exists($j_c) )
        throw new RuntimeException
          ('command class '.$j_c.' is not valid');
      if( ! array_key_exists('command_i', class_implements($j_c)) )
        throw new RuntimeException
          ('command class '.$j_c.' is not implements command_i');
      
      $command =
        (array_key_exists(conf::command_json_parameters_key, $j))
        ? new $j_c($j[conf::command_json_parameters_key])
        : new $j_c(null)
        ;
    }
    catch(Exception $e)
    { $log->warn('exception: '.$e->getMessage());
      $command = new c_error_t;
    }
    $i = new invoker_t;
    $i($command);
  }
}

final class include_commands_t
{ const include_dir = 'include';
  const pattern     = '';
  public function __construct()
  { $fs = scandir(self::include_dir);
    $fs = array_filter
      ( $fs
      , function($v)
        { return preg_match('/^c_.+_t\.php$/', $v);
        }
      );
    foreach($fs as $f){
      require_once($f);}
  }
}

