import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        interval: { type: Number, default: 5000 },
        url: String
    }

    connect() {
        this.startChecking();
    }

    startChecking() {
        this.checkReleaseStatus();

        this.checkingTimer = setInterval(() => {
            this.checkReleaseStatus();
        }, this.intervalValue);
    }

    async checkReleaseStatus() {
        try {
            const url = this.urlValue;
            const response = await fetch(url, {
                method: 'GET',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.released) {
                this.onMessageReleased(data);
            }
        } catch (error) {
            console.error('Error while checking release status', error);
        }
    }

    onMessageReleased(data) {
        if (this.checkingTimer) {
            clearInterval(this.checkingTimer);
            this.checkingTimer = null;
        }

        const row = this.element.closest('tr');
        if (row) {
            row.remove();
        }
    }
}
