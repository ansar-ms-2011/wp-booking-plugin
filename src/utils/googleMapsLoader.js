// utils/googleMapsLoader.js
class GoogleMapsLoader {
    constructor() {
        this.promise = null
        this.loading = false
        this.loaded = false
    }

    load(apiKey) {
        if (this.loaded) {
            return Promise.resolve()
        }

        if (this.loading) {
            return this.promise
        }

        this.loading = true
        this.promise = new Promise((resolve, reject) => {
            // Check if already loaded
            if (window.google && window.google.maps) {
                this.loaded = true
                this.loading = false
                resolve()
                return
            }

            // Create script element
            const script = document.createElement('script')
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&v=beta`
            script.async = true
            script.defer = true

            script.onload = () => {
                this.loaded = true
                this.loading = false
                resolve()
            }

            script.onerror = (error) => {
                this.loading = false
                reject(error)
            }

            document.head.appendChild(script)
        })

        return this.promise
    }

    isLoaded() {
        return this.loaded && window.google && window.google.maps
    }
}

export default new GoogleMapsLoader()