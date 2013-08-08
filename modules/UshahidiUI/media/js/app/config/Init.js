require.config(
{
	baseUrl : "./media/kohana/js/app",
	// 3rd party script alias names (Easier to type "jquery" than "libs/jquery, etc")
	// probably a good idea to keep version numbers in the file names for updates checking
	paths :
	{
		// Core Libraries
		"jquery" : "../libs/jquery",
		"jqueryui" : "../libs/jqueryui",
		"underscore" : "../libs/lodash",
		"backbone" : "../libs/backbone",
		"marionette" : "../libs/backbone.marionette",
		"handlebars" : "../libs/handlebars",
		"leaflet" : "../libs/leaflet",
		"jso2" : "../libs/jso2",
		"store" : "../libs/jso2/store",
		"utils" : "../libs/jso2/utils",

		// Plugins
		"backbone.validateAll" : "../libs/plugins/Backbone.validateAll",
		"text" : "../libs/plugins/text"
	},
	// Sets the configuration for your third party scripts that are not AMD compatible
	shim :
	{
		"jqueryui" : ["jquery"],
		"backbone" :
		{
			"deps" : ["underscore", "jquery"],
			// Exports the global window.Backbone object
			"exports" : "Backbone"
		},
		"marionette" :
		{
			"deps" : ["underscore", "backbone", "jquery"],
			// Exports the global window.Marionette object
			"exports" : "Marionette"
		},
		"handlebars" :
		{
			"exports" : "Handlebars"
		},
		// Backbone.validateAll plugin (https://github.com/gfranko/Backbone.validateAll)
		"backbone.validateAll" : ["backbone"],

		'leaflet': {
			deps: ['jquery'],
			exports: 'L'
		},
	}
});

// Includes Desktop Specific JavaScript files here (or inside of your Desktop router)
require(["App", "routers/AppRouter", "controllers/Controller", "jquery", "jqueryui", "backbone.validateAll"],
	function(App, AppRouter, Controller) {
		App.appRouter = new AppRouter(
		{
			controller : new Controller()
		});
		App.start();
		window.App = App;
	}); 