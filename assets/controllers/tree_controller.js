import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['toggle', 'children', 'branch']

    toggleBranch(event) {
        const branch = event.currentTarget.closest('[data-tree-target="branch"]')
        if (!branch) return

        const childrenContainer = branch.querySelector('[data-tree-target="children"]')
        const toggle = branch.querySelector('[data-tree-target="toggle"]')
        if (!childrenContainer || !toggle) return

        const icon = toggle.querySelector('svg')
        childrenContainer.classList.toggle('hidden')

        if (icon) {
            icon.classList.toggle('rotate-90')
        }
    }
}
