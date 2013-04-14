<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_logout_t
  extends c_auth_t
{
  protected function invoke()
  { global $log;
    
    $log->info(get_class($this).'::invoke');
    $log->info('to logout (session-id: '.session_id().' )');
    
    session_destroy();
    
    $s = new Smarty();
    
    $s->assign(conf::$default_template_params);
    $v = $s->fetch('login.html');
    
    $r = new command_return_t;
    $r->require_change_view = true;
    $r->view                = $v;
    return $r;
  }
}

