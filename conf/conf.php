<?php

final class conf
{ const is_debug_mode = true;
  const system_name   = 'Bingo!';
  
  const bingo_number_min =   1;
  const bingo_number_max = 100;
  const bingo_size       =   5;
  const bingo_free       = true;
  
  public static $default_template_params =
  [ 'debug'            => self::is_debug_mode
  , 'system_name'      => self::system_name
  , 'copyright_year'   => 2013
  , 'copyright_author' => 'Wonder Rabbit Project / Usagi Ito'
  , 'first_command'    => 'login'
  , 'is_wheel'         => false
  ];
  
  const command_json_key            = 'c';
  const command_json_command_key    = 'c';
  const command_json_parameters_key = 'p';
  
  const html_id_container_main      = 'm';
  
  const database_dsn = 'sqlite:database.sqlite3/main';
  
  public static function session_name()
  { return 'session_'.self::system_name; }
  public static function session_save_path()
  { return getcwd().'/sessions'; }
}

