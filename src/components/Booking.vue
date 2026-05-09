<template>
  <div class="booking-widget-wrapper">
    <!-- Car Booking Widget - Three Step Form -->
    <div class="booking-widget">
      <!-- Steps Header -->
      <div class="steps-header">
        <div class="step-tab" :class="{'active': currentStep === 1, 'completed': currentStep > 1}">
          <span class="step-num">1</span> <span>Select Car</span>
        </div>
        <div class="step-tab" :class="{'active': currentStep === 2, 'completed': currentStep > 2}">
          <span class="step-num">2</span> <span>Personal Details</span>
        </div>
        <div class="step-tab" :class="{'active': currentStep === 3}">
          <span class="step-num">3</span> <span>Location & Time</span>
        </div>
      </div>

      <!-- Form Content -->
      <div class="form-container">
        <!-- STEP 1: Car Selection (6 cars) -->
        <div class="step-pane" :class="{'active-pane': currentStep === 1}">
          <h3 style="margin-bottom: 0.25rem;">Choose your ride</h3>
          <p style="margin-bottom: 1rem; color: #475569;">Select a vehicle that fits your journey</p>
          <div class="car-grid">
            <div v-for="car in carOptions" :key="car.id" class="car-card"
                 :class="{'selected': formData.selectedCar === car.id}" @click="selectCar(car.id)">
              <div class="car-icon"><i :class="car.icon"></i></div>
              <div class="car-name">{{ car.name }}</div>
              <div class="car-desc">{{ car.desc }}</div>
            </div>
          </div>
          <div class="error-msg" v-if="stepErrors.car && !formData.selectedCar">Please select a car to continue.</div>
          <div class="button-group">
            <div></div>
            <button class="btn btn-primary" @click="nextStep(1)">Next: Personal Info <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- STEP 2: Personal Details -->
        <div class="step-pane" :class="{'active-pane': currentStep === 2}">
          <h3 style="margin-bottom: 0.25rem;">Who's driving?</h3>
          <p style="margin-bottom: 1rem; color: #475569;">Please provide your contact details</p>
          <div class="form-row">
            <div class="form-group">
              <label>First Name <span class="required">*</span></label>
              <input type="text" v-model="formData.firstName" placeholder="John" @blur="validateField('firstName')">
              <div class="error-msg" v-if="fieldErrors.firstName">{{ fieldErrors.firstName }}</div>
            </div>
            <div class="form-group">
              <label>Last Name <span class="required">*</span></label>
              <input type="text" v-model="formData.lastName" placeholder="Doe" @blur="validateField('lastName')">
              <div class="error-msg" v-if="fieldErrors.lastName">{{ fieldErrors.lastName }}</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Primary Phone <span class="required">*</span></label>
              <input type="tel" v-model="formData.primaryPhone" placeholder="+1 234 567 8900"
                     @blur="validateField('primaryPhone')">
              <div class="error-msg" v-if="fieldErrors.primaryPhone">{{ fieldErrors.primaryPhone }}</div>
            </div>
            <div class="form-group">
              <label>Secondary Phone</label>
              <input type="tel" v-model="formData.secondaryPhone" placeholder="Optional">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group full-width">
              <label>Email Address <span class="required">*</span></label>
              <input type="email" v-model="formData.email" placeholder="john.doe@example.com"
                     @blur="validateField('email')">
              <div class="error-msg" v-if="fieldErrors.email">{{ fieldErrors.email }}</div>
            </div>
          </div>
          <div class="button-group">
            <button class="btn btn-secondary" @click="prevStep"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn btn-primary" @click="nextStep(2)">Next: Location & Time <i
                class="fas fa-arrow-right"></i></button>
          </div>
        </div>

        <!-- STEP 3: Pickup/Dropoff + Roundtrip + Date/Time -->
        <div class="step-pane" :class="{'active-pane': currentStep === 3}">
          <h3 style="margin-bottom: 0.25rem;">Journey Details</h3>
          <p style="margin-bottom: 1rem; color: #475569;">Set pickup, dropoff & travel preferences</p>
          <div class="form-row">
            <div class="form-group full-width">
              <label>Pickup Location <span class="required">*</span></label>
              <input type="text" ref="pickupInput" placeholder="Enter pickup address" v-model="formData.pickupLocation"
                     @blur="validateField('pickupLocation')">
              <div class="error-msg" v-if="fieldErrors.pickupLocation">{{ fieldErrors.pickupLocation }}</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group full-width">
              <label>Dropoff Location <span class="required">*</span></label>
              <input type="text" ref="dropoffInput" placeholder="Enter dropoff address"
                     v-model="formData.dropoffLocation" @blur="validateField('dropoffLocation')">
              <div class="error-msg" v-if="fieldErrors.dropoffLocation">{{ fieldErrors.dropoffLocation }}</div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Pickup Date & Time <span class="required">*</span></label>
              <input type="datetime-local" v-model="formData.pickupDateTime" @blur="validateField('pickupDateTime')">
              <div class="error-msg" v-if="fieldErrors.pickupDateTime">{{ fieldErrors.pickupDateTime }}</div>
            </div>
            <div class="form-group checkbox-group" style="justify-content: flex-start; margin-top: 1.5rem;">
              <input type="checkbox" id="roundtrip" v-model="formData.isRoundtrip">
              <label for="roundtrip" style="text-transform: none; font-weight: 500;">Round trip (return journey)</label>
            </div>
          </div>
          <!-- Return location - conditional rendering when roundtrip checked -->
          <div class="return-location" v-if="formData.isRoundtrip">
            <div class="form-group full-width">
              <label>Return Pickup Location (for return journey) <span class="required">*</span></label>
              <input type="text" ref="returnInput" placeholder="Address for return trip pickup"
                     v-model="formData.returnPickupLocation" @blur="validateField('returnPickupLocation')">
              <div class="error-msg" v-if="fieldErrors.returnPickupLocation">{{
                  fieldErrors.returnPickupLocation
                }}
              </div>
              <small style="color: #4b5563;">Usually from drop off point, but you can specify custom</small>
            </div>
          </div>
          <div class="summary-text" v-if="formData.selectedCar">
            <i class="fas fa-car"></i> <strong>Selected:</strong> {{ getCarName() }} &nbsp;|&nbsp;
            <i class="fas fa-user"></i> {{ formData.firstName || 'Guest' }} {{ formData.lastName || '' }}
          </div>
          <div class="button-group">
            <button class="btn btn-secondary" @click="prevStep"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn btn-primary btn-success" @click="submitBooking"> Complete Booking <i
                class="fas fa-check-circle"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BookingWidget',
  data() {
    return {
      currentStep: 1,
      formData: {
        selectedCar: null,
        firstName: '',
        lastName: '',
        primaryPhone: '',
        secondaryPhone: '',
        email: '',
        pickupLocation: '',
        dropoffLocation: '',
        pickupDateTime: '',
        isRoundtrip: false,
        returnPickupLocation: ''
      },
      carOptions: [
        {id: 'sedan', name: 'Sedan', desc: 'Comfort 5 seats', icon: 'fas fa-car-side'},
        {id: 'mini_sedan', name: 'Mini Sedan', desc: 'Economy 4 seats', icon: 'fas fa-car'},
        {id: 'suv', name: 'SUV', desc: 'Spacious 7 seats', icon: 'fas fa-truck'},
        {id: 'luxury_sedan', name: 'Luxury Sedan', desc: 'Premium class', icon: 'fas fa-car'},
        {id: 'hatchback', name: 'Hatchback', desc: 'Compact & agile', icon: 'fas fa-caravan'},
        {id: 'ev', name: 'Electric SUV', desc: 'Zero emission', icon: 'fas fa-charging-station'}
      ],
      fieldErrors: {
        firstName: '',
        lastName: '',
        primaryPhone: '',
        email: '',
        pickupLocation: '',
        dropoffLocation: '',
        pickupDateTime: '',
        returnPickupLocation: ''
      },
      stepErrors: {
        car: false
      },
      // Store autocomplete instances
      pickupAutocomplete: null,
      dropoffAutocomplete: null,
      returnAutocomplete: null
    }
  },
  watch: {
    'formData.isRoundtrip'(val) {
      if (val) {
        this.$nextTick(() => {
          this.initAutocomplete();
        });
      }
    }
  },
  mounted() {
    // Load settings from parent (optional, keep original loadSettings if needed)
    this.loadSettings();
    // Initialize Google Places Autocomplete
    this.initAutocomplete();
  },
  beforeDestroy() {
    // Clean up autocomplete listeners if needed
    if (this.pickupAutocomplete) {
      google.maps.event.clearInstanceListeners(this.pickupAutocomplete);
    }
    if (this.dropoffAutocomplete) {
      google.maps.event.clearInstanceListeners(this.dropoffAutocomplete);
    }
    if (this.returnAutocomplete) {
      google.maps.event.clearInstanceListeners(this.returnAutocomplete);
    }
  },
  methods: {
    // Original loadSettings method preserved
    async loadSettings() {
      try {
        const response = await this.$api.get('/settings');
        if (response.data.success) {
          this.settings = response.data.data;
        }
      } catch (error) {
        this.$message.error('Failed to load settings');
      }
    },
    async saveSettings() {
      try {
        const response = await this.$api.post('/settings', this.settings);
        if (response.data.success) {
          this.$message.success('Settings saved successfully');
        }
      } catch (error) {
        this.$message.error('Failed to save settings');
      }
    },
    // Car selection
    selectCar(carId) {
      this.formData.selectedCar = carId;
      this.stepErrors.car = false;
    },
    getCarName() {
      const car = this.carOptions.find(c => c.id === this.formData.selectedCar);
      return car ? car.name : 'Not selected';
    },
    // Validation for individual fields
    validateField(field) {
      let error = '';
      if (field === 'firstName') {
        if (!this.formData.firstName.trim()) error = 'First name is required';
        else if (this.formData.firstName.trim().length < 2) error = 'At least 2 characters';
      } else if (field === 'lastName') {
        if (!this.formData.lastName.trim()) error = 'Last name is required';
      } else if (field === 'primaryPhone') {
        const phone = this.formData.primaryPhone.trim();
        if (!phone) error = 'Primary phone is required';
        else if (!/^[\+\d\s\-\(\)]{7,20}$/.test(phone)) error = 'Enter a valid phone number';
      } else if (field === 'email') {
        const email = this.formData.email.trim();
        if (!email) error = 'Email is required';
        else if (!/^\S+@\S+\.\S+$/.test(email)) error = 'Enter a valid email address';
      } else if (field === 'pickupLocation') {
        if (!this.formData.pickupLocation.trim()) error = 'Pickup location is required';
      } else if (field === 'dropoffLocation') {
        if (!this.formData.dropoffLocation.trim()) error = 'Dropoff location is required';
      } else if (field === 'pickupDateTime') {
        if (!this.formData.pickupDateTime) error = 'Pickup date & time is required';
        else {
          const selectedDate = new Date(this.formData.pickupDateTime);
          if (selectedDate < new Date()) error = 'Pickup time must be in the future';
        }
      } else if (field === 'returnPickupLocation') {
        if (this.formData.isRoundtrip && !this.formData.returnPickupLocation.trim()) {
          error = 'Return pickup location is required for round trip';
        }
      }
      this.fieldErrors[field] = error;
      return !error;
    },
    // Validate step 1 (car selection)
    validateStep1() {
      if (!this.formData.selectedCar) {
        this.stepErrors.car = true;
        return false;
      }
      return true;
    },
    // Validate step 2 (personal details)
    validateStep2() {
      const fields = ['firstName', 'lastName', 'primaryPhone', 'email'];
      let isValid = true;
      fields.forEach(field => {
        if (!this.validateField(field)) isValid = false;
      });
      return isValid;
    },
    // Validate step 3 (locations + datetime + return if roundtrip)
    validateStep3() {
      let isValid = true;
      if (!this.validateField('pickupLocation')) isValid = false;
      if (!this.validateField('dropoffLocation')) isValid = false;
      if (!this.validateField('pickupDateTime')) isValid = false;
      if (this.formData.isRoundtrip) {
        if (!this.validateField('returnPickupLocation')) isValid = false;
      }
      return isValid;
    },
    // Navigation: next step
    nextStep(step) {
      if (step === 1 && this.validateStep1()) {
        this.currentStep = 2;
      } else if (step === 2 && this.validateStep2()) {
        this.currentStep = 3;
        // Re-initialize autocomplete when step 3 becomes active (ensures DOM ready)
        this.$nextTick(() => {
          this.initAutocomplete();
        });
      }
    },
    prevStep() {
      if (this.currentStep > 1) {
        this.currentStep--;
      }
    },
    // Submit final booking with frontend validation
    submitBooking() {
      if (!this.validateStep3()) {
        this.$message.error('Please fix errors in the location & time section');
        return;
      }
      // Prepare final booking payload
      const bookingPayload = {
        car: this.formData.selectedCar,
        carName: this.getCarName(),
        customer: {
          firstName: this.formData.firstName,
          lastName: this.formData.lastName,
          primaryPhone: this.formData.primaryPhone,
          secondaryPhone: this.formData.secondaryPhone,
          email: this.formData.email
        },
        trip: {
          pickupLocation: this.formData.pickupLocation,
          dropoffLocation: this.formData.dropoffLocation,
          pickupDateTime: this.formData.pickupDateTime,
          isRoundtrip: this.formData.isRoundtrip,
          returnPickupLocation: this.formData.isRoundtrip ? this.formData.returnPickupLocation : null
        }
      };
      console.log('Booking submitted:', bookingPayload);
      this.$message.success('Booking confirmed! Check console for details.');
      // You can uncomment and use your existing API pattern:
      // this.$api.post('/bookings', bookingPayload).then(...)
    },
    // Initialize Google Places Autocomplete for location inputs
    async initAutocomplete() {
      console.log('initAutocomplete called');
      
      try {
        // Use vue2-google-maps promise to wait for API loading
        await this.$gmapApiPromiseLazy();
        
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
          console.warn('Google Maps API still not fully available after promise');
          return;
        }

        console.log('Google Maps API ready, initializing autocomplete instances');
      // Pickup input
      const pickupInput = this.$refs.pickupInput;
      if (pickupInput && !this.pickupAutocomplete) {
        console.log('Initializing pickup autocomplete');
        this.pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput, {
          types: ['geocode', 'establishment'],
          componentRestrictions: {country: 'us'} // optional, adjust as needed
        });
        this.pickupAutocomplete.addListener('place_changed', () => {
          const place = this.pickupAutocomplete.getPlace();
          if (place && place.formatted_address) {
            this.formData.pickupLocation = place.formatted_address;
            this.validateField('pickupLocation');
          }
        });
      }
      // Dropoff input
      const dropoffInput = this.$refs.dropoffInput;
      if (dropoffInput && !this.dropoffAutocomplete) {
        console.log('Initializing dropoff autocomplete');
        this.dropoffAutocomplete = new google.maps.places.Autocomplete(dropoffInput, {
          types: ['geocode', 'establishment']
        });
        this.dropoffAutocomplete.addListener('place_changed', () => {
          const place = this.dropoffAutocomplete.getPlace();
          if (place && place.formatted_address) {
            this.formData.dropoffLocation = place.formatted_address;
            this.validateField('dropoffLocation');
          }
        });
      }
      // Return location input (only exists when roundtrip is true, but we initialize if ref exists)
      const returnInput = this.$refs.returnInput;
      if (returnInput && !this.returnAutocomplete) {
        console.log('Initializing return autocomplete');
        this.returnAutocomplete = new google.maps.places.Autocomplete(returnInput, {
          types: ['geocode', 'establishment']
        });
        this.returnAutocomplete.addListener('place_changed', () => {
          const place = this.returnAutocomplete.getPlace();
          if (place && place.formatted_address) {
            this.formData.returnPickupLocation = place.formatted_address;
            this.validateField('returnPickupLocation');
          }
        });
      }
    } catch (error) {
      console.error('Error initializing Google Maps Autocomplete:', error);
    }
  }
},
}
</script>

