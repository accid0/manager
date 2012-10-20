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
   *
   * Enter description here ...
   *
   * @var Config
   */
  private $config = NULL;
  /**
   *
   * @var boolean
   */
  private $init = TRUE;
  /**
   *
   * Enter description here ...
   *
   * @var View|NULL
   */
  private $template = NULL;

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
    if (is_null(self::$instance)) self::$instance = new self($uri);
    return self::$instance;
  }

  /**
   *
   * Enter description here ...
   *
   * @param unknown_type $uri
   */
  private function __construct($uri)
  {
    $this->actions = explode('/', $uri);
    $this->config  = Kohana::$config->load('manager');
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
      $this->init = FALSE;
      $directory  = $this->config->get('views');
      $directory  = $directory['path'];
      if ( !empty($directory)) $directory .= DIRECTORY_SEPARATOR;
      if (empty($this->actions[0])) {
        $directory .= 'index';
        $result = array(
          'controller' => 'main',
          'action'     => 'action',
          'position'   => 0,
          'directory'  => $directory
        );
      }
      else{
        $directory .= $this->actions[0];
        $result = array(
          'controller' => 'main',
          'action'     => 'action',
          'position'   => 0,
          'directory'  => $directory
        );
      }
    }
    else {
      $actions = explode('/', $uri);
      $this->ensure(empty($actions[0]), "[Module::Manager] Not found uri[$uri];");
      $request    = Request::current();
      $position   = $request->param('position');
      $controller = $request->controller();
      $directory  = $request->directory();
      $current    = $request->action();
      $this->ensure(!isset($this->actions[++$position]),
        "[Module::Manager] Not found uri for index [$position]");
      $action = $this->actions[$position];
      $result = array(
        'controller' => $actions[0],
        'action'     => 'action',
        'directory'  => $directory . DIRECTORY_SEPARATOR . $action,
      );
      unset($actions[0]);
      foreach ($actions as $i => $key) {
        $this->ensure(!isset($this->actions[++$position]),
          "[Module::Manager] Not found param [$key] from uri");
        $result [$key] = $this->actions[$position];
      }
      $result['position'] = $position;
    }
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

    $file   = new SplFileInfo(
      APPPATH .
        'classes' . DIRECTORY_SEPARATOR . $class . EXT);

    $class = str_replace(array('\\', '/'), '_', trim($class, '/\\'));

    $this->ensure($config['create'] !== TRUE && !class_exists($class),
      "[Modules::Manager] class [$class] not found");

    if ( !class_exists($class) ) {
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
        @mkdir($file->getPath(), 0755, TRUE);
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
    $uri      = preg_replace('!\\|\s!', '/', $uri);
    $uri      = preg_replace('!/++!', '/', $uri);
    $uri      = trim($uri, '/');
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
  public static function execute($uri, $template = NULL)
  {
    $current        = self::instance();
    self::$instance = NULL;
    self::instance($uri)->template($template);
    $response       = Request::factory($uri)->execute();
    self::$instance = $current;
    return $response;
  }

  /**
   *
   * @param View|NULL $template
   */
  public static function template($template = NULL)
  {
    $instance = self::instance();
    $result   = $template;
    if (!is_null($template)) $instance->template = $template;
    else  $result = $instance->template;
    return $result;
  }
}