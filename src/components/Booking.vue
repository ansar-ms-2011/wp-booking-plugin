<template>
  <div class="booking-widget-wrapper">
    <!-- Loading state -->
    <div v-if="!mapsApiLoaded" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading booking system...</p>
      <div v-if="carLoadingError">
        <p class="error-msg">We got an error while loading list of available cars.</p>
        <button @click="loadCars" class="btn btn-primary">Load Cars</button>
      </div>
    </div>

    <!-- Main Booking widget -->
    <template v-else>
      <div class="booking-widget-heading-wrapper">
        <div class="booking-widget-heading">
          <span>START YOUR JOURNEY</span>
        </div>
      </div>
      <div class="booking-widget">
        <template v-if="!submitSuccessMessage" class="booking-form">
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
              <h3 class="step-heading">Choose your vehicle</h3>
              <p class="step-description">Select a vehicle that fits your journey</p>
              <div class="step-line">
                <hr>
              </div>
              <div class="car-grid">
                <div v-for="car in carOptions" :key="car.id" class="car-card"
                     :class="{'selected': formData.selectedCar === car.id}"
                     :style="{ backgroundImage: `url(${car.image_url})` }"
                     @click="selectCar(car.id)">

                  <div class="car-bottom">
                    <div class="car-name">
                      {{ car.name }}
                    </div>
                    <div class="car-desc">
                      <i class="fas fa-ellipsis-h"></i> {{ car.desc }}
                    </div>
                  </div>

                  <!-- CENTER GREEN CHECKMARK when this car is selected (exactly center of image) -->
                  <div v-if="formData.selectedCar === car.id" class="selected-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
                      <path d="M20 60 L45 85 L100 25" stroke="#FFF" stroke-width="12" fill="none" stroke-linecap="round"
                            stroke-linejoin="round"/>
                    </svg>
                  </div>
                </div>
              </div>
              <div class="error-msg" v-if="stepErrors.car && !formData.selectedCar">Please select a car to continue.
              </div>
              <div class="button-group-step-1">
                <div></div>
                <button class="btn btn-primary btn-step-1" @click="nextStep(1)">Next: Personal Info <i
                    class="fas fa-arrow-right"></i>
                </button>
              </div>
            </div>

            <!-- STEP 2: Personal Details -->
            <div class="step-pane" :class="{'active-pane': currentStep === 2}">
              <h3 class="step-heading">Who's travelling?</h3>
              <p class="step-description">Please provide complete details of the passenger(s).</p>
              <div class="step-line">
                <hr>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Full Name <span class="required">*</span></label>
                  <input type="text" v-model="formData.fullName" placeholder="John Doe"
                         @blur="validateField('fullName')">
                  <div class="error-msg" v-if="fieldErrors.fullName">{{ fieldErrors.fullName }}</div>
                </div>
                <div class="form-group">
                  <label>Email Address <span class="required">*</span></label>
                  <input type="email" v-model="formData.email" placeholder="john.doe@example.com"
                         @blur="validateField('email')">
                  <div class="error-msg" v-if="fieldErrors.email">{{ fieldErrors.email }}</div>
                </div>
                <div class="form-group">
                  <label>Primary Phone <span class="required">*</span></label>
                  <vue-tel-input
                      @input="handlePhoneInput"
                      v-model="formData.phoneNumber"
                      :dropdownOptions="dropdownOptions"
                      :inputOptions="inputOptions"
                  ></vue-tel-input>
                  <div class="error-msg" v-if="fieldErrors.primaryPhone">{{ fieldErrors.primaryPhone }}</div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Secondary Phone</label>
                  <vue-tel-input
                      @input="handlePhoneInputSecondary"
                      v-model="formData.phoneNumberSecondary"
                      :dropdownOptions="dropdownOptions"
                      :inputOptions="inputOptions"
                  ></vue-tel-input>
                </div>
                <div class="form-group">
                  <label>Total Passengers <span class="required">*</span></label>
                  <input type="number" v-model="formData.passengers" placeholder="Enter number of passengers"
                         @blur="validateField('passengers')">
                  <div class="error-msg" v-if="fieldErrors.passengers">{{ fieldErrors.passengers }}</div>
                </div>
                <div class="form-group">
                  <label>Luggage Pieces<span class="required">*</span></label>
                  <input type="number" v-model="formData.luggage" placeholder="Enter number of luggage pieces"
                         @blur="validateField('luggage')">
                  <div class="error-msg" v-if="fieldErrors.luggage">{{ fieldErrors.luggage }}</div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group checkbox-group"
                     style="justify-content: flex-start; margin-top: 1.5rem; align-items: center;">
                  <p style="text-transform: none; cursor: pointer; font-size: 1rem; text-align: justify;"
                     @click="formData.optedIn = !formData.optedIn">
                    <input type="checkbox" id="opted-in" v-model="formData.optedIn">
                    I agree to receive SMS messages from Chiefton Corporation <a href="ride2theairports.com">ride2theairports.com</a> regarding booking confirmations, ride updates, pickup and drop-off notifications, and customer service communications.
                    Message frequency varies. Message and data rates may apply.
                    Reply STOP to opt out and HELP for assistance. By checking this box, you agree to our <a href="#">Privacy Policy </a> and <a href="#"> Terms of Service.</a></p>
                </div>
              </div>

              <div class="button-group">
                <button class="btn btn-secondary" @click="prevStep"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn btn-primary" @click="nextStep(2)">Next: Location & Time <i
                    class="fas fa-arrow-right"></i></button>
              </div>
            </div>

            <!-- STEP 3: Pickup/Drop off + RoundTrip + Date/Time -->
            <div class="step-pane" :class="{'active-pane': currentStep === 3}">
              <h3 class="step-heading">Journey Details</h3>
              <p class="step-description">Set pickup, drop off & travel preferences</p>
              <div class="step-line">
                <hr>
              </div>
              <!-- Pickup Location -->
              <div class="form-row">
                <div class="form-group">
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
                      @placeSelected="handlePuSelected"
                  />
                </div>
                <!-- Drop off Location -->
                <div class="form-group">
                  <LocationInput
                      v-model="formData.dropOffLocation"
                      label="Drop off Location"
                      placeholder="Enter drop off address"
                      :required="true"
                      :error-message="fieldErrors.dropOffLocation"
                      input-ref="dropoffInput"
                      field-key="dropoff"
                      @validate="validateField"
                      @blur="validateField"
                      @placeSelected="handleDropOffSelected"
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group datepicker-input">
                  <label>Pickup Date & Time <span class="required">*</span></label>
                  <input type="datetime-local" v-model="formData.pickupDateTime"
                         @blur="validateField('pickupDateTime')" :min="minDateTime">
                  <div class="error-msg" v-if="fieldErrors.pickupDateTime">{{ fieldErrors.pickupDateTime }}</div>
                </div>
                <div class="form-group checkbox-group round-trip-checkbox">
                  <input type="checkbox" id="roundtrip" v-model="formData.isRoundTrip">
                  <label for="roundtrip" style="text-transform: none; font-weight: 500;">Round trip (return
                    journey)</label>
                </div>
                <div class="form-group datepicker-input" v-if="formData.isRoundTrip">
                  <label>Return Pickup Date & Time <span class="required">*</span></label>
                  <input type="datetime-local" v-model="formData.returnPickupDateTime"
                         @blur="validateField('returnPickupDateTime')">
                  <div class="error-msg" v-if="fieldErrors.returnPickupDateTime">{{
                      fieldErrors.returnPickupDateTime
                    }}
                  </div>
                </div>
              </div>

              <div class="summary-text" v-if="formData.selectedCar">
                <strong>Selected:</strong> {{ getCarName() }} &nbsp;|&nbsp;
                Name : {{ formData.fullName || 'Guest' }} |&nbsp;
                Phone : {{ formData.primaryPhone || 'No Phone number' }} |
                Email: {{ formData.email || 'No Email' }} |
                Passengers : {{ formData.passengers || '-' }} |
                Luggage Pieces : {{ formData.luggage || '-' }} |
                Messaging : {{ (formData.optedIn ? 'Opted-in' : 'Not opted-in') || '' }}
              </div>
              <div class="button-group">
                <button class="btn btn-secondary" @click="prevStep"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn btn-primary btn-success btn-submit" @click="submitBooking"
                        :disabled="isSubmitting">
                  <span v-if="isSubmitting" style="display: flex; align-items: center;">
                    <span class="spinner"></span>Submitting...
                  </span>
                  <span v-else>Submit Booking</span>
                </button>
              </div>
            </div>
          </div>
        </template>
        <div class="submission-result" v-if="submitSuccessMessage || submitErrorMessage">
          <div class="submission-success" v-if="submitSuccessMessage">
            <div class="success-message">
              <p>{{ submitSuccessMessage }}</p>
            </div>
            <button class="btn btn-primary" @click="resetWidget">Book another trip</button>
          </div>
          <div class="submission-error" v-else>
            <div class="error-message">
              <h4 style="margin-top: 0; margin-bottom: 5px;"><b>An error has been occurred!</b></h4>
              <p style="white-space: pre; margin-top: 0;">{{ submitErrorMessage }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import LocationInput from './LocationInput.vue';
import googleMapsLoader from '../utils/googleMapsLoader';

import '../assets/booking.css';
import '../assets/booking-responsive.css';


export default {
  name: 'BookingWidget',
  components: {
    LocationInput,
  },
  data() {
    return {
      currentStep: 1,
      mapsApiLoaded: false,
      settings: null,
      isSubmitting: false,
      formData: {
        selectedCar: null,
        fullName: '',
        primaryPhone: '',
        optedIn: false,
        secondaryPhone: '',
        email: '',
        passengers: '',
        luggage: '',
        pickupLocation: '',
        pickupLocationLat: '',
        pickupLocationLng: '',
        dropOffLocation: '',
        dropOffLocationLat: '',
        dropOffLocationLng: '',
        pickupDateTime: '',
        isRoundTrip: false,
        returnPickupDateTime: '',
        vehicleTypeId: null,
      },
      carOptions: [],
      carLoadingError: false,
      stepErrors: {
        car: false
      },
      submitSuccessMessage: null,
      submitErrorMessage: null,
      fieldErrors: {
        fullName: '',
        primaryPhone: '',
        secondaryPhone: '',
        email: '',
        passengers: '',
        luggage: '',
        pickupLocation: '',
        dropOffLocation: '',
        pickupDateTime: '',
        returnPickupDateTime: ''
      },
      phoneNumber: '',
      phoneNumberSecondary: '',
      dropdownOptions: {
        showFlags: true,
        showSearchBox: true
      },
      inputOptions: {
        showDialCode: true
      }
    }
  },
  async mounted() {
    await this.loadSettings()
    await this.loadGoogleMapsAPI()
    await this.loadCars()
  },
  computed: {
    minDateTime() {
      const now = new Date()

      now.setMinutes(now.getMinutes() - now.getTimezoneOffset())

      let x = now.toISOString().slice(0, 16)
      console.log('Min datetime:', x)
      return x;
    }
  },
  watch: {
    'formData.returnPickupDateTime'(newVal) {
      console.log('Parent received update:', newVal)
    }
  },
  methods: {
    handlePhoneInput(phone, phoneObject) {
      // console.log('Phone number input:', phone, phoneObject);
      if (phoneObject.valid) {
        this.fieldErrors.primaryPhone = '';
        this.formData.primaryPhone = phoneObject.number
      } else if (phoneObject.number?.length > 0) {
        this.formData.primaryPhone = ''
        this.fieldErrors.primaryPhone = 'Enter a valid phone number'
      } else {
        this.formData.primaryPhone = ''
        this.fieldErrors.primaryPhone = ''
      }
    },
    handlePhoneInputSecondary(phone, phoneObject) {
      if (phoneObject.valid) {
        this.fieldErrors.secondaryPhone = '';
        this.formData.secondaryPhone = phoneObject.number
      } else if (phoneObject.number?.length > 0) {
        this.formData.secondaryPhone = ''
        this.fieldErrors.secondaryPhone = 'Enter a valid phone number'
      } else {
        this.formData.secondaryPhone = ''
        this.fieldErrors.secondaryPhone = ''
      }
    },
    // Original loadSettings method preserved
    async loadSettings() {
      try {
        let timestamp = new Date().getTime();
        const response = await this.$api.get('/settings?t=' + timestamp);

        if (response.data.success) {
          this.settings = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load settings:', error);
      }
    },

    async loadGoogleMapsAPI() {
      try {
        const apiKey = this.settings?.googleMapsApiKey;
        await googleMapsLoader.load(apiKey)
        this.mapsApiLoaded = true
        console.log('Google Maps API loaded successfully')
      } catch (error) {
        console.error('Failed to load Google Maps API:', error)
      }
    },

    async loadCars() {
      try {
        this.carLoadingError = false;
        let timestamp = new Date().getTime();
        const response = await this.$api.get('/get-cars?t=' + timestamp + '');
        console.log('Cars fetched:', response.data);
        if (response.data.success) {
          this.carOptions = [];
          try {
            response.data.data?.forEach(car => {
              this.carOptions.push({
                id: car.id,
                name: car.name,
                desc: `Luggage: ${car.max_luggage} | Seats: ${car.max_passengers}`,
                icon: 'fas fa-car',
                image_url: car.image_url,
              })
            })
          } catch (e) {
            this.carLoadingError = true
            console.error('Error parsing cars:', e);
          }
        } else {
          console.error('Failed to load cars:', response.data.message);
        }
      } catch (error) {
        console.error('Failed to load settings:', error);
      }
    },

    handlePuSelected(place) {
      this.formData.pickupLocation = place.address;
      this.formData.pickupLocationLat = place.latitude;
      this.formData.pickupLocationLng = place.longitude;
      console.log('Pickup location selected:', place);
    },

    handleDropOffSelected(place) {
      this.formData.dropOffLocation = place.address;
      this.formData.dropOffLocationLat = place.latitude;
      this.formData.dropOffLocationLng = place.longitude;
      console.log('Drop off location selected:', place);
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
      if (field === 'fullName') {
        if (!this.formData.fullName.trim())
          error = 'Full name of the passenger is required';
        else if (this.formData.fullName.trim().length < 2)
          error = 'At least 2 characters';
      } else if (field === 'primaryPhone') {
        const phone = this.formData.primaryPhone.trim();
        if (!phone)
          error = 'Primary phone is required';
        else if (!/^[+\d\s\-()]{7,20}$/.test(phone))
          error = 'Enter a valid phone number';
      } else if (field === 'email') {
        const email = this.formData.email.trim();
        if (!email)
          error = 'Email is required';
        else if (!/^\S+@\S+\.\S+$/.test(email))
          error = 'Enter a valid email address';
      } else if (field === 'passengers') {
        if (!this.formData.passengers.trim())
          error = 'Total passengers is required';
      } else if (field === 'luggage') {
        if (!this.formData.luggage.trim())
          error = 'Total luggage Pcs is required';
      } else if (field === 'pickupLocation') {
        if (!this.formData.pickupLocation.trim())
          error = 'Pickup location is required';
      } else if (field === 'dropOffLocation') {
        if (!this.formData.dropOffLocation.trim())
          error = 'Drop off location is required';
      } else if (field === 'pickupDateTime') {
        if (!this.formData.pickupDateTime)
          error = 'Pickup date & time is required';
        else {
          const selectedDate = new Date(this.formData.pickupDateTime)
          const currentDate = new Date()
          currentDate.setSeconds(0)
          currentDate.setMilliseconds(0)

          if (selectedDate.getTime() <= currentDate.getTime()) {
            error = 'Pickup time must be in the future'
          }
        }
      } else if (field === 'returnPickupDateTime') {
        if (this.formData.isRoundTrip && !this.formData.returnPickupDateTime.trim()) {
          error = 'Return pickup date & time is required for round trip';
        } else {
          if (this.formData.isRoundTrip) {
            const returnDate = new Date(this.formData.returnPickupDateTime);
            if (returnDate < new Date()) {
              error = 'Return pickup time must be in the future';
            } else if (returnDate <= new Date(this.formData.pickupDateTime)) {
              error = 'Return pickup time must be after pickup time';
            }
          }
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
      const fields = ['fullName', 'primaryPhone', 'email', 'passengers', 'luggage'];
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
      if (!this.validateField('dropOffLocation')) isValid = false;
      if (!this.validateField('pickupDateTime')) isValid = false;
      if (this.formData.isRoundTrip) {
        if (!this.validateField('returnPickupDateTime')) isValid = false;
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
        carId: this.formData.selectedCar,
        carName: this.getCarName(),
        fullName: this.formData.fullName,
        primaryPhone: this.formData.primaryPhone,
        secondaryPhone: this.formData.secondaryPhone,
        email: this.formData.email,
        passengers: this.formData.passengers,
        luggage: this.formData.luggage,
        pickupLocation: this.formData.pickupLocation,
        pickupLocationLat: this.formData.pickupLocationLat,
        pickupLocationLng: this.formData.pickupLocationLng,
        dropOffLocation: this.formData.dropOffLocation,
        dropOffLocationLat: this.formData.dropOffLocationLat,
        dropOffLocationLng: this.formData.dropOffLocationLng,
        pickupDateTime: this.formData.pickupDateTime,
        isRoundTrip: this.formData.isRoundTrip,
        optedIn: this.formData.optedIn,
        returnPickupDateTime: this.formData.isRoundTrip ? this.formData.returnPickupDateTime : null
      };
      console.log('Booking details:', bookingPayload);
      this.sendBookingRequest(bookingPayload);
    },

    async sendBookingRequest(data) {
      this.submitSuccessMessage = null;
      this.submitErrorMessage = null;
      this.isSubmitting = true;
      const response = await this.$api.post('/save-booking', data);

      this.isSubmitting = false;
      //Booking might fail due to some error in the backend like distance calculation
      if (response.data.success && response.data?.external_api_response?.success) {
        this.resetForm();
        this.submitSuccessMessage = response.data?.external_api_response?.message;
        console.log('Booking request response:', response);
      } else {
        if (response.data?.external_api_response?.success === false) {
          this.submitErrorMessage = response.data?.external_api_response?.message;
        } else {
          this.submitErrorMessage = response.data?.message;
        }
        console.error('Booking request failed:', response.data.message);
      }
    },

    resetForm() {
      this.formData = {
        selectedCar: null,
        fullName: '',
        primaryPhone: '',
        optedIn: false,
        secondaryPhone: '',
        email: '',
        passengers: '',
        luggage: '',
        pickupLocation: '',
        pickupLocationLat: '',
        pickupLocationLng: '',
        dropOffLocation: '',
        dropOffLocationLat: '',
        dropOffLocationLng: '',
        pickupDateTime: '',
        isRoundTrip: false,
        returnPickupDateTime: '',
        vehicleTypeId: null,
      }
    },
    resetWidget() {
      this.currentStep = 1;
      this.resetForm();
      this.submitSuccessMessage = null;
      this.submitErrorMessage = null;
      this.fieldErrors = {
        fullName: '',
        primaryPhone: '',
        email: '',
        passengers: '',
        luggage: '',
        pickupLocation: '',
        dropOffLocation: '',
        pickupDateTime: '',
      }
    },
  },
}
</script>

<style scoped>
.spinner {
  width: 15px;
  height: 15px;
  border: 2px solid #e5e7eb;
  border-top: 2px solid #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 10px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.btn-submit:disabled {
  background-color: #e5e7eb;
  color: #6b7280;
  cursor: not-allowed;
}
</style>
<style>
.vue-tel-input {
  border-radius: 0 !important;
  border: none !important;
  font-family: inherit;
  font-size: 0.9rem !important;
  transition: 0.2s !important;
  padding: 0 !important;
}

.vue-tel-input .vti__input {
  border-radius: 0 20px 20px 0 !important;
  padding: 0.5rem 1rem !important;
  font-family: inherit;
  font-size: 0.9rem !important;
}

.vue-tel-input .vti__dropdown {
  border-top-left-radius: 1rem !important;
  border-bottom-left-radius: 1rem !important;
  font-family: inherit;
}

.vue-tel-input .vti__dropdown {
  background-color: #f3f3f3 !important;
}

.vue-tel-input .vti__dropdown:hover {
  background-color: #afaeae !important;
}

.vti__dropdown-list {
  min-width: 290px !important;
  overflow-x: hidden !important;
  overflow-y: auto !important;
}

.vti__dropdown-list .vti__input.vti__search_box {
  width: 92% !important;
  border-radius: 0 !important;
}
</style>