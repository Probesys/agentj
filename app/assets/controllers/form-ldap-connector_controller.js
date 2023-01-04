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
export default class extends Controller {
  static get targets() {
    return ['ldapHost', 'ldapPort', 'ldapBindDn', 'ldapPassword', 'urlCheckBind', 'ldapBindResult'];
  }
  connect() {

//        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
  }

  checkConnection() {

    const formData = new FormData();
    formData.append('ldapHost', this.ldapHostTarget.value);
    formData.append('ldapPort', this.ldapPortTarget.value);
    formData.append('ldapBindDn', this.ldapBindDnTarget.value);
    formData.append('ldapPassword', this.ldapPasswordTarget.value);
    const targetResult = this.ldapBindResultTarget;
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
              if (data.status == 'success') {
                targetResult.classList.add('text-success');
                targetResult.classList.remove('text-danger');
                
              } else {
                targetResult.classList.add('text-danger');
                targetResult.classList.remove('text-success');
              }
              targetResult.textContent = data.message;
              ;
            });
  }
}