<style scoped>
.booking-widget-wrapper {
  padding: 5px;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Booking widget styles */
.booking-widget {
  background: yellow;
  max-width: 980px;
  width: 100%;
  border-radius: 2rem;
  box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  margin: 0 auto;
}

.steps-header {
  display: flex;
  border-bottom: 1px solid #4b4d50;
}

.step-tab {
  flex: 1;
  text-align: center;
  padding: 1.2rem 0.5rem;
  font-weight: 600;
  font-size: 1rem;
  color: #64748b;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  cursor: default;
}

.step-tab.active {
  color: #1e4f8a;
  border-bottom: 3px solid #1e4f8a;
  font-weight: 700;
}

.step-tab .step-num {
  display: inline-block;
  background: #cbd5e1;
  color: #1e293b;
  border-radius: 40px;
  width: 26px;
  height: 26px;
  line-height: 26px;
  font-size: 0.85rem;
  font-weight: 700;
  margin-right: 8px;
  text-align: center;
}

.step-tab.active .step-num {
  background: #1e4f8a;
  color: white;
}

.step-tab.completed .step-num {
  background: #2c7a4d;
  color: white;
}

.form-container {
  padding: 2rem;
}

.step-pane {
  display: none;
  animation: fadeIn 0.25s ease;
}

.step-pane.active-pane {
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1.2rem;
  margin: 1.8rem 0 1.2rem;
}

.car-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 1.2rem;
  padding: 1rem 0.8rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.car-card:hover {
  border-color: #94a3b8;
  transform: translateY(-3px);
}

.car-card.selected {
  border-color: #1e4f8a;
  background: #f0f9ff;
}

.car-icon {
  font-size: 2.8rem;
  margin-bottom: 0.5rem;
  color: #2c3e66;
}

.car-name {
  font-weight: 700;
  font-size: 1.1rem;
}

.car-desc {
  font-size: 0.75rem;
  color: #5b6e8c;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.2rem;
  margin-bottom: 1.3rem;
}

.form-group {
  flex: 1 1 220px;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group.full-width {
  flex: 1 1 100%;
}

label {
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

input {
  padding: 0.75rem 1rem;
  border-radius: 1rem;
  border: 1.5px solid #e2e8f0;
  font-family: inherit;
  font-size: 0.9rem;
  transition: 0.2s;
}

input:focus {
  outline: none;
  border-color: #1e4f8a;
  box-shadow: 0 0 0 3px rgba(30, 79, 138, 0.2);
}

.error-msg {
  font-size: 0.9rem;
  color: #dc2626;
  margin-top: 0.2rem;
}

/* Fix Google Places Autocomplete dropdown visibility in modals/overflow containers */
.pac-container {
  z-index: 9999 !important;
  border-radius: 0 0 1rem 1rem;
  border-top: none;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  font-family: inherit;
}

.pac-item {
  padding: 8px 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.pac-item:hover {
  background-color: #f1f5f9;
}

.pac-item-query {
  font-size: 14px;
  color: #1e293b;
}

.pac-icon {
  margin-right: 10px;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0.6rem 0 0.2rem;
}

.checkbox-group input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.return-location {
  margin-top: 0.8rem;
  padding-left: 0.5rem;
  border-left: 3px solid #cbd5e1;
  transition: 0.2s;
}

.button-group {
  display: flex;
  justify-content: space-between;
  margin-top: 2rem;
}

.btn {
  padding: 0.8rem 1.8rem;
  border-radius: 2.5rem;
  font-weight: 600;
  border: none;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary {
  background: #1e4f8a;
  color: white;
}

.btn-primary:hover {
  background: #0f3b64;
  transform: translateY(-2px);
}

.btn-secondary {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-success {
  background: #2c7a4d;
}

.summary-text {
  background: #f1f5f9;
  border-radius: 1rem;
  padding: 1rem;
  font-size: 0.9rem;
  margin-bottom: 1.2rem;
}

@media (max-width: 680px) {
  .form-container {
    padding: 1.2rem;
  }

  .car-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .step-tab span:not(.step-num) {
    display: none;
  }
}
</style>