<!-- LocationInput.vue -->
<template>
  <div class="location-input-wrapper">
    <label v-if="label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <div class="input-wrapper" :style="{ position: 'relative' }">
      <input
          :ref="inputRef"
          type="text"
          :placeholder="placeholder"
          :value="internalValue"
          @input="handleInput"
          @blur="handleBlur"
          @keydown="handleKeydown"
          autocomplete="off"
          class="location-input"
          :class="{ 'with-loading': isFetching }"
          :disabled="!apiLoaded"
      />

      <!-- Loading spinner inside input -->
      <div v-if="isFetching" class="loading-spinner">
        <svg class="spinner" viewBox="0 0 24 24" width="16" height="16">
          <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3"/>
          <path class="spinner-path" fill="none" stroke="currentColor" stroke-width="3" d="M12 2 A10 10 0 0 1 22 12"/>
        </svg>
      </div>

      <!-- Suggestions dropdown -->
      <ul class="place-suggestions" v-if="suggestions.length && apiLoaded && !isSelectingSuggestion">
        <li
            v-for="(suggestion, idx) in suggestions"
            :key="suggestion.placeId || suggestion.text + idx"
            :class="{ 'active': activeSuggestionIndex === idx }"
            @click.stop="selectSuggestion(suggestion)"
            @mouseenter="activeSuggestionIndex = idx"
            @mousedown.prevent
        >
          <i class="fas fa-map-marker-alt"></i>
          <span>{{ suggestion.text }}</span>
        </li>
      </ul>
    </div>
    <div class="error-msg" v-if="errorMessage">{{ errorMessage }}</div>
  </div>
</template>

<script>
export default {
  name: 'LocationInput',
  props: {
    value: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'Enter address'
    },
    required: {
      type: Boolean,
      default: false
    },
    errorMessage: {
      type: String,
      default: ''
    },
    inputRef: {
      type: String,
      required: true
    },
    fieldKey: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      suggestions: [],
      activeSuggestionIndex: -1,
      sessionToken: null,
      debounceTimer: null,
      apiLoaded: false,
      apiLoadAttempts: 0,
      isFetching: false,
      placesService: null,
      internalValue: this.value,
      isSelectingSuggestion: false
    }
  },
  computed: {
    hasError() {
      return !!this.errorMessage
    }
  },
  mounted() {
    this.initAutocomplete()
  },
  beforeUnmount() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer)
    }
  },
  watch: {
    value(newVal) {
      // Sync external changes to internal value
      if (this.internalValue !== newVal) {
        this.internalValue = newVal
      }
    }
  },
  methods: {
    async initAutocomplete() {
      await this.waitForPlacesAPI()
    },

    async waitForPlacesAPI() {
      // Check if Google Maps API is already loaded
      if (typeof window !== 'undefined' && window.google?.maps?.importLibrary) {
        try {
          await google.maps.importLibrary('places')
          this.apiLoaded = true
          console.log('Places API loaded successfully')
          return
        } catch (error) {
          console.error('Error loading places library:', error)
        }
      }

      // If not loaded, wait for it with retries
      return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
          this.apiLoadAttempts++

          if (typeof window !== 'undefined' && window.google?.maps?.importLibrary) {
            clearInterval(checkInterval)
            google.maps.importLibrary('places')
                .then(() => {
                  this.apiLoaded = true
                  console.log('Places API loaded successfully')
                  resolve()
                })
                .catch((error) => {
                  console.error('Error loading places library:', error)
                  resolve()
                })
          } else if (this.apiLoadAttempts > 50) {
            // Timeout after 5 seconds (50 * 100ms)
            clearInterval(checkInterval)
            console.error('Google Places API failed to load after multiple attempts')
            this.apiLoaded = false
            resolve()
          }
        }, 100)
      })
    },

    async fetchPlaceSuggestions(query) {
      if (!this.apiLoaded) {
        console.warn('Places API not loaded yet')
        return
      }

      if (query.length < 3) {
        this.suggestions = []
        return
      }

      this.isFetching = true

      try {
        const {AutocompleteSessionToken, AutocompleteSuggestion} = await google.maps.importLibrary('places')

        if (!this.sessionToken) {
          this.sessionToken = new AutocompleteSessionToken()
        }

        const {suggestions} = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
          input: query,
          sessionToken: this.sessionToken
        })

        this.suggestions = suggestions
            .map(suggestion => {
              if (suggestion.placePrediction) {
                return {
                  placeId: suggestion.placePrediction.placeId,
                  text: suggestion.placePrediction.text?.text || '',
                  fullText: suggestion.placePrediction.fullText?.text || '',
                  types: suggestion.placePrediction.types || []
                }
              } else if (suggestion.queryPrediction) {
                return {
                  placeId: null,
                  text: suggestion.queryPrediction.text?.text || '',
                  fullText: suggestion.queryPrediction.text?.text || '',
                  types: []
                }
              }
              return null
            })
            .filter(s => s !== null)

        this.activeSuggestionIndex = -1
      } catch (err) {
        console.error(`Error fetching suggestions:`, err)
        this.suggestions = []
      } finally {
        this.isFetching = false
      }
    },

    async selectSuggestion(suggestion) {
      // Set flag to prevent blur from clearing suggestions immediately
      this.isSelectingSuggestion = true

      if (!this.apiLoaded) {
        console.warn('Places API not loaded yet')
        this.isSelectingSuggestion = false
        return
      }

      try {
        let address = suggestion.text
        let latitude = null
        let longitude = null

        // Fetch detailed address if we have a placeId
        if (suggestion.placeId) {
          const { Place } = await google.maps.importLibrary('places')

          const place = new Place({
            id: suggestion.placeId,
            requestedLanguage: 'en'
          })

          await place.fetchFields({
            fields: [
              'formattedAddress',
              'displayName',
              'location'
            ]
          })

          if (place.formattedAddress) {
            address = place.formattedAddress
          } else if (place.displayName) {
            address = place.displayName
          }

          // Extract coordinates
          if (place.location) {
            latitude = place.location.lat()
            longitude = place.location.lng()
          }
        }

        // Update reactive value
        this.internalValue = address

        // Emit address update
        this.$emit('input', address)

        // Emit full place data
        this.$emit('placeSelected', {
          address,
          latitude,
          longitude,
          placeId: suggestion.placeId
        }, this.fieldKey)

        // Clear suggestions
        this.suggestions = []
        this.activeSuggestionIndex = -1

        // Trigger validation
        this.$emit('validate', this.fieldKey)

      } catch (err) {
        console.error('Error fetching place details:', err)

        this.internalValue = suggestion.text

        this.$emit('update:modelValue', suggestion.text)

        this.$emit('place-selected', {
          address: suggestion.text,
          latitude: null,
          longitude: null,
          placeId: suggestion.placeId || null
        }, this.fieldKey)

        this.suggestions = []
      } finally {
        // Reset flag after a short delay
        setTimeout(() => {
          this.isSelectingSuggestion = false
        }, 300)
      }
    },

    handleInput(event) {
      const query = event.target.value

      this.internalValue = query
      this.$emit('input', query)

      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer)
      }

      this.debounceTimer = setTimeout(() => {
        this.fetchPlaceSuggestions(query)
      }, 300)
    },

    handleBlur() {
      // Only clear suggestions if we're not in the middle of selecting one
      if (!this.isSelectingSuggestion) {
        // Delay clearing suggestions to allow click events
        setTimeout(() => {
          if (!this.isSelectingSuggestion) {
            this.suggestions = []
            this.activeSuggestionIndex = -1
          }
        }, 200)
      }

      this.$emit('blur', this.fieldKey)
    },

    handleKeydown(event) {
      if (this.suggestions.length === 0) return

      if (event.key === 'ArrowDown') {
        event.preventDefault()
        this.activeSuggestionIndex = Math.min(this.activeSuggestionIndex + 1, this.suggestions.length - 1)
        this.scrollSuggestionIntoView()
      } else if (event.key === 'ArrowUp') {
        event.preventDefault()
        this.activeSuggestionIndex = Math.max(this.activeSuggestionIndex - 1, -1)
        this.scrollSuggestionIntoView()
      } else if (event.key === 'Enter' && this.activeSuggestionIndex >= 0) {
        event.preventDefault()
        this.selectSuggestion(this.suggestions[this.activeSuggestionIndex])
      } else if (event.key === 'Escape') {
        this.suggestions = []
        this.activeSuggestionIndex = -1
      }
    },

    scrollSuggestionIntoView() {
      this.$nextTick(() => {
        const activeElement = document.querySelector('.place-suggestions li.active')
        if (activeElement) {
          activeElement.scrollIntoView({block: 'nearest', behavior: 'smooth'})
        }
      })
    }
  }
}
</script>

