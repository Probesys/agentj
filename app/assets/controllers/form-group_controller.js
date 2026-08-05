import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets() {
        return ['domain', 'group', 'priority', 'priorityAlert', 'btnSubmit'];
    }

    static values = {
        urlCheckPriority: String
    }

    // check if the priority if not yet in use
    checkPriorityValidity(event) {

        const domainId = this.domainTarget.value;
        const groupId = this.groupTarget.value
        const priorityTarget = this.priorityTarget;
        const priorityAlertTarget = this.priorityAlertTarget;
        const btnSubmitTarget = this.btnSubmitTarget;

        btnSubmitTarget.disabled = true
        if (domainId) {
            var result = false;
            const formData = new FormData();
            formData.append('groupId', groupId);
            formData.append('domainId', domainId);
            formData.append('priority', priorityTarget.value);

            fetch(this.urlCheckPriorityValue, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData
            }).then((response) => {
                return response.json();
            }).then((data) => {
                if (data.status == 'success') {
                    priorityAlertTarget.classList.add("d-none");
                    btnSubmitTarget.disabled = false;
                } else {
                    priorityAlertTarget.classList.remove("d-none");
                    priorityAlertTarget.innerHTML = data.message;
                    btnSubmitTarget.disabled = true;
                }
            });
        }
    }
}
