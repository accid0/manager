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
   *
   * Enter description here ...
   *
   * @var Kohana_Manager
   */
  private static $instance = NULL;
  
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

  /**
   *
   * Enter description here ...
   *
   * @param string $uri
   *
   * @return Kohana_Manager
   */
  private static function instance($uri = '')
  {
    if ( self::$instance === null ) self::$instance = new self($uri);
    return self::$instance;
  }

  /**
   * @param string $uri
   */
  private function init( $uri){
    $this->_uri = $uri;
    if ( Utf8::strpos( $uri, '?') !== false){
      $uri = explode( '?', $uri);
      if ( $uri[0] == '' && count($uri) > 1)
        array_shift($uri);
      $gets = array();
      parse_str($uri[1], $gets);
      $this->gets = $gets;
      if ( !Request::initial() )
        parse_str($uri[1], $_GET);
    }
    else $uri = array($uri);
    $actions = explode('/', $uri[0]);
    if ( $actions[0] == '' && count($actions) > 1)
      array_shift($actions);
    $this->actions = $actions;
    $this->config  = Kohana::$config->load('manager');
  }

  /**
   *
   * Enter description here ...
   *
   * @param unknown_type $uri
   */
  private function __construct($uri)
  {
    $this->init( $uri);
  }

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

      if (!empty($directory)) $directory .= DIRECTORY_SEPARATOR;
      if (empty($this->actions[0])) {
        $directory .= 'index';
        $result = array(
          'controller' => 'main',
          'action'     => 'index',
          'position'   => 0,
          'directory'  => $directory,
          'query'      => $query,
        );
      }
      else {
        $directory .= URL::title( $this->actions[0], '', true);
        $result = array(
          'controller' => 'main',
          'action'     => 'index',
          'position'   => 0,
          'directory'  => $directory,
          'query'      => $query,
        );
      }
    }
    else {
      $actions = explode('/', $uri);
      $this->ensure(empty($actions[0]), "[Module::Manager] Uri empty;");
      $request    = Request::current();
      $query = $request->param('query');
      $position   = $request->param('position');

      $directory  = $request->directory();

      if( isset($this->actions[++$position]))
        $directory = $directory . DIRECTORY_SEPARATOR . URL::title( $this->actions[$position], '', true);

      $result = array(
        'controller' => preg_replace('![^\pL\pN\s]++!u', '', $actions[0]),
        'action'     => 'index',
        'directory'  => $directory,
        'query'      => $query,
      );
      unset($actions[0]);
      foreach ($actions as $i => $key) {
        if( isset($this->actions[++$position]))
          $result [$key] = $this->actions[$position];
        else $result[$key] = null;
      }
      $result['position'] = $position;
    }
    $result = Arr::merge( $result, $this->gets);
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
    self::instance($uri)->template($template);
    self::instance($uri)->cache($cache);
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
    $instance = self::instance();
    $result   = $cache;
    if (!is_null($cache)) $instance->cache = $cache;
    else  $result = $instance->cache;
    return $result;
  }

}
