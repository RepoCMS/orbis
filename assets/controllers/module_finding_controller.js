import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = [
        'treeContainer', 'modelSelect', 'componentList',
        'modulePath', 'componentName', 'findingType',
        'submitBtn', 'feedback', 'modelLabel',
    ]

    static values = {
        treeUrl: String,
        ticketId: String,
        findingType: { type: String, default: 'faulty' },
    }

    connect() {
        this.selectedPath = null
        this.selectedModuleLabel = null
        this.loadTree()
    }

    async loadTree() {
        const model = this.modelSelectTarget.value
        const url = this.treeUrlValue + '?model=' + encodeURIComponent(model)

        this.feedbackTarget.textContent = 'Cargando árbol de módulos…'
        this.feedbackTarget.className = 'text-xs text-gray-400'
        this.treeContainerTarget.innerHTML = ''

        try {
            const res = await fetch(url)
            const json = await res.json()
            const tree = json.data || []

            if (tree.length === 0) {
                this.feedbackTarget.textContent = 'No hay módulos definidos para este modelo.'
                return
            }

            this.feedbackTarget.textContent = ''
            this.renderTree(tree, this.treeContainerTarget, 0)
        } catch (e) {
            this.feedbackTarget.textContent = 'Error al cargar el árbol.'
            this.feedbackTarget.className = 'text-xs text-red-500'
        }
    }

    renderTree(nodes, container, depth) {
        for (const node of nodes) {
            const wrapper = document.createElement('div')
            wrapper.className = 'select-none'

            const row = document.createElement('div')
            row.className =
                'flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer group'
            row.style.paddingLeft = (depth * 20 + 8) + 'px'

            const hasChildren = node.children && node.children.length > 0
            const hasComponents = node.components && node.components.length > 0

            if (hasChildren) {
                const btn = document.createElement('button')
                btn.type = 'button'
                btn.className =
                    'w-4 h-4 flex items-center justify-center shrink-0 text-gray-400 hover:text-gray-700 transition-colors'
                btn.innerHTML =
                    '<svg class="w-3 h-3 transition-transform duration-150" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>'
                btn.addEventListener('click', (e) => {
                    e.stopPropagation()
                    const icon = btn.querySelector('svg')
                    const childContainer = wrapper.querySelector('.child-container')
                    if (childContainer) {
                        childContainer.classList.toggle('hidden')
                        if (icon) icon.classList.toggle('rotate-90')
                    }
                })
                row.appendChild(btn)
            } else {
                const spacer = document.createElement('span')
                spacer.className = 'w-4 h-4 shrink-0'
                row.appendChild(spacer)
            }

            const label = document.createElement('span')
            label.className = 'text-sm text-gray-800 flex-1 min-w-0 truncate'
            label.textContent = node.label + '  ' + node.code
            row.appendChild(label)

            if (hasComponents || hasChildren) {
                const count = document.createElement('span')
                count.className = 'text-xs text-gray-400 shrink-0'
                count.textContent = hasComponents
                    ? (node.components.length + ' componentes')
                    : (node.children.length + ' hijos')
                row.appendChild(count)
            }

            row.addEventListener('click', () => this.selectModule(node, row))

            wrapper.appendChild(row)

            if (hasComponents) {
                const childContainer = document.createElement('div')
                childContainer.className = 'child-container hidden'
                childContainer.style.paddingLeft = (depth * 20 + 28) + 'px'

                for (const comp of node.components) {
                    const chip = document.createElement('button')
                    chip.type = 'button'
                    chip.textContent = comp
                    chip.className =
                        'inline-block m-1 px-2.5 py-1 rounded text-xs font-medium border transition-colors ' +
                        'bg-gray-50 text-gray-600 border-gray-200 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300'
                    chip.addEventListener('click', (e) => {
                        e.stopPropagation()
                        this.selectComponent(comp, node)
                        this.highlightComponent(chip)
                    })
                    childContainer.appendChild(chip)
                }
                wrapper.appendChild(childContainer)
            } else if (hasChildren) {
                const childContainer = document.createElement('div')
                childContainer.className = 'child-container hidden'
                this.renderTree(node.children, childContainer, depth + 1)
                wrapper.appendChild(childContainer)
            }

            container.appendChild(wrapper)
        }
    }

    selectModule(node, row) {
        this.selectedPath = this.buildPath(node)
        this.selectedModuleLabel = node.label

        this.treeContainerTarget.querySelectorAll('.selected-module').forEach((el) => {
            el.classList.remove('bg-blue-50', 'text-blue-700', 'selected-module')
        })
        row.classList.add('bg-blue-50', 'text-blue-700', 'selected-module')

        if (node.components && node.components.length > 0) {
            this.componentListTarget.innerHTML = ''
            this.componentListTarget.classList.remove('hidden')
            this.modelLabelTarget.textContent =
                'Módulo: ' + node.label + ' (' + node.code + ') — Seleccione un componente:'

            for (const comp of node.components) {
                const chip = document.createElement('button')
                chip.type = 'button'
                chip.textContent = comp
                chip.className =
                    'inline-block m-1 px-3 py-1.5 rounded text-sm font-medium border transition-colors ' +
                    'bg-gray-50 text-gray-600 border-gray-200 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300'
                chip.addEventListener('click', () => {
                    this.selectComponent(comp, node)
                    this.highlightComponent(chip)
                })
                this.componentListTarget.appendChild(chip)
            }
        } else {
            this.componentListTarget.classList.add('hidden')
            this.selectComponent('', node)
        }
    }

    selectComponent(comp, node) {
        const path = this.buildPath(node)
        this.modulePathTarget.value = path
        this.componentNameTarget.value = comp
        this.componentNameTarget.dataset.label = comp || node.label
        this.submitBtnTarget.disabled = false
    }

    highlightComponent(chip) {
        this.componentListTarget.querySelectorAll('.selected-component').forEach((el) => {
            el.classList.remove('bg-blue-100', 'text-blue-800', 'border-blue-400', 'selected-component')
        })
        chip.classList.add('bg-blue-100', 'text-blue-800', 'border-blue-400', 'selected-component')
    }

    buildPath(node) {
        let path = node.code
        let parent = this.treeContainerTarget.querySelector('[data-code="' + node.code + '"]')
        return path
    }

    async submitFinding(event) {
        event.preventDefault()

        const modulePath = this.modulePathTarget.value
        const componentName = this.componentNameTarget.value
        const findingType = this.findingTypeTarget.value
        const notes = this.findingTypeTarget.form.querySelector('[name="notes"]')?.value || ''

        if (!modulePath) {
            this.feedbackTarget.textContent = 'Seleccione un módulo.'
            this.feedbackTarget.className = 'text-xs text-red-500'
            return
        }
        if (!componentName) {
            this.feedbackTarget.textContent = 'Seleccione un componente.'
            this.feedbackTarget.className = 'text-xs text-red-500'
            return
        }

        this.submitBtnTarget.disabled = true
        this.submitBtnTarget.textContent = 'Guardando…'

        try {
            const res = await fetch(
                '/api/v1/support/tickets/' + this.ticketIdValue + '/module-findings',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        modulePath: modulePath,
                        componentName: componentName,
                        findingType: findingType,
                        notes: notes,
                    }),
                },
            )

            if (res.ok) {
                this.feedbackTarget.textContent = 'Hallazgo registrado correctamente.'
                this.feedbackTarget.className = 'text-xs text-green-600'
                this.resetForm()
                this.closeModal()
                window.location.reload()
            } else {
                const err = await res.json()
                this.feedbackTarget.textContent = err.error || 'Error al guardar.'
                this.feedbackTarget.className = 'text-xs text-red-500'
            }
        } catch (e) {
            this.feedbackTarget.textContent = 'Error de conexión.'
            this.feedbackTarget.className = 'text-xs text-red-500'
        } finally {
            this.submitBtnTarget.disabled = false
            this.submitBtnTarget.textContent = 'Guardar hallazgo'
        }
    }

    resetForm() {
        this.modulePathTarget.value = ''
        this.componentNameTarget.value = ''
        this.selectedPath = null
        this.selectedModuleLabel = null
        this.componentListTarget.innerHTML = ''
        this.componentListTarget.classList.add('hidden')
        this.treeContainerTarget.querySelectorAll('.selected-module').forEach((el) => {
            el.classList.remove('bg-blue-50', 'text-blue-700', 'selected-module')
        })
    }

    closeModal() {
        const modal = this.element.closest('[id="modal-module-finding"]')
        if (modal) modal.classList.add('hidden')
    }
}
