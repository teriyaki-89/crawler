
<template>
    <div>
        <div style="margin:10px auto;">
                <div style="position:relative; width:400px; margin:0 auto">
                    <span class="label label-warning" style="float:left; font-size:18px;">Records per page</span>
                    <select v-model="pPage" v-on:change="reRenderPagi()" style="margin-left:10px; float:left; width:100px;" class="form-control" >
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>                        
                    </select>
                    <span>Total: {{countAll}}</span>
                    <div style="clear:both"></div>
                </div>
        </div>
        <div v-if="tPages>0">            
            <button v-on:click="getPage(pPage,page-1)" :disabled="page==1" class="btn btn-primary" >Previous</button>            
            <button id="first" v-on:click="getPage(pPage,1)" class="btn btn-light" >1</button>
            <span v-if="(page !== 1) && (page !== pageAmount) ">
                <span v-if="page-2 > 1">...</span>
                <button v-if="page-1 !==1" v-on:click="getPage(pPage,page-1)" class="btn btn-primary">{{page-1}}</button>
                <button  v-on:click="getPage(pPage,page)" class="btn btn-light">{{page}}</button>
                <button v-if="page+1 !== pageAmount" v-on:click="getPage(pPage,page+1)" class="btn btn-primary">{{page+1}}</button>
                <span v-if="page+2 < pageAmount">...</span>
            </span>
            <button v-if="tPages !==1" id="last" v-on:click="getPage(pPage,tPages)" class="btn btn-primary">{{tPages}}</button>
            <button  v-on:click="getPage(pPage,page+1)" :disabled="page==tPages" class="btn btn-primary">Next</button>
        </div>
        <div>
          <div style="float:right; margin:5px;">
            <div class="form-group">
              <div style="float:left; height:34px; display: table;">
                <label style="display: table-cell; vertical-align: middle;"  class="input-group-text" id="">LotLong</label>
              </div>              
              <div style="float:left; margin-left:10px; width:50%">
                  <input type="text" v-model="pattern" class="form-control" @change="searchLotLong()">
              </div>                            
            </div>     
          </div>
          <div style="float:left">
            <button class="btn btn-info" @click="updateTenders">Update Tenders</button>
          </div>
          <div style="clear:both"></div>
        </div>
        <transition >
              <div :key="1" v-if="tenders_arr.length == 0" class="loader"></div>
              <div :key="2" v-else>
                    <table class= "table table-striped" >
                      <thead>
                          <tr>
                              <th>id</th>
                              <th>LotLong</th>
                              <th>lotName</th>                
                              <th>name</th>
                              <th>price_per_unit</th>
                              <th>amount</th>
                              <th>summ</th>
                              <th>sched_way</th>
                              <th>status</th>                                
                              <th>way_of_buy</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr v-for="(record, index) in tenders_arr" :key="index">
                              <td> {{record.id}} </td>
                              <td> {{record.lotLong}} </td>
                              <td :title="record.lotName"> {{record.lotName.substring(0,25) + '...' }} </td>
                              <td :title="record.name"> {{record.name .substring(0,15) + '...' }} </td>
                              <td> {{record.price_per_unit}} </td>
                              <td> {{record.amount}} </td>
                              <td> {{record.summ}} </td>
                              <td> {{record.sched_way}} </td>                
                              <td> {{record.status}} </td>
                              <td :title="record.way_of_buy"> {{record.way_of_buy.substring(0,25) + '...'}} </td>                
                          </tr> 
                      </tbody>
                  </table>
              </div>
        </transition>        
    </div>
</template>
<script>
import Vue from "vue";
import { mapActions, mapGetters, mapMutations } from "vuex";
export default {
  data() {
    return {
      msg: "test msg",
      tenders_arr: [],
      pPage: 50,
      page: 1,
      pattern: ""
    };
  },
  computed: {
    ...mapGetters({
      tPages: "totalPages",
      getTenders: "getTenders",
      countAll: "count"
    }),
    pageAmount() {
      return this.tPages;
    }
  },
  methods: {
    ...mapActions({}),
    updateTenders() {
      Vue.http.get("tenders/download").catch(e => {
        console.log("error to update tenders " + e);
      });
    },
    httpGetPage() {
      this.tenders_arr = [];
      Vue.http
        .get(
          "tenders/showData/pPage=" +
            this.pPage +
            "/page=" +
            this.page +
            "/search=" +
            this.pattern
        )
        .then(response => response.json())
        .then(data => {
          //console.log(data);
          this.tenders_arr = data;
        })
        .catch(e => {
          console.log("error to download page " + e);
        });
    },

    getPage(pPage, page) {
      console.log("getPage");
      if (page == 1) {
        if (document.getElementById("first") !== null) {
          document
            .getElementById("first")
            .setAttribute("class", "btn btn-light");
          if (document.getElementById("last") !== null) {
            document
              .getElementById("last")
              .setAttribute("class", "btn btn-primary");
          }
        }
      } else if (page == this.pageAmount) {
        if (document.getElementById("last") !== null) {
          document
            .getElementById("last")
            .setAttribute("class", "btn btn-light");
          document
            .getElementById("first")
            .setAttribute("class", "btn btn-primary");
        }
      } else {
        document
          .getElementById("first")
          .setAttribute("class", "btn btn-primary");
        document
          .getElementById("last")
          .setAttribute("class", "btn btn-primary");
      }

      this.pPage = pPage;
      this.page = page;
      this.httpGetPage();
    },
    reRenderPagi() {
      console.log("reRender");
      //this.pattern = "";
      Vue.http
        .get("tenders/getPagi/pPage=" + this.pPage + "/search=" + this.pattern)
        .then(response => response.json())
        .then(data => {
          this.$store
            .dispatch("addPagiA", data)
            .catch(e => console.log("getPagi error " + e));
        });
      this.getPage(this.pPage, 1);
    },
    searchLotLong() {
      console.log("search started");
      /*setTimeout(() => {
        this.httpGetPage(), 2000;
      });
      setTimeout(() => {
        this.reRenderPagi(), 3000;
      });*/
      this.httpGetPage();
      this.reRenderPagi();
    }
  },

  beforeMount() {
    console.log("beforeMount");
    this.reRenderPagi();
  },
  mounted() {
    //console.log("mounted");
    //this.getPage(this.pPage, this.page);
  }
};
</script>
<style>
.loader {
  margin: 0 auto;
  border: 16px solid #f3f3f3; /* Light grey */
  border-top: 16px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 120px;
  height: 120px;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>