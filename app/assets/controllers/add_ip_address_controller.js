// assets/controllers/add_ip_address_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["container", "prototype"];

    connect() {
        this.index = this.containerTarget.children.length;
    }

    add(event) {
        event.preventDefault();
        let prototype = this.prototypeTarget.dataset.prototype;
        let newForm = prototype.replace(/__name__/g, this.index);
        this.index++;

        let div = document.createElement('div');
        div.innerHTML = newForm;

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.classList.add('btn', 'btn-danger', 'ml-2');

        deleteButton.innerText = 'Delete';
        
        deleteButton.setAttribute('data-action', 'click->add-ip-address#remove');
        div.appendChild(deleteButton);

        this.containerTarget.appendChild(div);
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('div').remove();
    }
}
