<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Response wrapper. Created as the result of any [Request] execution
 * or utility method (i.e. Redirect). Implements standard HTTP
 * response format.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2011 Kohana Team
 * @license    http://kohanaphp.com/license
 * @since      3.1.0
 */
class Response extends Kohana_Response {
  /**
   * (non-PHPdoc)
   * @see Kohana_Response::body()
   */
  public function body($content = NULL)
  {
	if ($content === NULL)
	  return $this->_body;

	$this->_body = $content;
	return $this;
  }
  /**
   * (non-PHPdoc)
   * @see Kohana_Response::__toString()
   */
  public function __toString()
  {
	return (string)$this->_body;
  }
}