<style scoped>
.location-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  width: 100%;
}

.location-input-wrapper label {
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  color: #334155;
}

.required {
  color: #c2410c;
  margin-left: 2px;
}

.input-wrapper {
  position: relative;
  width: 100%;
}

.location-input {
  width: 100%;
  padding: 0.75rem;
  padding-right: 2.5rem; /* Add space for spinner */
  border-radius: 1rem;
  border: 2px solid #e2e8f0;
  font-family: inherit;
  font-size: 0.9rem;
  transition: 0.2s;
  box-sizing: border-box;
}

.location-input.with-loading {
  padding-right: 2.5rem;
}

.input-wrapper,
.place-suggestions {
  box-sizing: border-box;
}

.location-input:focus {
  outline: none;
  border-color: #1e4f8a;
  box-shadow: 0 0 0 3px rgba(30, 79, 138, 0.2);
}

.location-input:disabled {
  background-color: #f7fafc;
  cursor: not-allowed;
}

/* Loading spinner inside input */
.loading-spinner {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.spinner {
  animation: spin 1s linear infinite;
  color: #64748b;
}

.spinner-circle {
  opacity: 0.25;
}

.spinner-path {
  opacity: 0.75;
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}

.error-msg {
  font-size: 0.9rem;
  font-weight: 500;
  color: #dc2626;
}

.place-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  margin: 0;
  padding: 0;
  list-style: none;
}

.place-suggestions li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.15s;
  font-size: 0.85rem;
}

.place-suggestions li:hover,
.place-suggestions li.active {
  background: #f1f5f9;
}

.place-suggestions li i {
  color: #64748b;
  width: 16px;
  font-size: 0.9rem;
}

.place-suggestions li span {
  flex: 1;
  color: #1e293b;
}
</style>