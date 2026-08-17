/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)

import './styles/css/app.css';
import Chart from 'chart.js/auto';
window.Chart = Chart;

import $ from 'jquery';
window.$ = window.jQuery = $;



import { Modal } from 'bootstrap';
window.Modal = Modal;

import  'jquery-ui-dist/jquery-ui';

import './agentj';
import './nav';

import './bootstrap';
