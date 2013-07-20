<?php defined('SYSPATH') or die('No direct script access.');
/**
 * View_Null 
 * 
 * @uses View
 * @package 
 * @version $id$
 * @copyright 2013 Accido
 * @author Andrew Scherbakov <kontakt.asch@gmail.com> 
 * @license PHP Version 5.2 {@link http://www.php.net/license/}
 */
class View_Null extends View{

  // public factory(file=NULL,array data=NULL) {{{ 
  /**
   * factory
   * 
   * @param string $file 
   * @param array $data 
   * @static
   * @access public
   * @return View_Null
   */
  public static function factory($file = NULL, array $data = NULL){
    return new self(NULL,$data);
  }
  // }}}

}
