<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_reset_t
  extends c_auth_t
{ private $ps;
  public function construct($parameters)
  { 
  }
  
  public function invoke()
  { global $log;
    
    $log->info(get_class($this).'::invoke');
    
    if( ! $_SESSION['is_wheel'] )
      throw new RuntimeException('require wheel role');
    
    $s = main_t::$database->query('select name from players');
    
    if($s === false)
      throw new RuntimeException('database query failed');
    
    $players = $s->fetchAll(PDO::FETCH_NUM);
    
    $cs = [];
    
    foreach($players as $player)
    {
$log->debug($player[0]);
      $c = new c_card_t
        ( [ 'option' => 'reset'
          , 'name'   => $player[0]
          ]
        );
      $c->force_no_wheel();
      array_push($cs, $c);
    }
    
    $c = new c_lot_t (['option' => 'reset']);
    $c->force_no_wheel();
    array_push($cs, $c);
    
    $r = true;
    
    foreach($cs as $c)
      $r |= $c() !== false;
    
    return $r;
  }

}

