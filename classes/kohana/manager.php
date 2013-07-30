<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana user guide and api browser.
 *
 * @package    Kohana/Manager
 * @category   Modules
 * @author     Andrew Scherbakov
 */
class Kohana_Manager
{

  /**
   * ATTR_LOW_EVENT_PRIORITY 
   * 
   * @const int
   */
  const ATTR_LOW_EVENT_PRIORITY         = 100;

  /**
   * ATTR_NORMAL_EVENT_PRIORITY 
   * 
   * @const int
   */
  const ATTR_NORMAL_EVENT_PRIORITY      = 50;

  /**
   * ATTR_HIGH_EVENT_PRIORITY 
   * 
   * @const int
   */
  const ATTR_HIGH_EVENT_PRIORITY        = 10;

  /**
   * ATTR_EXPRESS_EVENT_PRIORITY 
   * 
   * @const int
   */
  const ATTR_EXPRESS_EVENT_PRIORITY     = 0;

  /**
   *
   * Enter description here ...
   *
   * @var Kohana_Manager
   */
  private static $instance = NULL;

  /**
   * @var array $events
   * @static
   */
  private static $events = array();
  
  /**
   *
   * Enter description here ...
   *
   * @var array
   */
  private $actions = array();

  /**
   * Gets params of query
   *
   * @var array
   */
  private $gets = array();

  /**
   * Initial query
   * @var null
   */
  private $_uri = null;

  /**
   *
   * Enter description here ...
   *
   * @var Config
   */
  private $config = null;
  
  /**
   *
   * @var boolean
   */
  private $init = true;
  
  /**
   *
   * Enter description here ...
   *
   * @var View|NULL
   */
  private $template = null;

  /**
   * @var HTTP_Cache|NULL
   */
  private $cache = null;

  // private instance(uri='',array gets=array()) {{{ 
  /**
   * instance
   * 
   * @param string $uri 
   * @param array $gets 
   * @static
   * @access private
   * @return void
   */
  private static function instance( $uri = '', array $gets = null )
  {
    if ( self::$instance === null ) self::$instance = new self( $uri, (array)$gets );
    return self::$instance;
  }
  // }}}

  // private init(uri,array gets) {{{ 
  /**
   * init
   * 
   * @param string  $uri 
   * @param array   $gets 
   * @access private
   * @return void
   */
  private function init( $uri, array $gets ){
    $this->_uri = $uri;
    if ( Utf8::strpos( $uri, '?') !== false){
      $uri = explode( '?', $uri);
      if ( $uri[0] == '' && count($uri) > 1)
        array_shift($uri);
      parse_str( $uri[1], $gets );
      if ( !Request::initial() )
        parse_str($uri[1], $_GET);
    }
    else $uri = array($uri);
    $actions = explode('/', $uri[0]);
    if ( $actions[0] == '' && count($actions) > 1)
      array_shift($actions);
    $this->actions = $actions;
    $this->config  = Kohana::$config->load('manager');
    if ( !Request::initial() )
      $this->gets  = Arr::merge( $_GET, $gets );
    else
      $this->gets    = $gets;
  }
  // }}}

  // private __construct(uri,array gets) {{{ 
  /**
   * __construct
   * 
   * @param mixed $uri 
   * @param array $gets 
   * @access private
   * @return void
   */
  private function __construct( $uri, array $gets )
  {
    $this->init( $uri, $gets );
  }
  // }}}

  /**
   *
   * Enter description here ...
   *
   * @param boolean $exp
   * @param string $msg
   *
   * @throws Request_Exception
   */
  private function ensure($exp, $msg)
  {
    if ($exp) throw new Request_Exception($msg);
  }

