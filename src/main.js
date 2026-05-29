import Vue from 'vue';
import App from './App.vue';
import store from './store';
import api from './api/client';
import VueTelInput from 'vue-tel-input';
import 'vue-tel-input/dist/vue-tel-input.css';

Vue.use(VueTelInput);
Vue.prototype.$api = api;
Vue.prototype.withCredentials = true;
Vue.config.productionTip = false;

new Vue({
    store,
    render: h => h(App)
}).$mount('#mevp-app');
