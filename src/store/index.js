import Vue from 'vue';
import Vuex from 'vuex';
import api from '../api/client';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        user: null,
        settings: {},
        isLoading: false
    },
    mutations: {
        SET_USER(state, user) {
            state.user = user;
        },
        SET_SETTINGS(state, settings) {
            state.settings = settings;
        },
        SET_LOADING(state, loading) {
            state.isLoading = loading;
        }
    },
    actions: {
        async fetchSettings({ commit }) {
            commit('SET_LOADING', true);
            try {
                const response = await api.get('/settings');
                commit('SET_SETTINGS', response.data.data);
            } finally {
                commit('SET_LOADING', false);
            }
        }
    },
    getters: {
        isLoggedIn: state => !!state.user,
        getSettings: state => state.settings
    }
});
