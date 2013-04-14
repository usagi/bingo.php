var command_json_key            = '{$command_json_key}';
var command_json_command_key    = '{$command_json_command_key}';
var command_json_parameters_key = '{$command_json_parameters_key}';
var container_main = null;

var command = function(c, ps, f)
{ 
  container_main = $('#{$html_id_container_main}');
  
  var command_json = {};
  command_json[command_json_command_key]    = c;
  if(ps !== void 0)
    command_json[command_json_parameters_key] = ps;
  
  var q = {};
  q[command_json_key] = JSON.stringify(command_json);
  
  var f_default = function(r)
  {
    r = JSON.parse(r);
    
    if(r['has_error'])
      console.log('command error:', r['error']);
    
    if(r['require_auth'])
    {
      location.href = r['auth_url'];
      return;
    }
    
    if(typeof f === typeof(function(){}))
      if(f(r) === false)
        return;
    
    if(r['require_change_view'])
    {
      window.history.pushState(null,null,location.origin);
      container_main.html(r['view']);
    }
  }
  
  $.post('/', q, f_default);
};

$(function(){
  command('{$first_command}');
});
