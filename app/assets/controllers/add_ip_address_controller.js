import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['container', 'prototype'];

    connect(){
        this.index = this.containerTarget.children.length;
    }

    add(event) {
        event.preventDefault();
        let prototype = this.prototypeTarget.dataset.prototype;
        let newForm = prototype.replace(/__name__/g, this.index);
        this.index++;

        let div = document.createElement('div');
        div.innerHTML = newForm;
        this.containerTarget.appendChild(div);
    }
}
