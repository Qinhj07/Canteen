<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <!-- import CSS -->
    <title>XX餐厅系统</title>
    <link rel="stylesheet" href="static/element-ui/index.css">
</head>
<style>
    .el-row {
        margin-bottom: 20px;

    &
    :last-child {
        margin-bottom: 0;
    }

    }
    .el-col {
        border-radius: 4px;
    }

    .bg-purple-dark {
        background: #99a9bf;
    }

    .bg-purple {
        background: #d3dce6;
    }

    .bg-purple-light {
        background: #e5e9f2;
    }

    .grid-content {
        border-radius: 4px;
        min-height: 36px;
    }

    .row-bg {
        padding: 10px 0;
        background-color: #f9fafc;
    }
</style>
<body>
<div id="app">
    <el-container>
        <el-header>
            <el-row :gutter="20">
                <el-col :span="16" :offset="4">
                    <div class="grid-content bg-purple">
                        <el-row>
                            <el-col :span="8">
                                <div class="grid-content bg-purple">总共：@{{total}}</div>
                            </el-col>
                            <el-col :span="8">
                                <div class="grid-content bg-purple-light">已使用：@{{used}}</div>
                            </el-col>
                            <el-col :span="8">
                                <div class="grid-content bg-purple"> 未使用@{{unUse}}</div>
                            </el-col>
                        </el-row>
                    </div>
                </el-col>
            </el-row>
        </el-header>
        <el-main>
            <el-container>
                <el-header>
                    <el-row :gutter="20">
                        <el-col :span="4">
                            <div class="grid-content"></div>
                        </el-col>
                        <el-col :span="20">
                            <div class="grid-content">
                                <el-row :gutter="20">
                                    <el-col :span="18">
                                        <div class="grid-content">
                                            <el-input
                                                placeholder="请输入号码"
                                                v-model="input"
                                                clearable
                                                @keyup.enter.native="useOrder">
                                            </el-input>
                                        </div>
                                    </el-col>
                                    <el-col :span="2">
                                        <div class="grid-content">
                                            <el-button type="danger" @click="useOrder">查找</el-button>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="grid-content">
                            </div>
                        </el-col>
                    </el-row>
                </el-header>
                <el-main>
                    <el-row :gutter="20">
                        <el-col :span="2">
                            <div class="grid-content"></div>
                        </el-col>
                        <el-col :span="20">
                            <div class="grid-content">
                                <el-table
                                    :data="tableData"
                                    height="250"
                                    border
                                    style="width: 100%">
                                    <el-table-column
                                        fixed
                                        prop="date"
                                        label="日期"
                                        width="180">
                                    </el-table-column>
                                    <el-table-column
                                        prop="phone"
                                        label="号码"
                                        width="180">
                                    </el-table-column>
                                    <el-table-column
                                        prop="mealType"
                                        label="餐品"
                                        width="180">
                                    </el-table-column>
                                    <el-table-column
                                        prop="receipt"
                                        label="餐单">
                                        <template slot-scope="scope">
                                            <span v-html="scope.row.receipt"></span>
                                        </template>
                                    </el-table-column>
                                    <el-table-column
                                        prop="usedAt"
                                        label="扫码时间">
                                    </el-table-column>
                                </el-table>
                            </div>
                        </el-col>
                        <el-col :span="2">
                            <div class="grid-content"></div>
                        </el-col>
                    </el-row>

                </el-main>
            </el-container>
        </el-main>
    </el-container>


</div>
</body>
<script src="static/vue.js"></script>
<script src="static/element-ui/index.js"></script>
<script src="static/element-ui/index.js"></script>
<script src="static/axios.min.js"></script>
<script>
    new Vue({
        el: '#app',
        data: function () {
            return {
                total: 0,
                used: 0,
                unUse: 0,
                input: '',
                tableData: []
            }
        },
        mounted: function () {
            this.getCnt();
            this.getUsed();
        },
        methods: {
            useOrder() {
                let phoneReg = /^(13[0-9]|14[01456879]|15[0-35-9]|16[2567]|17[0-8]|18[0-9]|19[0-35-9])\d{8}$/;
                let codeReg = /^[\u4e00-\u9fa5_a-zA-Z0-9_]{10}$/;
                if (phoneReg.test(this.input)) {
                    this.search(this.input)
                } else if (codeReg.test(this.input)) {
                    this.useOrderByCode(this.input)
                } else {
                    this.$message({
                        message: '请输入正确的号码',
                        type: 'warning'
                    });
                }
            },

            search(phone) {
                axios.get('api/meal/geByPhone/' + phone, {}).then(resp => {
                    receipts = "";
                    respData = resp.data.data;
                    respData.forEach((item) => {
                        item.item.forEach((items) => {
                            receipts += items.name + "- X" + items.num + "<br/>";
                        });
                    });
                    receipts += respData[0].usedAt;
                    this.$alert(receipts, respData[0].phone + "  " + respData[0].mealType + "  " + respData[0].status, {
                        dangerouslyUseHTMLString: true
                    });
                })
                this.input = '';

            },

            getCnt() {
                axios.get('api/meal/getCount', {}).then(resp => {
                    if (resp.data.data) {
                        this.total = resp.data.data.total;
                        this.used = resp.data.data.used;
                        this.unUse = resp.data.data.left;
                    }
                })
            },

            useOrderByCode(code) {
                var that = this
                axios.get('api/meal/use/' + code, {}).then(resp => {
                    if (resp.data.code === 2000) {
                        itemOut = resp.data.data;
                        let receiptList = "";
                        itemOut.item.forEach((item) => {
                            receiptList += item.name + "- X" + item.num + "<br/>"
                        });
                        that.tableData.unshift({
                            date: itemOut.useDate,
                            phone: itemOut.phone,
                            mealType: itemOut.mealType,
                            receipt: receiptList,
                            usedAt: itemOut.useAt
                        });
                        that.used = that.used + 1;
                        that.unUse = that.unUse - 1;
                        this.$message({
                            message: '扫码用餐成功！',
                            type: 'success'
                        });
                    }else {
                        this.$message({
                            message: '扫码失败！',
                            type: 'error'
                        });
                    }
                    that.input = '';
                })
            },

            getUsed() {
                var that = this
                axios.get('api/meal/getUse', {}).then(resp => {
                    resp.data.data.forEach(function (itemOut) {
                        let receiptList = "";
                        itemOut.item.forEach((item) => {
                            receiptList += item.name + "- X" + item.num + "<br/>"
                        });
                        that.tableData.unshift({
                            date: itemOut.useDate,
                            phone: itemOut.phone,
                            mealType: itemOut.mealType,
                            receipt: receiptList,
                            usedAt: itemOut.usedAt
                        });
                    });
                });
            },
        }
    })
</script>
</html>

