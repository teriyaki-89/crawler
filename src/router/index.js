import Vue from "vue";
import Router from "vue-router";
import Tenders from "@/components/Tenders";

Vue.use(Router);

export default new Router({
  routes: [
    {
      path: "*",
      name: "Tenders",
      component: Tenders
    }
  ],
  mode: "history"
});
