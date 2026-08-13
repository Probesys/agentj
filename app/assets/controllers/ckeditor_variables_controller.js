import { Controller } from "@hotwired/stimulus"

export default class extends Controller {

    insert (event) {
        const editor = window.editor;
        if (!editor) {
            return;
        }

        const variable = event.currentTarget.dataset.variable;
        if (!variable) {
            console.error('data-variable is undefined on element');
            return;
        }

        const html = variable.startsWith('[URL_')
            ? `<a href="${variable}">${variable}</a>`
            : variable;

        const viewFragment = editor.data.processor.toView(html);
        const modelFragment = editor.data.toModel(viewFragment);
        editor.model.insertContent(modelFragment);
    }
}
