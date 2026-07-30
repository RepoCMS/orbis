import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["contentFrame"]
    static values = { currentRange: String }

    connect() {
        document.addEventListener("turbo:before-fetch-response", () => {
            this._updateRangeFromParams()
        })
    }

    changeRange(event) {
        const range = event.currentTarget.dataset.range
        if (!range) return
        this.currentRangeValue = range
        this._reload()
    }

    _updateRangeFromParams() {
        const params = new URLSearchParams(window.location.search)
        const range = params.get("range")
        if (range && range !== this.currentRangeValue) {
            this.currentRangeValue = range
        }
    }

    _reload() {
        if (!this.hasContentFrameTarget) return
        const url = new URL(window.location.href)
        url.searchParams.set("range", this.currentRangeValue)
        this.contentFrameTarget.src = url.toString()
    }
}
