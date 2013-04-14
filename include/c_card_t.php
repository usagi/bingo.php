<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_card_t
  extends c_auth_t
{ private $ps;
  private $fnw = false;
  private $table;

  public function force_no_wheel($a = true)
  { $this->fnw = $a; }

  public function construct($parameters)
  { global $log;
    
    $log->info(get_class($this).'::construct');
    
    if(is_null($parameters))
      $parameters = [];
    else
      to_array($parameters);
    
    $this->ps =
      [ 'name'   => array_key_exists('name', $parameters)
                      ? $parameters['name']
                      : $_SESSION['user_name']
      , 'option' => array_key_exists('option', $parameters)
                      ? $parameters['option']
                      : null
      , 'number' => array_key_exists('number', $parameters)
                      ? $parameters['number']
                      : null
      ];
    
    $this->table = 'card_'.$this->ps['name'];
  }
  
  public function invoke()
  { global $log;
    $log->info(get_class($this).'::invoke');
    
    $this->prepare();
    
    if(is_null($this->ps['option']))
      return $this->read();
    
    if( (! $this->fnw) && (! $_SESSION['is_wheel']) )
      throw new RuntimeException('require wheel role');
    
    return $this->write();
  }

  private function prepare()
  { global $log;
    $log->info(get_class($this).'::prepare');
    
    $s = main_t::$database->prepare
      ( 'select count(*)'
      . ' from sqlite_master'
      . ' where type = \'table\''
      . ' and name = ?'
      );
    
    if($s === false)
      throw new RuntimeException('PDO prepare failed');
    
    $s->execute( [ $this->table ] );
    
    if($s->fetch()[0] == 0)
    {
      $log->info('create table: '.$this->table);
      $s = main_t::$database->prepare
        ( 'create table '
        . $this->table
        . ' ( x integer not null'
        . ' , y integer not null'
        . ' , number integer not null'
        . ' , is_punched integer not null default 0'
        . ')'
        );
      
      if($s === false)
        throw new RuntimeException('PDO prepare failed');
      
      $s->execute();
    }
  }

  private function read()
  { global $log;
    $log->info(get_class($this).'::read');
    
    $s = main_t::$database->query('select * from '.$this->table);
    $f = $s->fetchAll(PDO::FETCH_NUM);
    
    $r = new command_return_t;
    $r->return = $f;
    return $r;
  }

  private function write()
  { global $log;
    $log->info(get_class($this).'::write');
    
    switch($this->ps['option'])
    {
    case 'reset':
      $is_succeeded = $this->write_reset();
      break;
    //case 'punch':
    default:
      throw new RuntimeException('unkown option');
      //$is_succeeded = $this->write_punch();
      break;
    }
    
    $log->info('is succeeded: '.var_export($is_succeeded, true));
    
    $r = new command_return_t;
    $r->return = $is_succeeded;
    return $r;
  }

  private function write_reset()
  { global $log;
    // generate new card
    $bingo_numbers = range
      ( conf::bingo_number_min
      , conf::bingo_number_max
      );
    mt_shuffle($bingo_numbers);
    $bingo_size_sq = conf::bingo_size * conf::bingo_size;
    $card_numbers = array_slice
      ( $bingo_numbers
      , 0
      , $bingo_size_sq
      );
    $fa = function($a){ return       $a % conf::bingo_size ; };
    $fb = function($b){ return (int)($b / conf::bingo_size); };
    $card = array_map
      ( null
      , array_map($fa, range(0, $bingo_size_sq - 1))
      , array_map($fb, range(0, $bingo_size_sq - 1))
      , $card_numbers
      );
    if(conf::bingo_free)
      $card[$bingo_size_sq >> 1][2] = 0;
    
    // apply to the database
    main_t::$database->beginTransaction();
    //$s = main_t::$database->prepare('truncate '.$this->table);
    $s = main_t::$database->prepare('delete from '.$this->table);
    $s->execute();
    $s = main_t::$database->prepare
      ( 'insert into '
      . $this->table
      . ' values( ? , ? , ? , 0 )'
      );
    foreach($card as $cell)
      $s->execute( [ $cell[0], $cell[1], $cell[2] ] );
    
    if( ! main_t::$database->commit())
      throw new RuntimeException('database commit failed');
    
    return true;
  }
}

