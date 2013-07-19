<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Manager extends Controller {
  /**
   * @var string
   */
  protected $template = NULL;
  /**
   * @var View
   */
  protected $view = NULL;
  /**
   * 
   * @var string
   */
  protected $file = NULL;
  /**
   * @var boolean
   */
  protected $auto_render = TRUE;

  // private trigger_events() {{{ 
  /**
   * trigger_events
   * 
   * @access private
   * @return void
   */
  private function trigger_events(){
    $event = $this->request->param('_event');
    try{
      Manager::trigger( $event, $this->view, $this->request );
    }
    catch( Exception $e ){
      // @todo realize log errors
      throw new Exception($e);
    }
  }
  // }}}

  /**
   * @param boolean $exp
   * @param string $msg
   * @throws View_Exception
   */
  protected function ensure( $exp, $msg){
    if ( $exp)  throw new View_Exception( $msg);
  }
  /**
   */
  protected function initialize(){
  }

  /**
   */
  protected function finalize(){

  }
  /**
   * 
   * Enter description here ...
   * @param string $uri
   * @return string
   */
  protected function extend( $uri, $cache = TRUE){
    if ( $cache) $cache = Manager::cache();
    else $cache = NULL;
    $response = (string)Request::factory( $uri, $cache)->execute();
    if ( $this->view && $this->file)  $this->view->set_filename( $this->file);
    return $response;
  }
  /**
   * 
   * @param string $uri
   * @return string
   */
  protected function execute( $uri, View $view = NULL, $cache = TRUE){
    if ( $cache) $cache = Manager::cache();
    else $cache = NULL;
    $response = (string)Manager::execute($uri, $view, $cache);
    if ( $this->view && $this->file)  $this->view->set_filename( $this->file);
    return $response;
  }
  
  /**
   * (non-PHPdoc)
   * @see Kohana_Controller::before()
   */
  public function before(){
    $this->initialize();
    if( $this->template != ''){
      $this->file = $this->template;
    }
    if ( $this->auto_render === TRUE){
      if ( $this->view = Manager::template()){
        if ( $this->file != NULL && $this->file != '')
          $this->view->set_filename( $this->file);
        else{
          $this->auto_render = FALSE;
          $this->file = $this->view->get_filename();
        }
      }
      elseif( $this->file != NULL && $this->file != ''){
        $this->view = View::factory( $this->file);
        Manager::template( $this->view);
      }
    }
    $this->trigger_events();
    parent::before();
  }

  /**
   * Standart action method of controller from manager
   * For params of action
   */
  public function action_index(){
    $this->do_action();
  }

  /**
   * Innerhiting this method for your controller
   * For params of action
   */
  protected function do_action(){

  }

  /**
   * (non-PHPdoc)
   * @see Kohana_Controller::after()
   */
  public function after(){
    if ( $this->auto_render === TRUE && $this->view){
      $this->response->body( $this->view->render());
    }
    $this->finalize();
    parent::after();
  }

} // End Manager
