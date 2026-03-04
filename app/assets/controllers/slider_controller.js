import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["slider", "output"]

    connect() {
        this.refresh();
    }

    refresh() {
        this.outputTarget.innerHTML = this.sliderTarget.value;
    }
}
