<?php

// main routine

try
{ new includes_t;
  configure_logging();
  $main = new main_t;
  $main();
}
catch(Exception $e)
{ $log->error('exception-message: ' . $e->getMessage());
  $log->error('exception-file   : ' . $e->getFile());
  $log->error('exception-line   : ' . $e->getLine());
}

$log->info('main routine is to exit');
exit(0);

// detail

final class includes_t
{ public function __construct()
  {
    foreach($this as $a)
      require_once($a);
  }
  private $conf     = 'conf/conf.php';
  private $log4php  = 'include.external/log4php/Logger.php';
  private $smarty   = 'include.external/smarty/Smarty.class.php';
  private $helper   = 'include/helper.php';
  private $reciever = 'include/reciever_t.php';
  private $invoker  = 'include/invoker_t.php';
}

function configure_logging()
{ $log_file = './conf/log4php.xml';
  $logger   = 'main';
  
  Logger::configure($log_file);
  $GLOBALS['log'] = Logger::getLogger($logger);
}

final class main_t
{ private $reciever;
  static public $database;

  public function __construct()
  { global $log;
    $log->info('main_t::__construct');
    $this->reciever = new reciever_t;
    self::$database = new PDO(conf::database_dsn);
    $log->info('database is opened ( '.conf::database_dsn.' )');
  }
  public function __invoke()
  { global $log;
    
    $log->info('main_t::__invoke');
    
    $this->session_initialize();
    
    header('pragma: no-cache');
    header('cache-control: no-cache');
    
    if( array_key_exists('openid_mode', $_REQUEST) )
    {
      $log->info('catch auth result (session-id: '.session_id().' )');
      $c = unserialize($_SESSION['auth_suspended_command']);
      $c->auth_resume();
    }
    
    if( array_key_exists(conf::command_json_key, $_REQUEST) )
      $this->reciever->__invoke( $_REQUEST[conf::command_json_key] );
    else
      $this->view_top();
      
  }

  private function session_initialize()
  { global $log;
    $log->info(get_class($this).'::session_initialize');
    session_name(conf::session_name());
    $p = conf::session_save_path();
    if( ! is_dir($p) )
    {
      $log->warn('session directory is not exists, try create');
      if( ! mkdir($p) )
        throw new RuntimeException
                  ('can not create session directory: '.$p);
      $log->info('session directory is created: '.$p);
    }
    session_save_path(conf::session_save_path());
    session_start();
    $log->info('session_id: '.session_id());
    $log->debug('session: '. print_r($_SESSION, true));
  }

  private function view_top()
  { global $log;
    $s = new Smarty;
    $s->assign(conf::$default_template_params);
    $vs =
    [ 'script_client_main'
        => '<script src=\'/?'
         . conf::command_json_key
         . '={"c":"client_main"}\'></script>'
    , 'html_id_container_main'
        => conf::html_id_container_main
    ];
    $s->assign($vs);
    $s->display('top.html');
  }

  private function view_login()
  { global $log;
  }
}

