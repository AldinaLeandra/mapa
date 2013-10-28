<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Post_Value
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application\Models
 * @copyright  2013 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

abstract class Model_Post_Value extends ORM {
	/**
	 * A post_decimal belongs to a post and form_attribute
	 *
	 * @var array Relationships
	 */
	protected $_belongs_to = array(
		'post' => array(),
		'form_attribute' => array(),
		);

	// Insert/Update Timestamps
	protected $_created_column = array('column' => 'created', 'format' => TRUE);
	
	/**
	 * Does this attribute type have complex (ie. array) values?
	 * @var boolean 
	 **/
	protected $_complex_value = FALSE;

	/**
	 * Rules for the post_decimal model
	 *
	 * @return array Rules
	 */
	public function rules()
	{
		return array(
			'id' => array(
				array('numeric')
			),
			
			'post_id' => array(
				array('numeric'),
				array(array($this, 'fk_exists'), array('Post', ':field', ':value')),
			),
			'form_attribute_id' => array(
				array('numeric'),
				array(array($this, 'fk_exists'), array('Form_Attribute', ':field', ':value')),
			),
			'value' => array(
				
			)
		);
	}
	
	/**
	 * Does this attribute type have complex values?
	 * @return boolean
	 */
	public function complex_value()
	{
		return $this->_complex_value;
	}
}