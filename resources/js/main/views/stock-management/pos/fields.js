import { ref } from "vue";
import { useI18n } from "vue-i18n";

const fields = () => {
    const { t } = useI18n();
    const taxes = ref([]);
    const warehouses = ref([]);
    const customers = ref([]);
    const brands = ref([]);
    const categories = ref([]);
    const productLists = ref([]);
    const posDefaultCustomer = ref({});
    const productsHasMore = ref(false);
    const productsLoading = ref(false);
    const productPageSize = 48;

    const formData = ref({
        user_id: undefined,
        tax_id: undefined,
        category_id: undefined,
        brand_id: undefined,
        tax_id: undefined,
        tax_rate: 0,
        tax_amount: 0,
        discount_type: "percentage",
        discount_value: 0,
        discount: 0,
        shipping: 0,
        subtotal: 0,
    });

    const orderItemColumns = [
        {
            title: "#",
            dataIndex: "sn",
        },
        {
            title: t("product.name"),
            dataIndex: "name",
        },
        {
            title: t("product.quantity"),
            dataIndex: "unit_quantity",
        },
        {
            title: t("product.subtotal"),
            dataIndex: "subtotal",
        },
        {
            title: t("common.action"),
            dataIndex: "action",
        },
    ];

    const customerUrl = "customers?limit=500";

    const fetchPosProducts = (append = false) => {
        productsLoading.value = true;
        const offset = append ? productLists.value.length : 0;

        return axiosAdmin
            .post("pos/products", {
                brand_id: formData.value.brand_id,
                category_id: formData.value.category_id,
                limit: productPageSize,
                offset: offset,
            })
            .then((productResponse) => {
                const products = productResponse.data.products || [];
                productLists.value = append
                    ? [...productLists.value, ...products]
                    : products;
                productsHasMore.value = !!productResponse.data.has_more;
                productsLoading.value = false;
            })
            .catch(() => {
                productsLoading.value = false;
            });
    };

    const getPreFetchData = () => {
        fetchPosProducts(false);

        axiosAdmin.get("taxes?limit=1000").then((taxesResponse) => {
            taxes.value = taxesResponse.data;
        });

        axiosAdmin.get(customerUrl).then((customersResponse) => {
            customers.value = customersResponse.data;
        });

        axiosAdmin.get("categories?limit=1000").then((caegoriesResponse) => {
            categories.value = caegoriesResponse.data;
        });

        axiosAdmin.get("brands?limit=1000").then((brandsResponse) => {
            brands.value = brandsResponse.data;
        });

        axiosAdmin.get("default-walkin-customer").then((response) => {
            const walkIn = response.data.customer;
            if (!walkIn) {
                return;
            }

            posDefaultCustomer.value = walkIn;
            if (!formData.value.user_id) {
                formData.value = {
                    ...formData.value,
                    user_id: walkIn.xid,
                };
            }
        });
    };

    return {
        taxes,
        customers,
        brands,
        categories,
        productLists,
        formData,

        customerUrl,

        orderItemColumns,
        getPreFetchData,
        fetchPosProducts,
        productsHasMore,
        productsLoading,
        posDefaultCustomer,
    };
};

export default fields;
