import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    insert (event) {
        if (!CKEDITOR.currentInstance) {
            return;
        }

        const variable = event.currentTarget.dataset.variable;
        if (!variable) {
            console.error('data-variable is undefined on element');
            return;
        }

        if (variable.startsWith('[URL_')) {
            CKEDITOR.currentInstance.insertHtml(
                `<a href="${variable}">${variable}</a>`
            );
        } else {
            CKEDITOR.currentInstance.insertHtml(variable);
        }
    }
}
