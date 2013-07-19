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
<<<<<<< HEAD

=======
  
>>>>>>> 775854029cab69da707de03b0d4450f370eb3634
  /**
   * @return string
   */
  public function get_filename(){
<<<<<<< HEAD
    return $this->_file;
  }

  /**
   * @param  string   $file
   *
   * @return View
   */
  public function set_filename( $file){
    if ( is_file( $file ) ){
      $this->_file = $file;

    }
    else parent::set_filename( $file);
    return $this;
=======
    $file = str_replace( array(APPPATH, MODPATH, SYSPATH, EXT), '', $this->_file);
    $file = Utf8::substr($file, Utf8::strpos( $file, 'views/') + 6);
    return $file;
>>>>>>> 775854029cab69da707de03b0d4450f370eb3634
  }
}