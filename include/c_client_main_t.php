<?php

require_once('command_i.php');
require_once('command_return_t.php');

final class c_client_main_t
  implements command_i
{ public function __construct($parameters)
  {
  }
  public function __invoke()
  { header('content-type: text/ecmascript');
    $s = new Smarty();
    $s->assign(conf::$default_template_params);
    if(array_key_exists('first_command', $_SESSION))
    {
      $s->assign('first_command', $_SESSION['first_command']);
      unset($_SESSION['first_command']);
    }
    $vs =
    [ 'command_json_key'            => conf::command_json_key
    , 'command_json_command_key'    => conf::command_json_command_key
    , 'command_json_parameters_key' => conf::command_json_parameters_key
    , 'html_id_container_main'      => conf::html_id_container_main
    ];
    $s->assign($vs);
    $v = $s->display('client_main.js');
  }
}

