import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    handle(event) {
        const submitter = event.submitter || this.element.querySelector("[type=submit]")
        if (this.element.dataset.opencodeSubmitting === "true") {
            event.preventDefault()
            return
        }
        this.element.dataset.opencodeSubmitting = "true"
        if (submitter) {
            submitter.disabled = true
            submitter.classList.add("opacity-50", "cursor-not-allowed")
        }
    }
}
