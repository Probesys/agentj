
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['resultButton', 'lastExecution'];

    static values = {
        interval: { type: Number, default: 2000 },
        url: String,
        successLabel: String,
        errorLabel: String,
    }

    connect() {
        this.startChecking();
    }

    startChecking() {
        this.checkImportStatus();

        this.checkingTimer = setInterval(() => {
            this.checkImportStatus();
        }, this.intervalValue);
    }

    async checkImportStatus() {
        try {
            const url = this.urlValue;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.isImportOngoing) {
                this.element.dataset.importOngoing = true;
            } else {
                this.element.dataset.importOngoing = false;

                if (data.lastExecution.type === 'Success') {
                    this.resultButtonTarget.title = this.successLabelValue;
                    this.resultButtonTarget.querySelector('.fas').classList.add('fa-info-circle');
                    this.resultButtonTarget.querySelector('.fas').classList.remove('fa-exclamation-triangle');
                    this.resultButtonTarget.hidden = false;
                } else if (data.lastExecution.type === 'Error') {
                    this.resultButtonTarget.title = this.errorLabelValue;
                    this.resultButtonTarget.querySelector('.fas').classList.remove('fa-info-circle');
                    this.resultButtonTarget.querySelector('.fas').classList.add('fa-exclamation-triangle');
                    this.resultButtonTarget.hidden = false;
                } else {
                    this.resultButtonTarget.hidden = true;
                }
            }

            if (data.lastExecution && data.lastExecution.date) {
                const date = new Date(data.lastExecution.date.date);

                const formatter = new Intl.DateTimeFormat(document.documentElement.lang, {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: data.lastExecution.date.timezone,
                });

                this.lastExecutionTarget.textContent = formatter.format(date);
            } else {
                this.lastExecutionTarget.textContent = '—';
            }
        } catch (error) {
            console.error('Error while checking import status', error);
        }
    }

    disconnect() {
        if (this.checkingTimer) {
            clearInterval(this.checkingTimer);
            this.checkingTimer = null;
        }
    }
}
