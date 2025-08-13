import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    submit (event) {
        event.preventDefault();
        const confirmMessage = this.element.dataset.messageConfirm;
        const checkboxesGroup = this.element.dataset.massActionGroup;
        const checkboxes = document.querySelectorAll(`input[type='checkbox'][data-mass-action-group="${checkboxesGroup}"]:checked`);

        checkboxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = checkbox.name;
            hiddenInput.value = checkbox.value;
            this.element.append(hiddenInput);
        });

        if (confirm(confirmMessage)) {
            this.element.requestSubmit();
        }
    }
}
