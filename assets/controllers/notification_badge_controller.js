import { Controller } from '@hotwired/stimulus';

/**
 * notification_badge_controller
 *
 * Polling cada 30s del badge de notificaciones no leídas.
 * Actualiza el contador sin recargar la página completa.
 *
 * Uso:
 *   <div data-controller="notification-badge"
 *        data-notification-badge-url-value="/notifications/badge"
 *        data-notification-badge-interval-value="30000">
 *   </div>
 *
 * HTML esperado del endpoint:
 *   <turbo-frame id="notification-badge">
 *     <span class="...">3</span>
 *   </turbo-frame>
 */
export default class extends Controller {
    static values = {
        url: { type: String, default: '/notifications/badge' },
        interval: { type: Number, default: 30000 },
    }

    /** @type {number|null} */
    _timer = null

    /** @type {AbortController|null} */
    _abortController = null

    connect() {
        this._startPolling()
    }

    disconnect() {
        this._stopPolling()
    }

    /**
     * Inicia el polling periódico para actualizar el badge.
     */
    _startPolling() {
        // Primera actualización inmediata
        this._fetchAndUpdate()

        // Luego cada N milisegundos
        this._timer = setInterval(() => {
            this._fetchAndUpdate()
        }, this.intervalValue)
    }

    /**
     * Detiene el polling.
     */
    _stopPolling() {
        if (this._timer !== null) {
            clearInterval(this._timer)
            this._timer = null
        }

        if (this._abortController !== null) {
            this._abortController.abort()
            this._abortController = null
        }
    }

    /**
     * Fetch asíncrono del badge y actualización del DOM.
     */
    async _fetchAndUpdate() {
        // Cancelar request pendiente anterior
        if (this._abortController !== null) {
            this._abortController.abort()
        }

        this._abortController = new AbortController()

        try {
            const response = await fetch(this.urlValue, {
                signal: this._abortController.signal,
                headers: { 'Accept': 'text/html' },
            })

            if (!response.ok) {
                console.warn('[NotificationBadge] Error fetching badge:', response.status)
                return
            }

            const html = await response.text()

            // Extraer el contenido del turbo-frame de la respuesta
            const parser = new DOMParser()
            const doc = parser.parseFromString(html, 'text/html')
            const newFrame = doc.querySelector('turbo-frame#notification-badge')

            if (!newFrame) {
                console.warn('[NotificationBadge] No se encontró turbo-frame#notification-badge en la respuesta')
                return
            }

            // Reemplazar el frame existente
            const currentFrame = this.element.querySelector('turbo-frame#notification-badge')
            if (currentFrame) {
                currentFrame.innerHTML = newFrame.innerHTML
            } else {
                // Si no hay frame (primera carga), insertarlo
                this.element.appendChild(newFrame.cloneNode(true))
            }
        } catch (error) {
            // Ignorar errores de aborte (por reconexión)
            if (error instanceof DOMException && error.name === 'AbortError') {
                return
            }
            console.warn('[NotificationBadge] Error:', error)
        }
    }
}
