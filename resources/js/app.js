import jQuery from 'jquery';
import jQueryTerminal from 'jquery.terminal';

// jquery.terminal's CJS export is a factory — call it to install the plugin.
jQueryTerminal(window, jQuery);

window.$ = window.jQuery = jQuery;
