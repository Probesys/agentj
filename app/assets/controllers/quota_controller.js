import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["container", "template"]

  connect() {
    this.updateIndexes()
  }

  addQuota(event) {
    event.preventDefault()
    const content = this.templateTarget.innerHTML.replace(/__name__/g, this.containerTarget.children.length)
    const element = document.createRange().createContextualFragment(content)
    this.containerTarget.appendChild(element)
    this.updateIndexes()
  }

  removeQuota(event) {
    event.preventDefault();
    const item = event.target.closest('.quota-item');
    item.remove();
    this.updateIndexes();
  }

  updateIndexes() {
    this.containerTarget.querySelectorAll('.quota-item').forEach((item, index) => {
      item.querySelectorAll('input').forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/\[quotas\]\[\d+\]/, `[quotas][${index}]`))
        }
      })
    })
  }
}