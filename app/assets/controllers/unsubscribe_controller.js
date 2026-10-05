import { Controller } from '@hotwired/stimulus';

// Opens the unsubscribe page of the list (HTTPS or mailto) once the user has
// confirmed, then submits the form to record the request in AgentJ.
export default class extends Controller {
    static values = {
        url: String,
    };

    submit(event) {
        event.preventDefault();

        if (this.urlValue.startsWith('mailto:')) {
            window.location.href = this.urlValue;
        } else if (this.urlValue) {
            window.open(this.urlValue, '_blank', 'noopener');
        }

        this.element.requestSubmit();
    }
}
