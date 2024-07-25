import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["container", "template"]

  connect() {
    this.index = this.containerTarget.children.length
  }

  addQuota(event) {
    event.preventDefault()
    const content = this.templateTarget.innerHTML.replace(/__name__/g, this.index)
    const element = document.createRange().createContextualFragment(content)
    this.containerTarget.appendChild(element)
    this.index++
  }

  removeQuota(event) {
    event.preventDefault()
    const item = event.target.closest('.quota-item')
    item.remove()
  }
}