  /**
   * @return array
   */
  private function doParse($uri)
  {
    if ($this->init) {
      $this->init = false;
      $directory  = $this->config->get('views');
      $directory  = $directory['path'];

      $query = '';
      $i=1;
      while($i < count($this->actions)-1)
        $query .= $this->actions[$i++] . '/';
      if ( isset( $this->actions[$i]))
        $query .= $this->actions[$i];
      if (strlen($directory) ) $directory .= DIRECTORY_SEPARATOR;
      if (empty($this->actions[0])) {
        $directory .= 'index';
        $result = array(
          'controller' => 'main',
          'action'     => 'index',
          'position'   => 0,
          'directory'  => $directory,
          'query'      => $query,
          '_event'     => 'index',
        );
      }
      else {
        $sid = URL::title( $this->actions[0], '', true);
        $directory .= $sid;
        $result = array(
          'controller' => 'main',
          'action'     => 'index',
          'position'   => 0,
          'directory'  => $directory,
          'query'      => $query,
          '_event'     => $sid,
        );
      }
    }
    else {
      $actions = explode('/', $uri);
      $this->ensure(empty($actions[0]), "[Module::Manager] Uri empty;");
      $request    = Request::current();
      $position   = $request->param('position');
      $directory  = $request->directory();
      if( isset($this->actions[++$position])){
        $sid = URL::title( $this->actions[$position], '', true);
        $directory = $directory . DIRECTORY_SEPARATOR . $sid;
      }
      $event = '';
      for( $i=0; $i<=$position; $i++){
        if ( isset($this->actions[$i]) ) {
          $part   = Utf8::str_ireplace( array('-', '.', ',', ' ', '\\', '/'), '_', $this->actions[$i] );
          $event .= ( $i===0 ? '' : '_' ) . $part;
        }
      }
      $controller = Url::title( preg_replace('![^\pL\pN\s]++!u', '', $actions[0]), '', true );
      $result = array(
        'controller'  => $controller,
        'action'      => 'index',
        'directory'   => $directory,
        '_event'      => "{$event}_{$controller}",
      );
      unset($actions[0]);
      foreach ($actions as $i => $key) {
        if( isset($this->actions[++$position]) && !array_key_exists($result[$key]) )
          $result[$key] = $this->actions[$position];
        else $result[$key] = null;
      }
      $result['position'] = $position;
      $query = '';
      for($i=$position+1; $i<count($this->actions); $i++){
        $query .= ( $i === ($position+1) ? '' : '/' ) . $this->actions[$i];
      }
      $result['query'] = $query;
    }
    $result = Arr::merge( $this->gets, $result );
    $result['current_root'] = $this->_uri;
    return $result;
  }

  /**
   *
   * Enter description here ...
   *
   * @param string $file
   */
  private function create($class, $action)
  {
    $config = $this->config['views'];

    $file = new SplFileInfo(
      APPPATH .
        'classes' . DIRECTORY_SEPARATOR . $class . EXT);

    $class = str_replace(array('\\', '/'), '_', trim($class, '/\\'));

    $this->ensure($config['create'] !== true && !class_exists($class),
      "[Modules::Manager] class [$class] not found");

    if (!class_exists($class)) {
      $name = str_replace('_', ' ', $class);
      $name = Utf8::ucwords($name);
      $name = str_replace(' ', '_', $name);
      $date = date(" Y - m M - d D");
      $ext  = EXT;
      $code = <<<EOF
<?php defined('SYSPATH') or die('No direct script access.');
/**
*@name $name
*@packages Manager/Controllers
*@subpackage Controllers
*@category Controllers
*@author Andrew Scherbakov
*@version 1.0
*@copyright created $date
*/
class $name extends Controller_Manager{

  /**
   * view file for action
   * @var string
   */
  protected \$template = '';

  /**
   * (non-PHPdoc)
   * @see Controller_Manager::initialize()
   */
  protected function initialize(){

  }

  /**
   * (non-PHPdoc)
   * @see Controller_Manager::finalize()
   */
  protected function finalize(){

  }

  /**
   * (non-PHPdoc)
   * @see Controller_Manager::do_action()
   */
  protected function do_action(){

  }
}
EOF;
      if (!is_dir($file->getPath()))
        @mkdir($file->getPath(), 0755, true);
      $fs = $file->openFile('a');
      $fs->fwrite($code);
      $fs->eof();
    }
  }

