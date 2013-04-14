<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_wheel_t
  extends c_auth_t
{ private $ps;
  public function construct($parameters)
  { if( is_null($parameters) )
      return;
    
    if( ! array_key_exists('name', $parameters) )
      throw new InvalidArgumentException();
    
    $this->ps =
      [ 'name'   => $parameters->name
      , 'option' => array_key_exists('option', $parameters)
                      ? $parameters->option
                      : null
      ];
  }
  
  public function invoke()
  { if( ! $_SESSION['is_wheel'] )
      throw new RuntimeException('require wheel role');
      
    return is_null($this->ps)
      ? $this->read()
      : $this->write()
      ;
  }

  private function read()
  { $s = main_t::$database->query('select * from wheel');
    
    $users = $s->fetchAll();
    
    $r = new command_return_t;
    $r->return = $users;
    return $r;
  }

  private function write()
  { global $log;

    if($this->ps['option'] === 'remove')
      $q = 'delete from wheel where name=\''
         . $this->ps['name']
         .'\'';
    else
      $q = 'insert or replace into wheel '
         . 'values(\''.$this->ps['name'].'\')'
         ;
    
    $is_succeeded = true;
    
    try
    { $s = main_t::$database->query($q);
    }
    catch(Exception $e)
    { $is_succeeded = false;
    }
    
    $r = new command_return_t;
    $r->return = $is_succeeded;
    return $r;
  }
}

