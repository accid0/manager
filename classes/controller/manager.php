<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Manager extends Controller {
  
  /**
   * @var View
   */
  private static $extends = NULL;
  /**
   * @var View
   */
  protected $template = NULL;
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
   * @var array
   */
  protected $templates = array();
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
   * @return mixed
   */
  protected function extend( $uri){
    $response = (string)Request::factory( $uri)->execute();
    if ( $this->template && $this->file)  $this->template->set_filename( $this->file);
    return $response;
  }
  /**
   * 
   * @param string $uri
   * @return mixed
   */
  protected function execute( $uri){
    $response = (string)Manager::execute($uri);
    return $response;
  }
  
  /**
   * (non-PHPdoc)
   * @see Kohana_Controller_Template::before()
   */
  public function before(){
    $action = Request::current()->action();
    if( isset($this->templates[$action])){
      $this->file = $this->templates[$action];
    }
    if ( $this->auto_render === TRUE){
      if ( Manager::template()){
        $this->template = Manager::template();
        if ( $this->file)
          $this->template->set_filename( $this->file);
        else{
          $this->auto_render = FALSE;
          $this->file = $this->template->get_filename();
        }
      }
      elseif( $this->file){
        $this->template = View::factory( $this->file);
        Manager::template( $this->template);
      }
    }
    $this->initialize();
    parent::before();
  }
  /**
   * (non-PHPdoc)
   * @see Kohana_Controller_Template::after()
   */
  public function after(){
    $this->finalize();
    if ( $this->auto_render === TRUE && $this->template){
      $this->response->body( $this->template);
    }
    parent::after();
  }

} // End Manager