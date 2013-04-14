<?php

require_once('command_i.php');
require_once('command_return_t.php');
require_once('command_error_t.php');

final class c_error_t
  implements command_i
{ private $ps = null;
  public function __construct($parameters)
  { $this->ps = $parameters;
  }
  public function __invoke()
  { $e = new command_error_t;
    $e->what       = 'command is not found';
    $e->command    = get_class($this);
    $e->parameters = $this->ps;
    $e->time       = date('c');
    $r = new command_return_t;
    $r->has_error = true;
    $r->error     = $e;
    return $r;
  }
}

