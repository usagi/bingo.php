<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_player_t
  extends c_auth_t
{ private $ps;
  private $fnw = false;

  public function force_no_wheel($a)
  { $this->fnw = $a; }

  public function construct($parameters)
  { if( is_null($parameters) )
      return;
    else
      to_array($parameters);
    
    if( ! array_key_exists('name', $parameters) )
      throw new InvalidArgumentException();
    
    $this->ps =
      [ 'name'   => $parameters['name']
      , 'option' => array_key_exists('option', $parameters)
                      ? $parameters['option']
                      : null
      ];
  }
  
  public function invoke()
  {
    if(is_null($this->ps))
      return $this->read();
    
    if( (! $this->fnw) && (! $_SESSION['is_wheel']) )
      throw new RuntimeException('require wheel role');
    
    return $this->write();
  }

  private function read()
  { $s = main_t::$database->query('select * from players');

    $f = $s->fetchAll();
    
    $r = new command_return_t;
    $r->return = $f;
    return $r;
  }

  private function write()
  { global $log;
    
    $log->info(get_class($this).'::write');
    
    $is_succeeded = true;
    
    if($this->ps['option'] === 'remove')
    {
      $q = 'delete from players where name=\''
         . $this->ps['name']
         .'\'';
      $s = main_t::$database->query($q);
      if($s === false)
        throw new RuntimeException('database query failed');
    }
    else
    {
      $q = 'select count(*) from players'
         . ' where name = \''.$this->ps['name'].'\''
         ;
      $s = main_t::$database->query($q);
      if($s === false)
        throw new RuntimeException('database query failed');
      
      if($s->fetch(PDO::FETCH_NUM)[0] == 0)
      {
        $q = 'insert into players values(\''
           . $this->ps['name']
           . '\',0,0)'
           ;
        $s = main_t::$database->query($q);
        if($s === false)
          throw new RuntimeException('database query failed');
      }
    }
    
    $log->info('is succeeded: '.var_export($is_succeeded, true));
    
    $r = new command_return_t;
    $r->return = $is_succeeded;
    return $r;
  }
}

