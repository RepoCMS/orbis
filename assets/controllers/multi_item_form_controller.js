import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['container', 'template', 'count', 'row']

    connect() {
        this._reindex()
    }

    addRow() {
        const index = this.rowTargets.length
        const html = this.templateTarget.innerHTML.replaceAll('__index__', index)
        this.containerTarget.insertAdjacentHTML('beforeend', html)
        this._reindex()
    }

    removeRow(event) {
        const row = event.target.closest('[data-multi-item-form-target="row"]')
        if (row) {
            row.remove()
            this._reindex()
        }
    }

    toggleSerialized(event) {
        const row = event.currentTarget.closest('[data-multi-item-form-target="row"]')
        if (!row) return
        const checked = event.currentTarget.checked
        const serialField = row.querySelector('[data-multi-item-form-target="serialField"]')
        const quantityField = row.querySelector('[data-multi-item-form-target="quantityField"]')
        if (serialField) {
            serialField.style.display = checked ? '' : 'none'
            serialField.querySelectorAll('input, select, textarea').forEach((el) => {
                el.required = checked
                if (!checked) el.value = ''
            })
        }
        if (quantityField) {
            quantityField.style.display = checked ? 'none' : ''
            quantityField.querySelectorAll('input, select, textarea').forEach((el) => {
                el.required = !checked
            })
        }
    }

    _reindex() {
        this.rowTargets.forEach((row, i) => {
            row.dataset.index = i
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/g, `[${i}]`)
            })
            const removeBtn = row.querySelector("[data-action*='multi-item-form#removeRow']")
            if (removeBtn) {
                removeBtn.style.display = this.rowTargets.length > 1 ? '' : 'none'
            }
        })
        if (this.hasCountTarget) {
            this.countTarget.value = this.rowTargets.length
        }
    }
}
