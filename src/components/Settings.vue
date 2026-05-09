<template>
    <div class="settings">
        <el-card>
            <div slot="header">
                <span>Plugin Settings</span>
            </div>
            <el-form :model="settings" label-width="150px">
                <el-form-item label="API Endpoint">
                    <el-input v-model="settings.api_endpoint" placeholder="https://api.example.com"></el-input>
                </el-form-item>
                <el-form-item label="Cache Duration (seconds)">
                    <el-input-number v-model="settings.cache_duration" :min="60" :max="86400"></el-input-number>
                </el-form-item>
                <el-form-item label="Max Items">
                    <el-input-number v-model="settings.max_items" :min="10" :max="1000"></el-input-number>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="saveSettings">Save Settings</el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script>
export default {
    name: 'Settings',
    data() {
        return {
            settings: {
                api_endpoint: '',
                cache_duration: 3600,
                max_items: 100
            }
        }
    },
    created() {
        this.loadSettings();
    },
    methods: {
        async loadSettings() {
            try {
                const response = await this.$api.get('/settings');
                if (response.data.success) {
                    this.settings = response.data.data;
                }
            } catch (error) {
                this.$message.error('Failed to load settings');
            }
        },
        async saveSettings() {
            try {
                const response = await this.$api.post('/settings', this.settings);
                if (response.data.success) {
                    this.$message.success('Settings saved successfully');
                }
            } catch (error) {
                this.$message.error('Failed to save settings');
            }
        }
    }
}
</script>

<style scoped>
.settings {
    padding: 20px;
}
</style>
