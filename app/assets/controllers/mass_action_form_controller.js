import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    submit () {
        const confirmMessage = this.element.dataset.messageConfirm;
        const formData = new FormData(this.element);
        const checkboxesTargetClass = this.element.dataset.checkboxTargetClass;
        const checkboxes = document.querySelectorAll(`input[type='checkbox'].${checkboxesTargetClass}:checked`);

        checkboxes.forEach(checkbox => {
            this.element.append(checkbox);
        });

        if (confirm(confirmMessage)) {
            this.element.requestSubmit();
        }
    }
}
