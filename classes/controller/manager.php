<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Manager extends Controller {
  
  /**
   * @var View
   */
  private static $extends = NULL;
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
  protected function initialize()
  {
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
  protected function extend( $uri){
    $response = (string)Request::factory( $uri)->execute();
    if ( $this->view && $this->file)  $this->view->set_filename( $this->file);
    return $response;
  }
  /**
   * 
   * @param string $uri
   * @return string
   */
  protected function execute( $uri){
    $response = (string)Manager::execute($uri);
    if ( $this->view && $this->file)  $this->view->set_filename( $this->file);
    return $response;
  }
  
  /**
   * (non-PHPdoc)
   * @see Kohana_Controller::before()
   */
  public function before(){
    if( $this->template != ''){
      $this->file = $this->template;
    }
    if ( $this->auto_render === TRUE){
      if ( Manager::template()){
        $this->view = Manager::template();
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
    $this->initialize();
    parent::before();
  }

  /**
   * Standart action method of controller from manager
   * For params of action
   * @see Request::param( $sid, $default)
   */
  public function action_index(){
    $this->do_action();
  }

  /**
   * Innerhiting this method for your controller
   * For params of action
   * @see Request::param( $sid, $default)
   */
  protected function do_action(){

  }

  /**
   * (non-PHPdoc)
   * @see Kohana_Controller::after()
   */
  public function after(){
    $this->finalize();
    if ( $this->auto_render === TRUE && $this->view){
      $this->response->body( $this->view);
    }
    parent::after();
  }

} // End Manager