<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_lot_t
  extends c_auth_t
{ private $ps;
  private $fnw = false;

  public function force_no_wheel($a = true)
  { $this->fnw = $a; }

  public function construct($parameters)
  {
    if(is_null($parameters))
      $parameters = [];
    else
      to_array($parameters);
    
    $this->ps =
      [ 'option' => array_key_exists('option', $parameters)
                      ? $parameters['option']
                      : null
      ];
  }
  
  public function invoke()
  { global $log;
    $log->info(get_class($this).'::invoke');
    
    $this->prepare();
    
    switch($this->ps['option'])
    {
    case 'reset':
      if( (! $this->fnw) && (! $_SESSION['is_wheel']) )
        throw RuntimeException('need wheel role');
      $is_succeeded = $this->reset();
      break;
    case 'lot':
      if( (! $this->fnw) && (! $_SESSION['is_wheel']) )
        throw RuntimeException('need wheel role');
      $is_succeeded = $this->lot();
    case 'status':
    default:
      $is_succeeded = $this->status();
      break;
    }
    
    //$log->info('is succeeded: '.var_export($is_succeeded, true));
    
    $r = new command_return_t;
    $r->return = $is_succeeded;
    return $r;
  }

  private function prepare()
  { global $log;
    $log->info(get_class($this).'::prepare');
    
    $s = main_t::$database->query
      ( 'select count(*)'
      . ' from sqlite_master'
      . ' where type = \'table\''
      . ' and name = \'lotter\''
      );
    
    if($s === false)
      throw new RuntimeException('PDO prepare failed');
    
    if($s->fetch()[0] == 0)
    {
      $log->info('create table: lotter');
      $s = main_t::$database->query
        ( 'create table '
        . ' lotter'
        . ' ( number integer not null primary key'
        . ' , is_popped integer not null default 0'
        . ')'
        );
      
      if($s === false)
        throw new RuntimeException('PDO prepare failed');
    }
    
    $s = main_t::$database->query
      ( 'select count(*)'
      . ' from sqlite_master'
      . ' where type = \'table\''
      . ' and name = \'lotter_history\''
      );
    
    if($s === false)
      throw new RuntimeException('PDO prepare failed');
    
    if($s->fetch()[0] == 0)
    {
      $log->info('create table: lotter_history');
      $s = main_t::$database->query
        ( 'create table '
        . ' lotter_history'
        . ' ( sequence integer primary key autoincrement'
        . ' , number integer not null'
        . ')'
        );
      
      if($s === false)
        throw new RuntimeException('PDO prepare failed');
    }
  }

  private function reset()
  { global $log;
    
    $bingo_numbers = range
      ( conf::bingo_number_min
      , conf::bingo_number_max
      );
    
    main_t::$database->beginTransaction();
    main_t::$database->query('delete from lotter');
    //main_t::$database->query('delete from lotter_history');
    main_t::$database->query('drop table lotter_history');
    $this->prepare();
    $s = main_t::$database->prepare
      ( 'insert into lotter values( ? , 0 )' );
    foreach($bingo_numbers as $n)
      $s->execute( [ $n ] );
    if(conf::bingo_free)
    {
      if( main_t::$database->query
            ('insert or replace into lotter values(0,1)')
          === false
      )
        throw new RuntimeException('bingo free apply failed');
      
      $s = main_t::$database->query
        ( 'insert into lotter_history(number) values(0)' );
      
      if( $s === false )
        throw new RuntimeException('database query failed');
    }
    
    if( main_t::$database->commit() === false )
      throw new RuntimeException('database commit failed');
    
    $this->lot_apply_cards();
    
    return true;
  }

  private function lot()
  { global $log;
    
    $log->info(get_class($this).'::lot');
    
    $s = main_t::$database->query
      ( 'select number from lotter'
      . ' where is_popped = 0'
      . ' order by random()'
      . ' limit 1'
      );
    
    if( $s === false )
      throw new RuntimeException('database query failed');
    
    $fetched = $s->fetch(PDO::FETCH_NUM);
    if(count($fetched) == 0)
      return false;
    
    $n = $fetched[0];
    $log->info('lot number: '.$n);
    
    $q = 'update lotter'
       . ' set is_popped = 1'
       . ' where number = '.$n
       ;
    $log->debug($q);
    $s = main_t::$database->query($q);
    
    if( $s === false )
      throw new RuntimeException('database query failed');
    
    $s = main_t::$database->query
      ( 'insert into lotter_history(number) values('.$n.')' );
    
    if( $s === false )
      throw new RuntimeException('database query failed');
    
    return $this->lot_apply_cards();
  }

  private function lot_apply_cards()
  { global $log;
    
    $log->info(get_class($this).'::lot_apply_cards');
    
    $s = main_t::$database->query
          ('select number from lotter where is_popped = 1');
    
    if($s === false)
      throw new RuntimeException('database query failed');
    
    $numbers = $s->fetchAll(PDO::FETCH_NUM);
    
    $s = main_t::$database->query
          ('select name from sqlite_master where name like \'card_%\'');
    
    if($s === false)
      throw new RuntimeException('database query failed');
    
    $cards = $s->fetchAll(PDO::FETCH_NUM);
    
    main_t::$database->beginTransaction();
    foreach($numbers as $n)
      foreach($cards as $r)
      {
        $q = 'update '.$r[0]
           . ' set is_punched = 1'
           . ' where number = '.$n[0]
           ;
        $log->debug($q);
        $s = main_t::$database->query($q);
      }
    main_t::$database->commit();
    
    return $this->lot_apply_players();
  }

  private function lot_apply_players()
  { global $log;
    
    $log->info(get_class($this).'::lot_apply_players');
    
    $s = main_t::$database->query
          ('select * from players');
    
    if($s === false)
      throw new RuntimeException('database query failed');
    
    $players = $s->fetchAll(PDO::FETCH_NUM);
    
    foreach($players as $player)
    {
      $player_name = $player[0];
      
      $bingo = 0;
      $reach = 0;
      
      $f = function(&$bingo, &$reach, &$player_name, $p1, $p2)
        {
          $s = main_t::$database->query
            ( 'select count(*) from card_'
            . $player_name
            . ' where is_punched = 1 and '.$p1.' = '.$p2
            );
          
          if($s === false)
            throw new RuntimeException('database query failed');
          
          switch($s->fetch(PDO::FETCH_NUM)[0])
          {
          case 5: ++ $bingo; break;
          case 4: ++ $reach; break;
          }
        };
      
      $m = conf::bingo_size - 1;
      
      foreach(range(0, $m) as $n)
      { 
        $f($bingo, $reach, $player_name, 'x', $n);
        $f($bingo, $reach, $player_name, 'y', $n);
      }
      
      $f($bingo, $reach, $player_name, 'x',       'y');
      $f($bingo, $reach, $player_name, 'x', $m.' - y');

      $log->info
        ( 'player: '.$player_name
        . ' , bingo: '.$bingo
        . ' , reach: '.$reach
        );

      $s = main_t::$database->query
        ( 'update players set'
        . ' n_reach = '.$reach
        . ', n_bingo = '.$bingo
        . ' where name = \''.$player_name.'\''
        );

      if($s === false)
        throw new RuntimeException('database query failed');
    }
    
    return true;
  }

  private function status()
  { global $log;
    
    $log->info(get_class($this).'::status');
    
    $s = main_t::$database->query('select * from lotter');
    if($s === false)
      throw new RuntimeException('database query failed');
    $ra = $s->fetchAll(PDO::FETCH_NUM);
    
    $s = main_t::$database->query('select * from lotter_history');
    if($s === false)
      throw new RuntimeException('database query failed');
    $rb = $s->fetchAll(PDO::FETCH_NUM);
    
    return [$ra, $rb];
  }
}
