<template>
  <div class="booking-widget-wrapper">
    <!-- Loading state -->
    <div v-if="!mapsApiLoaded" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading booking system...</p>
    </div>

    <!-- Main widget -->
    <div v-else class="booking-widget">
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
          <h3 class="step-heading">Choose your ride</h3>
          <p class="step-description">Select a vehicle that fits your journey</p>
          <hr>
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
          <h3 class="step-heading">Who's travelling?</h3>
          <p class="step-description">Please provide your contact details</p>
          <hr>
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
          <h3 class="step-heading">Journey Details</h3>
          <p class="step-description">Set pickup, dropoff & travel preferences</p>
          <hr>
          <!-- Pickup Location -->
          <div class="form-row">
            <div class="form-group full-width">
              <LocationInput
                  v-model="formData.pickupLocation"
                  label="Pickup Location"
                  placeholder="Enter pickup address"
                  :required="true"
                  :error-message="fieldErrors.pickupLocation"
                  input-ref="pickupInput"
                  field-key="pickup"
                  @validate="validateField"
                  @blur="validateField"
              />
            </div>
          </div>

          <!-- Dropoff Location -->
          <div class="form-row">
            <div class="form-group full-width">
              <LocationInput
                  v-model="formData.dropoffLocation"
                  label="Dropoff Location"
                  placeholder="Enter dropoff address"
                  :required="true"
                  :error-message="fieldErrors.dropoffLocation"
                  input-ref="dropoffInput"
                  field-key="dropoff"
                  @validate="validateField"
                  @blur="validateField"
              />
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
              <LocationInput
                  v-model="formData.returnPickupLocation"
                  label="Return Pickup Location"
                  placeholder="Address for return trip pickup"
                  :required="true"
                  :error-message="fieldErrors.returnPickupLocation"
                  input-ref="returnInput"
                  field-key="return"
                  @validate="validateField"
                  @blur="validateField"
              />
              <small style="color: #4b5563;">Usually from drop off point, but you can specify custom</small>
            </div>
          </div>

          <div class="summary-text" v-if="formData.selectedCar">
            <i class="fas fa-car"></i> <strong>Selected:</strong> {{ getCarName() }} &nbsp;|&nbsp;
            <i class="fas fa-user"></i> {{ formData.firstName || 'Guest' }} {{ formData.lastName || '' }}
          </div>
          <div class="button-group">
            <button class="btn btn-secondary" @click="prevStep"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn btn-primary btn-success" @click="submitBooking"> Submit <i
                class="fas fa-check-circle"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LocationInput from './LocationInput.vue'
import googleMapsLoader from '../utils/googleMapsLoader'

export default {
  name: 'BookingWidget',
  components: {
    LocationInput
  },
  data() {
    return {
      currentStep: 1,
      mapsApiLoaded: false,
      settings: null,
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
      }
    }
  },
  async mounted() {
    await this.loadSettings()
    await this.loadGoogleMapsAPI()
    await this.loadCars()
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
        console.error('Failed to load settings:', error);
      }
    },

    async loadGoogleMapsAPI() {
      try {
        // const apiKey = 'AIzaSyBWk6v169JWz27vhH5inP3qvL_THc3RXGs'
        const apiKey = this.settings?.googleMapsApiKey;
        console.log('Google Maps API Key:', apiKey);
        await googleMapsLoader.load(apiKey)
        this.mapsApiLoaded = true
        console.log('Google Maps API loaded successfully')
      } catch (error) {
        console.error('Failed to load Google Maps API:', error)
        this.$message.error('Failed to load maps service. Please refresh the page.')
      }
    },

    async loadCars() {
      try {
        const response = await this.$api.get('/get-cars');

        console.log('Cars fetched:', response.data);
        if (response.data.success) {
          this.carOptions = [];
          response.data.data?.forEach(car => {
            this.carOptions.push({
              id: car.id,
              name: car.name,
              desc: `Luggage: ${car.max_luggage} | Seats: ${car.max_passengers}`,
              icon: 'fas fa-car'
            })
          })
        } else {
          console.error('Failed to load cars:', response.data.message);
        }
      } catch (error) {
        console.error('Failed to load settings:', error);
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
        if (!this.formData.firstName.trim())
          error = 'First name is required';
        else if (this.formData.firstName.trim().length < 2)
          error = 'At least 2 characters';
      } else if (field === 'lastName') {
        if (!this.formData.lastName.trim())
          error = 'Last name is required';
      } else if (field === 'primaryPhone') {
        const phone = this.formData.primaryPhone.trim();
        if (!phone)
          error = 'Primary phone is required';
        else if (!/^[\+\d\s\-\(\)]{7,20}$/.test(phone))
          error = 'Enter a valid phone number';
      } else if (field === 'email') {
        const email = this.formData.email.trim();
        if (!email)
          error = 'Email is required';
        else if (!/^\S+@\S+\.\S+$/.test(email))
          error = 'Enter a valid email address';
      } else if (field === 'pickupLocation') {
        if (!this.formData.pickupLocation.trim())
          error = 'Pickup location is required';
      } else if (field === 'dropoffLocation') {
        if (!this.formData.dropoffLocation.trim())
          error = 'Dropoff location is required';
      } else if (field === 'pickupDateTime') {
        if (!this.formData.pickupDateTime)
          error = 'Pickup date & time is required';
        else {
          const selectedDate = new Date(this.formData.pickupDateTime);
          if (selectedDate < new Date())
            error = 'Pickup time must be in the future';
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
      console.log('Validating step 3:', this.formData);
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
        console.error('Please fix errors in the location & time section');
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
    }
  },
  watch: {
    'formData.returnPickupLocation'(newVal) {
      console.log('Parent received update:', newVal)
    }
  }
}
</script>

<style scoped>
/* Keep all existing styles except remove place-suggestions and location input specific styles */
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
  padding: 1rem 2rem 2rem 2rem;
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
  margin: 0.5rem 0 1.2rem;
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
  font-weight: 500;
  color: #dc2626;
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


.booking-widget-wrapper {
  padding: 5px;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  min-height: 500px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  background: white;
  border-radius: 2rem;
  padding: 2rem;
}

.loading-state i {
  font-size: 3rem;
  color: #1e4f8a;
  margin-bottom: 1rem;
}

.loading-state p {
  color: #475569;
  font-size: 1rem;
}

/* Rest of your existing styles remain the same */
.booking-widget {
  background: yellow;
  max-width: 980px;
  width: 100%;
  border-radius: 2rem;
  box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  margin: 0 auto;
}
.step-heading{
  margin-top: 2px;
  margin-bottom: 2px;
}
.step-description{
  color: #475569;
  font-size: 12px;
  margin-top: 2px;
  margin-bottom: 0;
}
</style>