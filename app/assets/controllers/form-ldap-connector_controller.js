import { Controller } from '@hotwired/stimulus';


var controllerForm = null;
export default class extends Controller {
  static get targets() {
    return ['ldapPort', 'urlCheckBind', 'urlCheckUserFilter', 'urlCheckGroupFilter', 'ldapBindResult'];
  }

  connect() {
    controllerForm = this.element;
    const cboxSyncGroup = this.element.querySelector('#ldap_connector_synchronizeGroup');
    this.showHideGroupInfo(cboxSyncGroup.checked);
    const cboxAnonymousBind = this.element.querySelector('#ldap_connector_allowAnonymousBind');
    this.showHideBindInfo(cboxAnonymousBind.checked);
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

  checkGroupFilter(e) {
    const targetResult = e.target;

    var result = false;
    if (this.ldapPortTarget.validity.valid) {
      const formData = new FormData(controllerForm);


      fetch(this.urlCheckGroupFilterTarget.value, {
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
  
  
  showHideGroupInfoEventClick(event) {
    this.showHideGroupInfo(event.target.checked);
  }

  showHideGroupInfo(show) {
    const fieldSetGroupInfo = this.element.querySelector('#info-group-ldap');
    if (show) {
      fieldSetGroupInfo.classList.remove('d-none');
      const listGroupInput = document.querySelectorAll('[data-ldap-group]');
      listGroupInput.forEach(input => {
        input.setAttribute('required', true);
      });
    } else {
      fieldSetGroupInfo.classList.add('d-none');
      const listGroupInput = document.querySelectorAll('[data-ldap-group]');
      listGroupInput.forEach(input => {
        input.removeAttribute('required');
      });
    }

  }

  showHideBindInfoEventClick(event) {

    this.showHideBindInfo(event.target.checked);
  }  

  showHideBindInfo(checked) {
    const divBindingInfo = this.element.querySelector('#info-binding-ldap');
    if (!checked) {
      divBindingInfo.classList.remove('d-none');
      const listBindingInput = document.querySelectorAll('[data-ldap-bind]');
      listBindingInput.forEach(input => {
        input.setAttribute('required', true);
      });
    } else {
      divBindingInfo.classList.add('d-none');
      const listBindingInput = document.querySelectorAll('[data-ldap-bind]');
      listBindingInput.forEach(input => {
        input.removeAttribute('required');
      });
    }
    this.checkConnection();
  }    
}
