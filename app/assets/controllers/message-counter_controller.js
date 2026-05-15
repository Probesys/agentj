import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
    };

    static targets = [
        'untreated',
        'spammed',
        'virus',
        'authorized',
        'banned',
        'deleted',
        'restored',
    ];

    connect() {
        this.load();
    }

    load() {

        this.constructor.targets.forEach(name => {
            this[`${name}Target`].textContent = '...';
        });

        fetch(this.urlValue, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(counts => {
                Object.entries(counts).forEach(([key, value]) => {
                    const targetName = `${key}Target`;
                    this[targetName].textContent = value;
                });
            })
            .catch(() => {
                this.constructor.targets.forEach(name => {
                    this[`${name}Target`].textContent = '#err';
                });
            });
    }
}
