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
        deleteButton.classList.add('btn', 'btn-danger', 'col-sm-2');

        deleteButton.innerText = Translator.trans('Message.Actions.Delete');

        deleteButton.setAttribute('data-action', 'click->add-ip-address#remove');
        div.appendChild(deleteButton);

        this.containerTarget.appendChild(div);
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('div').remove();
    }

    validateIp(event) {
        const input = event.target;
        const ip = input.value;
        const ipPattern = /^(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)$/;

        if (!ipPattern.test(ip) && ip != "") {

            $('#dialog-confirm').dialog('option', 'title', $(this).find(':selected').data('dialog-title'));
            $('#dialog-confirm').data("type-action-confirm", "form");
            $('#dialog-confirm').data("form-to-confirm", "massive-actions-form");
            $("#dialog-content").html(Translator.trans('Message.Actions.ipAddressInvalid'));

            $("#dialog-confirm").dialog("open");
            input.value = '';
        } 
    }
}
