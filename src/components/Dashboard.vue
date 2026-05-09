<template>
    <div class="dashboard">
        <el-row :gutter="20">
            <el-col :span="24">
                <el-card class="mevp-card">
                    <div slot="header">
                        <span>Data Management</span>
                        <el-button 
                            type="primary" 
                            size="small" 
                            @click="showAddDialog"
                            style="float: right;">
                            Add New
                        </el-button>
                    </div>
                    
                    <el-table :data="items" stripe>
                        <el-table-column prop="id" label="ID" width="80"></el-table-column>
                        <el-table-column prop="title" label="Title"></el-table-column>
                        <el-table-column prop="content" label="Content"></el-table-column>
                        <el-table-column prop="status" label="Status">
                            <template slot-scope="scope">
                                <el-tag :type="scope.row.status === 'active' ? 'success' : 'info'">
                                    {{ scope.row.status }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="Actions" width="150">
                            <template slot-scope="scope">
                                <el-button 
                                    type="text" 
                                    @click="editItem(scope.row)">Edit</el-button>
                                <el-button 
                                    type="text" 
                                    style="color: #f56c6c;"
                                    @click="deleteItem(scope.row.id)">Delete</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    
                    <el-pagination
                        @current-change="handleCurrentChange"
                        :current-page.sync="currentPage"
                        :page-size="10"
                        layout="total, prev, pager, next"
                        :total="total">
                    </el-pagination>
                </el-card>
            </el-col>
        </el-row>
        
        <!-- Add/Edit Dialog -->
        <el-dialog 
            :title="dialogTitle" 
            :visible.sync="dialogVisible"
            width="500px">
            <el-form :model="form" :rules="rules" ref="form">
                <el-form-item label="Title" prop="title">
                    <el-input v-model="form.title" placeholder="Enter title"></el-input>
                </el-form-item>
                <el-form-item label="Content" prop="content">
                    <el-input 
                        type="textarea" 
                        v-model="form.content" 
                        placeholder="Enter content"
                        :rows="4">
                    </el-input>
                </el-form-item>
                <el-form-item label="Status" prop="status">
                    <el-select v-model="form.status" placeholder="Select status">
                        <el-option label="Active" value="active"></el-option>
                        <el-option label="Inactive" value="inactive"></el-option>
                    </el-select>
                </el-form-item>
            </el-form>
            <span slot="footer">
                <el-button @click="dialogVisible = false">Cancel</el-button>
                <el-button type="primary" @click="saveItem" :loading="saving">Save</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
export default {
    name: 'Dashboard',
    data() {
        return {
            items: [],
            currentPage: 1,
            total: 0,
            dialogVisible: false,
            dialogTitle: 'Add New Item',
            saving: false,
            form: {
                id: null,
                title: '',
                content: '',
                status: 'active'
            },
            rules: {
                title: [
                    { required: true, message: 'Please enter title', trigger: 'blur' }
                ],
                content: [
                    { required: true, message: 'Please enter content', trigger: 'blur' }
                ]
            }
        }
    },
    created() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            try {
                const response = await this.$api.get('/data');
                if (response.data.success) {
                    this.items = response.data.data;
                    this.total = this.items.length;
                }
            } catch (error) {
                this.$message.error('Failed to fetch data');
            }
        },
        showAddDialog() {
            this.dialogTitle = 'Add New Item';
            this.form = {
                id: null,
                title: '',
                content: '',
                status: 'active'
            };
            this.dialogVisible = true;
        },
        editItem(item) {
            this.dialogTitle = 'Edit Item';
            this.form = { ...item };
            this.dialogVisible = true;
        },
        async saveItem() {
            this.$refs.form.validate(async (valid) => {
                if (valid) {
                    this.saving = true;
                    try {
                        const url = this.form.id ? `/data/${this.form.id}` : '/data';
                        const response = await this.$api.post(url, this.form);
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            this.dialogVisible = false;
                            this.fetchData();
                        }
                    } catch (error) {
                        this.$message.error('Failed to save data');
                    } finally {
                        this.saving = false;
                    }
                }
            });
        },
        async deleteItem(id) {
            this.$confirm('Are you sure you want to delete this item?', 'Confirm', {
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                type: 'warning'
            }).then(async () => {
                try {
                    const response = await this.$api.delete(`/data/${id}`);
                    if (response.data.success) {
                        this.$message.success('Item deleted successfully');
                        this.fetchData();
                    }
                } catch (error) {
                    this.$message.error('Failed to delete item');
                }
            }).catch(() => {});
        },
        handleCurrentChange(val) {
            this.currentPage = val;
            this.fetchData();
        }
    }
}
</script>

<style scoped>
.dashboard {
    padding: 20px;
}
</style>
