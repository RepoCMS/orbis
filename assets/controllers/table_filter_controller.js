import { Controller } from "@hotwired/stimulus"

/**
 * table-filter controller
 *
 * Modes:
 *  - Server-side: form submits via GET (client list). Badge/panel auto-open
 *    driven by data-active-filters-count attribute on the controller element.
 *  - Client-side: filters rows instantly without page reload (employee/user/role).
 *    Add data-table-filter-target="searchInput" on the text input and
 *    data-table-filter-target="filterSelect" data-col="N" data-match="exact|contains"
 *    on each select. Add data-table-filter-target="row" on each <tr>.
 */
export default class extends Controller {
    static targets = [
        "panel",
        "badge",
        "searchInput",
        "row",
        "filterSelect",
        "noResults",
    ]
    static values = { open: Boolean }

    connect() {
        // Server-side mode: auto-open panel if there are active filters
        const serverCount = parseInt(
            this.element.dataset.activeFiltersCount ?? "0"
        )
        if (serverCount > 0) {
            this.openValue = true
        }
        this._setBadge(serverCount)
    }

    openValueChanged() {
        if (this.hasPanelTarget) {
            this.panelTarget.classList.toggle("hidden", !this.openValue)
        }
    }

    toggle() {
        this.openValue = !this.openValue
    }

    // Client-side row filtering
    filterRows() {
        if (!this.hasRowTarget) return

        const searchTerm = this.hasSearchInputTarget
            ? this.searchInputTarget.value.toLowerCase().trim()
            : ""

        let visible = 0
        this.rowTargets.forEach((row) => {
            const text = row.textContent.toLowerCase()
            const matchesSearch = !searchTerm || text.includes(searchTerm)

            const matchesSelects = this.filterSelectTargets.every((select) => {
                if (!select.value) return true
                const colIdx = select.dataset.col
                const exact = select.dataset.match === "exact"

                if (colIdx !== undefined) {
                    const cell = row.querySelectorAll("td")[parseInt(colIdx)]
                    if (!cell) return true
                    const cellText = cell.textContent.trim().toLowerCase()
                    return exact
                        ? cellText === select.value.toLowerCase()
                        : cellText.includes(select.value.toLowerCase())
                }
                return text.includes(select.value.toLowerCase())
            })

            const show = matchesSearch && matchesSelects
            row.style.display = show ? "" : "none"
            if (show) visible++
        })

        if (this.hasNoResultsTarget) {
            this.noResultsTarget.classList.toggle("hidden", visible > 0)
        }
        this._updateActiveCount()
    }

    _updateActiveCount() {
        const searchActive =
            this.hasSearchInputTarget &&
            this.searchInputTarget.value.trim()
                ? 1
                : 0
        const filtersActive = this.filterSelectTargets.filter(
            (s) => s.value
        ).length
        this._setBadge(searchActive + filtersActive)
    }

    _setBadge(count) {
        if (this.hasBadgeTarget) {
            this.badgeTarget.textContent = count
            this.badgeTarget.classList.toggle("hidden", count === 0)
        }
    }
}
