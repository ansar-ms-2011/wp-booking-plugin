<template>
    <div class="mevp-container">
        <span>Booking widget content will goe here</span>
    </div>
</template>

<script>
export default {
    name: 'App',
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
                const response = await this.$api.get('/get_data');
                if (response.data.success) {
                    console.log('API connected successfully');
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
