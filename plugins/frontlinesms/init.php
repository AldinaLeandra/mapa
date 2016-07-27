<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * FrontlineSMS Data Providers
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    DataProvider\FrontlineSMS
 * @copyright  2013 Ushahidi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

// Plugin Info
$plugin = array(
	'name' => 'FrontlineSMS',
	'version' => '0.1',

	// Services Provided By This Plugin
	'services' => array(
			Message_Type::SMS => TRUE
	),

	'options' => array(
		'key' => array(
				'label' => 'Key',
				'input' => 'text',
				'description' => 'The API key',
				'rules' => array('required')
		),
		'secret' => array(
			'label' => 'Secret',
			'input' => 'text',
			'description' => 'Set a secret so that only authorized FrontlineCloud accounts can send/recieve message. You need to configure the same secret in the FrontlineCloud Activity.',
			'rules' => array('required')
		)
	),

		// Links
	'links' => array(
		'overview' => 'http://www.frontlinesms.com/technologies/frontlinesms-overview/',
		'download' => 'http://www.frontlinesms.com/technologies/download/'
	)
);

// Register the plugin
DataProvider::register_provider('frontlinesms', $plugin);

// Additional Routes

/**
 * SMS Callback url
 */
Route::set('frontlinesms_sms_callback_url', 'sms/frontlinesms(/<action>)')
	->defaults(array(
		'directory' => 'Sms',
		'controller' => 'frontlinesms',
	));

/**
 * Legacy FrontlineSMS Callback url
 */
Route::set('frontlinesms_legacy_callback_url', 'frontlinesms(/<action>)')
	->defaults(array(
		'directory' => 'Sms',
		'controller' => 'frontlinesms'
	));
