/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
/**
 * Plugins css
 */
import 'datatables.net-bs5/css/dataTables.bootstrap5.css'
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.css'
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css'
import 'datatables.net-select-bs5/css/select.bootstrap5.css'

import 'select2/dist/css/select2.css'

import './scss/bootstrap.scss';
import './scss/app.scss';
import './scss/icons.scss';


// jquery, bootstrap and popper-core
const $ = require('jquery');
global.$ = global.jQuery = $;
require('bootstrap');
require('@popperjs/core');
require('inputmask')
// Moment
const moment = require('moment');
global.moment = moment;

// Feather icons
const feather = require('feather-icons')
global.feather = feather



/**
 * Plugins js
 */
import 'datatables.net'
import 'datatables.net-bs5'
import 'datatables.net-responsive'
import 'datatables.net-responsive-bs5'
import 'datatables.net-buttons'
import 'datatables.net-buttons-bs5'
import 'datatables.net-select'
import 'datatables.net-select-bs5'
import 'datatables.net-buttons/js/buttons.html5'
import 'datatables.net-buttons/js/buttons.flash'
import 'datatables.net-buttons/js/buttons.print'

import 'select2'

import './js/datatables.init'
import './js/form.init'

/**
 * Page js
 */
import './js/add-form-collection';
import './js/delete.form.collection';
import './js/dossierPersonal/personnel';
import './js/dossierPersonal/add_prime_juridique';
import './js/dossierPersonal/fetchpersonal'

import './js/app';

// Feather Icons
feather.replace()
