import { Controller } from 'stimulus';
const $ = require('jquery');
/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
  connect() {

    const btnSubmit = this.element.querySelector('#loginBtn');
    const element = this.element
    
    btnSubmit.addEventListener("click", function (e) {
       console.log("click");
      var email = element.querySelector('#inputUsername').value;
      try {
        $.ajax({
          type: "GET",
          url: '/check-auth-mode?_username=' + email,
          success: function (data) {
            console.log(data.authType);
            if (data.authType == 'AZUR_AD') {
              e.preventDefault();
              document.location.href = '/oAuthClient?login=' + email
            }

          },
          error: function () {

          }
        });


      } catch (e) {
        console.log(e);
      }

    });
  }
}
