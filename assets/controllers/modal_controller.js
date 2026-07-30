import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["backdrop", "content"]

    open() {
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove("hidden")
        }
        if (this.hasContentTarget) {
            this.contentTarget.classList.remove("hidden")
        }
        document.body.style.overflow = "hidden"
    }

    close() {
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.add("hidden")
        }
        if (this.hasContentTarget) {
            this.contentTarget.classList.add("hidden")
        }
        document.body.style.overflow = ""
    }

    closeBackdrop(event) {
        if (event.target === this.backdropTarget) {
            this.close()
        }
    }
}
