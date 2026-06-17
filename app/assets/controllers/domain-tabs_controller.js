import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets = ['domain', 'auth', 'filter', 'alert'];

    connect() {
        const hash = window.location.hash;

        if (hash) {
            this.setActiveTab(hash.slice(1));
        } else {
            this.setActiveTab('domain');
        }
    }

    setActiveTab(tabName) {
        const elem = $(this[tabName + 'Target']);
        elem.tab('show');
    }
}