  /**
   *
   * @param string $uri
   *
   * @return array
   */
  public static function parse($uri)
  {
    $uri      = preg_replace("!\\|\s!u", '/', $uri);
    $uri      = preg_replace("!/++!u", '/', $uri);
    $uri      = Utf8::trim($uri, "/");
    $uri      = Utf8::strtolower($uri);
    $instance = self::instance($uri);
    $result   = $instance->doParse($uri);
    $instance->create(
      'controller' . DIRECTORY_SEPARATOR .
        $result['directory'] . DIRECTORY_SEPARATOR .
        $result['controller'], $result['action']);
    return $result;
  }

  /**
   * @return string
   */
  public static function theme()
  {
    if (!self::$instance)
      throw new Request_Exception("[Modules::Manager] Instance not found");
    $instance = self::instance();
    $config   = $instance->config->get('views');
    return URL::site($config['theme'], 'http') . '/';
  }

  /**
   *
   * Enter description here ...
   *
   * @param string $uri
   *
   * @return Response
   */
  public static function execute($uri, $template = null, HTTP_Cache $cache = null)
  {
    $current        = self::$instance;
    self::$instance = null;
    $new = self::instance($uri, $current->gets);
    $new->template($template);
    $new->cache($cache);
    $response       = Request::factory($uri, $cache)->execute();
    //$response->send_headers();
    self::$instance = $current;
    return $response;
  }

  /**
   *
   * @param View|NULL $template
   */
  public static function template($template = null)
  {
    if (!self::$instance)
      throw new Request_Exception("[Modules::Manager] Instance not found");
    $instance = self::instance();
    $result   = $template;
    if (!is_null($template)) $instance->template = $template;
    else  $result = $instance->template;
    return $result;
  }

  /**
   * @static
   * @param HTTP_Cache|null $cache
   * @return HTTP_Cache|null
   */
  public static function cache( HTTP_Cache $cache = null){
    if (!self::$instance)
      throw new Request_Exception("[Modules::Manager] Instance not found");
    $instance = self::instance();
    $result   = $cache;
    if (!is_null($cache)) $instance->cache = $cache;
    else  $result = $instance->cache;
    return $result;
  }

  // public bind(event,closure,priority=self::ATTR_NORMAL_EVENT_PRIORITY) {{{ 
  /**
   * bind
   * 
   * @param string  $event 
   * @param closure $closure 
   * @param integer $priority 
   * @static
   * @access public
   * @return bool
   */
  public static function bind( $event, $closure, $priority = self::ATTR_NORMAL_EVENT_PRIORITY ){
    $return = false;
    if ( is_callable( $closure ) && is_int( $priority ) ){
      if ( !array_key_exists( $event, self::$events ) ) 
        self::$events[$event] = array();
      if ( array_key_exists( $priority, self::$events[$event]) )
        array_push( self::$events[$event][$priority], $closure);
      else self::$events[$event][$priority] = array( $closure );
      ksort(self::$events[$event]);
      $return = true;
    }

    return $return;
  }
  // }}}

  // public trigger(event) {{{ 
  /**
   * trigger
   * 
   * @param string  $event 
   * @static
   * @access public
   * @return bool
   */
  public static function trigger( $event ){
    $return = false;
    if ( array_key_exists( $event, self::$events) ){
      $args = func_get_args();
      array_shift($args);
      foreach( self::$events[$event] as $callbacks ){
        foreach( $callbacks as $cb){
          call_user_func_array( $cb, $args);
        }
      }
      $return = true;
    }

    return $return;
  }
  // }}}

}
