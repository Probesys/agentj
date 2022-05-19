import { Controller } from 'stimulus';
const axios = require('axios').default;

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

  static values = {
    url: String,
  }

  connect() {
    this.urlValue = this.element.dataset.urlChecklabel;

    const inputGroupLabel = this.element.querySelector('#groups_name');
    const selectGroupDomain = this.element.querySelector('#groups_domain');

    this.element.addEventListener('submit', (event) => {
      event.preventDefault(); 
      this.checkIfDomainGroupExists(inputGroupLabel.value, selectGroupDomain.value).then(result => {
        if (!result){        
           this.element.submit();
        }
        else{
          const msgContainer = this.element.querySelector('#flash-message-modal-container');
        $("#flash-message-modal-container").removeClass("d-none alert-danger alert-success alert-secondary alert-info alert-warning");
        $('#flashmessagecontent').html('Un groupe avec le même ,nom existe déjà pour ce domaine');
        $('#flash-message-modal-container').addClass('alert-danger');
        }
      })
    });
  }

  /*
   * Check if a group with a label allready exists for a domain
   */
  checkIfDomainGroupExists(name, domain) {

    var formData = new FormData();
    var retVal = false;
    formData.append('domain', domain);
    formData.append('name', name);
    return axios.post(this.urlValue, formData)
            .then(function (response) {
              return response.data.result
      
            })
            .catch(function (error) {
              console.log(error);
              return false;
            });

  }

}
