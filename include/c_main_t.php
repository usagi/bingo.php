<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_main_t
  extends c_auth_t
{ protected function invoke()
  { global $log;
    
    $q = 'select count(*) from players where name=\''
       . $_SESSION['user_name']
       . '\''
       ;
    $t = main_t::$database->query($q);
    
    if($t->fetch()[0] !== 1)
    {
      $params = [ 'name' => $_SESSION['user_name'] ];
      $p = new c_player_t($params);
      $p->force_no_wheel(true);
      $p();
    }
    
    $s = new Smarty();
    
    if(conf::is_debug_mode)
    {
      try
      {
        $f = function($s, $q, $a)
        {
          $t = main_t::$database->query($q);
          if($t === false)
            throw new RuntimeException('database query is failed');
          $s->assign($a, $t->fetchAll());
         };
        
        foreach(array('players','lotter') as $v)
          $f($s, 'select * from '.$v , $v);
      }
      catch(Exception $e)
      { throw $e;
      }
    }

    $d = null;

    $s->assign(conf::$default_template_params);
    $v = $s->fetch('main.html');
    
    $r = new command_return_t;
    $r->require_change_view = true;
    $r->view                = $v;
    return $r;
  }
}

