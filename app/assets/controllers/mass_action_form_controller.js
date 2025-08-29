import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    submit () {
        const confirmMessage = this.element.dataset.messageConfirm;
        const checkboxesGroup = this.element.dataset.massActionGroup;
        const checkboxes = document.querySelectorAll(`input[type='checkbox'][data-mass-action-group="${checkboxesGroup}":checked`);

        checkboxes.forEach(checkbox => {
            this.element.append(checkbox);
        });

        if (confirm(confirmMessage)) {
            this.element.requestSubmit();
        }
    }
}
