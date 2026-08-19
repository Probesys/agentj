// assets/controllers/add_ip_address_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["container", "prototype"];
    static values = {
        errorLabel: String,
        errorDuplicatedLabel: String,
        errorBadFormatLabel: String,
        removeLabel: String
    }

    connect() {
        this.index = this.containerTarget.children.length;
    }


    onSubmit(event) {
        const allInputs = this.containerTarget.querySelectorAll('input');
        const allValid = Array.from(allInputs).every((input) => this.validateIp({ target: input }));

        if (!allValid) {
            event.preventDefault();
        }
    }

    add(event) {
        event.preventDefault();
        let prototype = this.prototypeTarget.dataset.prototype;
        let newForm = prototype.replace(/__name__/g, this.index);
        this.index++;

        let div = document.createElement('div');
        div.classList.add('domain-relay-row');
        div.innerHTML = newForm;

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.classList.add('btn', 'btn-danger', 'col-sm-2');

        deleteButton.innerText = this.removeLabelValue;

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

            $('#dialog-confirm').dialog('option', 'title', this.errorLabelValue);
            $("#dialog-content").html(this.errorBadFormatLabelValue);
            $('#dialog-confirm').dialog('option', 'buttons', []);
            $('#dialog-confirm').dialog('option', 'close', function () {
                input.focus();
            });
            $("#dialog-confirm").dialog("open");
            return false;
        }

        const allInputs = this.containerTarget.querySelectorAll('input');

        let duplicateFound = false;

        allInputs.forEach(function (ipInput) {
            if (ipInput !== input && ipInput.value.trim() === ip) {
                duplicateFound = true;
            }
        });

        if (duplicateFound) {
            $('#dialog-confirm').dialog('option', 'title', this.errorLabelValue);
            $("#dialog-content").html(this.errorDuplicatedLabelValue);

            $('#dialog-confirm').dialog('option', 'buttons', []);
            $('#dialog-confirm').dialog('option', 'close', function () {
                input.focus();
            });
            $("#dialog-confirm").dialog("open");
            return false;

        } else {
            input.setCustomValidity('');
        }

        return true;
    }
}
