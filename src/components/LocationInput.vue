<!-- LocationInput.vue -->
<template>
  <div class="location-input-wrapper" :class="{ 'has-error': hasError }">
    <label v-if="label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <div class="input-wrapper" :style="{ position: 'relative' }">
      <input
          :ref="inputRef"
          type="text"
          :placeholder="placeholder"
          :value="modelValue"
          @input="handleInput"
          @blur="handleBlur"
          @keydown="handleKeydown"
          autocomplete="off"
          class="location-input"
      />

      <!-- Suggestions dropdown -->
      <ul class="place-suggestions" v-if="suggestions.length">
        <li
            v-for="(suggestion, idx) in suggestions"
            :key="idx"
            :class="{ 'active': activeSuggestionIndex === idx }"
            @click="selectSuggestion(suggestion)"
            @mouseenter="activeSuggestionIndex = idx"
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
    modelValue: {
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
      debounceTimer: null
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
  methods: {
    async initAutocomplete() {
      // Wait for Places API to be ready
      await this.waitForPlacesAPI()
    },

    async waitForPlacesAPI() {
      if (window.google?.maps?.importLibrary) {
        await google.maps.importLibrary('places')
        return
      }

      // If API not loaded yet, wait for it
      return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
          if (window.google?.maps?.importLibrary) {
            clearInterval(checkInterval)
            google.maps.importLibrary('places').then(resolve)
          }
        }, 100)
      })
    },

    async fetchPlaceSuggestions(query) {
      if (query.length < 3) {
        this.suggestions = []
        return
      }

      try {
        const { AutocompleteSessionToken, AutocompleteSuggestion } = await google.maps.importLibrary('places')

        // Create new session token for each search
        this.sessionToken = new AutocompleteSessionToken()

        const response = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
          input: query,
          sessionToken: this.sessionToken
        })

        this.suggestions = response.suggestions
            .map(suggestion => {
              if (suggestion.placePrediction) {
                return {
                  placeId: suggestion.placePrediction.placeId,
                  text: suggestion.placePrediction.text?.text || '',
                  fullText: suggestion.placePrediction.fullText?.text || ''
                }
              } else if (suggestion.queryPrediction) {
                return {
                  placeId: null,
                  text: suggestion.queryPrediction.text?.text || '',
                  fullText: suggestion.queryPrediction.text?.text || ''
                }
              }
              return null
            })
            .filter(s => s !== null)

        this.activeSuggestionIndex = -1
      } catch (err) {
        console.error(`Error fetching suggestions:`, err)
        this.suggestions = []
      }
    },

    async selectSuggestion(suggestion) {
      try {
        let address = suggestion.text

        // Fetch detailed address if we have a placeId
        if (suggestion.placeId) {
          const { FetchPlaceRequest } = await google.maps.importLibrary('places')

          const request = {
            placeId: suggestion.placeId,
            sessionToken: this.sessionToken,
            fields: ['formattedAddress']
          }

          const { place } = await FetchPlaceRequest.fetchPlace(request)
          if (place.formattedAddress) {
            address = place.formattedAddress
          }
        }

        // Emit the selected value
        this.$emit('update:modelValue', address)
        this.$emit('place-selected', address, this.fieldKey)

        // Update input field value
        const input = this.$el.querySelector('.location-input')
        if (input) {
          input.value = address
        }

        // Clear suggestions
        this.suggestions = []
        this.activeSuggestionIndex = -1

        // Trigger validation
        this.$emit('validate', this.fieldKey)

      } catch (err) {
        console.error('Error fetching place details:', err)
        // Fallback to suggestion text
        this.$emit('update:modelValue', suggestion.text)
        this.$emit('place-selected', suggestion.text, this.fieldKey)
        this.suggestions = []
      }
    },

    handleInput(event) {
      const query = event.target.value
      this.$emit('update:modelValue', query)

      // Debounce API calls
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer)
      }

      this.debounceTimer = setTimeout(() => {
        this.fetchPlaceSuggestions(query)
      }, 300)
    },

    handleBlur() {
      // Delay clearing suggestions to allow click events
      setTimeout(() => {
        this.suggestions = []
        this.activeSuggestionIndex = -1
      }, 200)
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
          activeElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
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
  padding: 0.75rem 1rem;
  border-radius: 1rem;
  border: 1.5px solid #e2e8f0;
  font-family: inherit;
  font-size: 0.9rem;
  transition: 0.2s;
}

.location-input:focus {
  outline: none;
  border-color: #1e4f8a;
  box-shadow: 0 0 0 3px rgba(30, 79, 138, 0.2);
}

.has-error .location-input {
  border-color: #dc2626;
}

.error-msg {
  font-size: 0.9rem;
  color: #dc2626;
  margin-top: 0.2rem;
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