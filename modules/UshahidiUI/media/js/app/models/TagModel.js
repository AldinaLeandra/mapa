/**
 * Tag Model
 *
 * @module     TagModel
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  2013 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */

define(['jquery', 'backbone', 'App'],
	function($, Backbone, App) {
		var TagModel = Backbone.Model.extend(
		{
			urlRoot: App.config.baseurl + 'api/v2/tags',
			toString : function ()
			{
				return this.get('tag');
			}
		});
	
		return TagModel;
	});