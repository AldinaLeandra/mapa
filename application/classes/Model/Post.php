<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Posts
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class Model_Post extends ORM {
	/**
	 * A post has many comments decimal, geometry, int
	 * point, text, varchar, tasks
	 * 
	 * A post has and belongs to many sets and tags
	 * 
	 * A post has many [children] posts
	 *
	 * @var array Relationhips
	 */
	protected $_has_many = array(
		'post_comments' => array(),
		'post_decimal' => array(),
		'post_geometry' => array(),
		'post_int' => array(),
		'post_point' => array(),
		'post_text' => array(),

		'tasks' => array(),

		'sets' => array('through' => 'posts_sets'),
		'tags' => array('through' => 'posts_tags'),

		'children' => array(
			'model'  => 'Post',
			'foreign_key' => 'parent_id',
			),
		);

	/**
	 * A post belongs to a user, and a [parent]
	 *
	 * @var array Relationhips
	 */
	protected $_belongs_to = array(
		'user' => array(),

		'parent' => array(
			'model'  => 'post',
			'foreign_key' => 'parent_id',
			),
		);


	// Insert/Update Timestamps
	protected $_created_column = array('column' => 'created', 'format' => TRUE);
	protected $_updated_column = array('column' => 'updated', 'format' => TRUE);

	/**
	 * Rules for the post model
	 *
	 * @return array Rules
	 */
	public function rules()
	{
		return array(
			'form_id' => array(
				array('not_empty'),
				array('numeric'),
				array(array($this, 'form_exists'), array(':validation', ':field', ':value'))
			),

			'title' => array(
				array('not_empty'),
				array('max_length', array(':value', 150))
			),

			// Post Types
			'status' => array(
				array('in_array', array(':value', array(
					'draft',
					'published',
					'pending'
				)) )
			),

			// Post Types
			'type' => array(
				array('not_empty'),
				array('in_array', array(':value', array(
					'report',
					'revision',
					'comment',
					'alert'
				)) )
			)
		);
	}

	/**
	 * Callback function to check if form exists
	 */
	public function form_exists($validation, $field, $value)
	{
		$form = ORM::factory('Form')
			->where('id', '=', $value)
			->find();

		if ( ! $form->loaded() )
		{
			$validation->error($field, 'form_exists');
		}
	}

	

	/**
	 * Prepare single post for api ( ++ Hairy :) )
	 * along with values from attached tables
	 * 
	 * @return array $response
	 * @todo the queries need some optimizing (EAV Fun)
	 */
	public function for_api()
	{
		$response = array();
		if ( $this->loaded() )
		{
			$response = array(
				'url' => url::site('api/v2/posts/'.$this->id, Request::current()),
				'id' => $this->id,
				'form_id' => $this->form_id,
				'title' => $this->title,
				'content' => $this->content,
				'status' => $this->status,
				'created' => strtotime($this->created),
				'updated' => strtotime($this->updated),
				'values' => array()
				);

			// Create the Super Union
			// @todo generalize this - how do plugins add other attribute types?
			$datetimes = DB::select('key', 'value')
				->from('post_datetime')
				->join('form_attributes')
					->on('post_datetime.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$decimals = DB::select('key', 'value')
				->union($datetimes)
				->from('post_decimal')
				->join('form_attributes')
					->on('post_decimal.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$geometries = DB::select('key', 'value')
				->union($decimals)
				->from('post_geometry')
				->join('form_attributes')
					->on('post_geometry.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$ints = DB::select('key', 'value')
				->union($geometries)
				->from('post_int')
				->join('form_attributes')
					->on('post_int.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$points = DB::select('key', 'value')
				->union($ints)
				->from('post_point')
				->join('form_attributes')
					->on('post_point.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$texts = DB::select('key', 'value')
				->union($points)
				->from('post_text')
				->join('form_attributes')
					->on('post_text.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$varchars = DB::select('key', 'value')
				->union($texts)
				->from('post_varchar')
				->join('form_attributes')
					->on('post_varchar.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);

			$datetimes = DB::select('key', 'value')
				->union($varchars)
				->from('post_datetime')
				->join('form_attributes')
					->on('post_datetime.form_attribute_id', '=', 'form_attributes.id')
				->where('post_id', '=', $this->id);
				
			$results = $datetimes->execute();

			foreach ($results as $result)
			{
				$response['values'][$result['key']] = $result['value'];
			}
		}
		else
		{
			$response = array(
				'errors' => array(
					'Post does not exist'
					)
				);
		}

		return $response;
	}
}
