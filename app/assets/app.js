/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)

import './styles/app.css';
import './styles/scss/styles.scss';
// start the Stimulus application
import './bootstrap';

document.addEventListener('turbo:visit', () => {
  // fade out the old body
  document.body.classList.add('turbo-loading');
});

document.addEventListener('turbo:render', () => {
  // after rendering, we first allow the turbo-loading class to set the low opacity
  // THEN, one frame later, we remove the turbo-loading class, which allows the fade in
  requestAnimationFrame(() => {
    document.body.classList.remove('turbo-loading');
  });
});