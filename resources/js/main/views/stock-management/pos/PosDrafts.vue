<template>
    <a-drawer
        :title="`${$t('stock.drafts')} (${drafts.length})`"
        :width="drawerWidth"
        :open="visible"
        @close="$emit('closed')"
    >
        <a-table
            :dataSource="drafts"
            :columns="columns"
            :loading="loading"
            :pagination="false"
            rowKey="unique_id"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'customer'">
                    {{ record.user && record.user.name ? record.user.name : "-" }}
                </template>
                <template v-if="column.dataIndex === 'order_date'">
                    {{ formatDateTime(record.order_date) }}
                </template>
                <template v-if="column.dataIndex === 'total'">
                    {{ formatAmountCurrency(record.total) }}
                </template>
                <template v-if="column.dataIndex === 'action'">
                    <a-space>
                        <a-button type="primary" size="small" @click="recall(record)">
                            {{ $t("stock.recall_draft") }}
                        </a-button>
                        <a-button danger size="small" @click="remove(record)">
                            {{ $t("common.delete") }}
                        </a-button>
                    </a-space>
                </template>
            </template>
        </a-table>
        <a-empty v-if="!loading && drafts.length === 0" :description="$t('stock.no_drafts')" />
    </a-drawer>
</template>

<script>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Modal, message } from "ant-design-vue";
import common from "../../../../common/composable/common";

export default {
    props: ["visible"],
    emits: ["closed", "recalled", "countUpdated"],
    setup(props, { emit }) {
        const { t } = useI18n();
        const { formatAmountCurrency, formatDateTime } = common();
        const drafts = ref([]);
        const loading = ref(false);

        const columns = [
            {
                title: t("stock.invoice_number"),
                dataIndex: "invoice_number",
            },
            {
                title: t("payments.user"),
                dataIndex: "customer",
            },
            {
                title: t("common.date"),
                dataIndex: "order_date",
            },
            {
                title: t("stock.total_items"),
                dataIndex: "total_items",
            },
            {
                title: t("common.total"),
                dataIndex: "total",
            },
            {
                title: t("common.action"),
                dataIndex: "action",
            },
        ];

        const fetchDrafts = () => {
            loading.value = true;
            axiosAdmin
                .get("pos/drafts")
                .then((response) => {
                    drafts.value = response.data.drafts || [];
                    loading.value = false;
                    emit("countUpdated", drafts.value.length);
                })
                .catch(() => {
                    loading.value = false;
                });
        };

        watch(
            () => props.visible,
            (isVisible) => {
                if (isVisible) {
                    fetchDrafts();
                }
            }
        );

        const recall = (record) => {
            axiosAdmin.get(`pos/drafts/${record.unique_id}`).then((response) => {
                emit("recalled", response.data);
                emit("closed");
                message.success(t("stock.draft_recalled"));
            });
        };

        const remove = (record) => {
            Modal.confirm({
                title: t("stock.delete_draft"),
                content: t("stock.delete_draft_message"),
                okText: t("common.delete"),
                okType: "danger",
                cancelText: t("common.cancel"),
                onOk: () => {
                    return axiosAdmin
                        .delete(`pos/drafts/${record.unique_id}`)
                        .then(() => {
                            fetchDrafts();
                        });
                },
            });
        };

        return {
            drafts,
            loading,
            columns,
            formatAmountCurrency,
            formatDateTime,
            recall,
            remove,
            drawerWidth: window.innerWidth <= 991 ? "90%" : "60%",
        };
    },
};
</script>
