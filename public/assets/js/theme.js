/**
 * Theme Initialization - Loads before DOM for no-flash
 */
(function() {
    'use strict';
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', theme);
})();
