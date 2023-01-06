import { Controller } from 'stimulus';

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
var controllerForm = null;
export default class extends Controller {
  static get targets() {
    return ['ldapPort', 'urlCheckBind', 'urlCheckUserFilter', 'ldapBindResult'];
  }

  connect() {
    controllerForm = this.element;

    this.checkConnection();
  }

  checkConnection() {

    const targetResult = this.ldapBindResultTarget;

    var result = false;
    if (this.ldapPortTarget.validity.valid) {
      const formData = new FormData(controllerForm);


      fetch(this.urlCheckBindTarget.value, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
        body: formData
      })
              .then((response) => {
                return response.json();
              })
              .then((data) => {
                this.showConnectionResult(data.status, targetResult);


                ;
              });
    } else {
      this.showConnectionResult('error', targetResult);

    }

  }

  showConnectionResult(status, target) {
    if (status == 'success') {
      target.classList.add('text-success');
      target.classList.remove('text-danger');
      target.innerHTML = '<i class="fas fa-2x fa-wifi"></i>';

    } else {
      target.classList.add('text-danger');
      target.classList.remove('text-success');
      target.innerHTML = '<i class="fas fa-2x fa-wifi"></i>';
    }

  }

  checkUserFilter(e) {
    const targetResult = e.target;

    var result = false;
    if (this.ldapPortTarget.validity.valid) {
      const formData = new FormData(controllerForm);


      fetch(this.urlCheckUserFilterTarget.value, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
        body: formData
      })
              .then((response) => {
                return response.json();
              })
              .then((data) => {
                targetResult.nextSibling.nextSibling.innerHTML = data.message;
                targetResult.nextSibling.nextSibling.classList.remove("d-none");
              });
    }

  }
}
