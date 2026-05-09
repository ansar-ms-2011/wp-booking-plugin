<template>
  <div class="mevp-container">
    <Booking></Booking>
  </div>
</template>

<script>
import Booking from './components/Booking.vue';

export default {
  name: 'App',
  components: {
    Booking
  },
  data() {
    return {
      pluginTitle: 'My Elementor Vue Plugin'
    }
  },
  created() {
    this.checkApiConnection();
  },
  methods: {
    async checkApiConnection() {
      try {
        // const response = await this.$api.get('/settings');
        const response = await this.$api.get('/data');
        if (response.data.success) {
          console.log('API connected successfully', response.data.data);
        }
      } catch (error) {
        console.error('API connection failed:', error);
      }
    }
  }
}
</script>

<style scoped>
.el-header {
  background-color: #fff;
  border-bottom: 1px solid #e5e5e5;
  padding: 0 20px;
}

.el-main {
  padding: 20px;
  background-color: #f5f7fa;
}
</style>
