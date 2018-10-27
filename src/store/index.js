import Vue from "vue";
import Vuex from "vuex";

Vue.use(Vuex);

export const store = new Vuex.Store({
  state: {
    totalPages: 0,
    tenders_arr: [],
    json: "",
    count: 0
  },
  getters: {
    totalPages(state) {
      return state.totalPages;
    },
    count(state) {
      return state.count;
    },
    getTenders(state) {
      return state.tenders_arr;
    }
  },
  mutations: {
    addPagiM(state, array) {
      this.state.count = array.count;
      this.state.totalPages = array.totalPages;
    }
  },
  actions: {
    addPagiA(store, array) {
      store.commit("addPagiM", array);
    }
  },
  strict: process.env.NODE_ENV !== "production"
});
