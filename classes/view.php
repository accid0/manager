<?php defined('SYSPATH') or die('No direct script access.');

class View extends Kohana_View {
  /**
   * 
   */
  public function __clone(){
    foreach ( $this->_data as $key => $item){
      if ( is_object( $item))  $this->_data [$key]= clone $item;
    }
  }
  
  /**
   * @return string
   */
  public function get_filename(){
    return $this->_file;
  }
}