import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    connect() {
        this.editor = CKEDITOR.replace(this.element);
    }

    disconnect() {
        if (this.editor) {
            CKEDITOR.remove(this.editor);
        }
    }
}
