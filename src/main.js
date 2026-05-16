import Vue from 'vue';
import App from './App.vue';
import store from './store';
import api from './api/client';

Vue.prototype.$api = api;
Vue.prototype.withCredentials = true;
Vue.config.productionTip = false;

new Vue({
    store,
    render: h => h(App)
}).$mount('#mevp-app');
