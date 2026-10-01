/**
 * @licstart  The following is the entire license notice for this JavaScript Code.
 * WPe Hooks 1.2
 * WPe Hooks is a simple JavasScript Code to use actions and filters like WordPress.
 *
 * Copyright 2017, Sebastian Robles, Esteban Truelsegaard.
 * Licensed under the GNU General Public License v2 or later.
 *
 * Date: Mon, Sep 18, 2017
 * @licend  The above is the entire license notice for this JavaScript Code.
 */
if (typeof wpe_filter !== 'function') {
	function wpe_filter() {
	    this.callbacks = new Array();
	}
}
if (typeof wpe_actions !== 'object') {
	var wpe_filters = {};
}
if (typeof js_apply_filters !== 'function') {
    function js_apply_filters(tag, value) {
		var args = Array.prototype.slice.call(arguments);
		if (wpe_filters[tag] == undefined) {
			return value;
		}
		if (wpe_filters[tag].callbacks == undefined) {
			return value;
		}
		args.shift();
		for (var priority in  wpe_filters[tag].callbacks) {
			for (var i = 0; i < wpe_filters[tag].callbacks[priority].length; i++) {
				args[0] = value;
				value = wpe_filters[tag].callbacks[priority][i].function.apply(this, args);
			}
		}
		return value;
	}
}

if (typeof js_add_filter !== 'function') {
	function js_add_filter(tag, function_to_add, priority = 10, accepted_args = 1) {
		/* The argument accepted_args is not used because JavaScript is not strict with the arguments of functions  */
		if (wpe_filters[tag] == undefined) {
			wpe_filters[tag] = new wpe_filter();
		}
		var new_callback = {
			function: function_to_add,
			accepted_args: accepted_args
		};
		if (wpe_filters[tag].callbacks[priority] == undefined) {
			wpe_filters[tag].callbacks[priority] = new Array();
		}
		wpe_filters[tag].callbacks[priority].push(new_callback);
	}
}
if (typeof wpe_action !== 'function') {
	function wpe_action() {
	    this.callbacks = new Array();
	}
}
if (typeof wpe_actions !== 'object') {
	var wpe_actions = {};
}
if (typeof js_do_actions !== 'function') {
	function js_do_actions(tag) {
		var args = Array.prototype.slice.call(arguments);
		if (wpe_actions[tag] == undefined) {
			return false;
		}
		if (wpe_actions[tag].callbacks == undefined) {
			return false;
		}
		args.shift();
		for (var priority in  wpe_actions[tag].callbacks) {
			for (var i = 0; i < wpe_actions[tag].callbacks[priority].length; i++) {
				wpe_actions[tag].callbacks[priority][i].function.apply(this, args);
			}
		}
		return true;
	}
}
if (typeof js_add_action !== 'function') {
	function js_add_action(tag, function_to_add, priority = 10, accepted_args = 1) {
		/* The argument accepted_args is not used because JavaScript is not strict with the arguments of functions  */
		if (wpe_actions[tag] == undefined) {
			wpe_actions[tag] = new wpe_action();
		}
		var new_callback = {
			function: function_to_add,
			accepted_args: accepted_args
		};
		if (wpe_actions[tag].callbacks[priority] == undefined) {
			wpe_actions[tag].callbacks[priority] = new Array();
		}
		wpe_actions[tag].callbacks[priority].push(new_callback);
	}
}

/*
Example Code: 
function filter_test_url(url, argument) {
	url = 'etruel.com';
	return url;
}
js_add_filter('wpematico_test_url_params', filter_test_url, 10, 2);
var url_test = js_apply_filters('wpematico_test_url_params', 'URL', 'A value', 'a second argument');
alert(url_test);

function do_test(value){
	alert('Hello '+value+'!');
}
js_add_action('action_wpematico', do_test, 10, 1);
js_do_actions('action_wpematico', 'World');
*/
