<template>
    <a-layout-header class="admin-topbar">
        <a-row>
            <a-col :span="4">
                <a-space>
                    <MenuOutlined class="trigger" @click="showHideMenu" />
                </a-space>
            </a-col>
            <a-col :span="20">
                <HeaderRightIcons>
                    <a-space>
                        <template v-if="innerWidth > 768">
                            <component
                                v-for="(appModule, index) in appModules"
                                :key="index"
                                v-bind:is="appModule + 'TopbarIcon'"
                            />
                        </template>
                        <TopbarIconVue v-if="innerWidth > 768" />
                        <template
                            v-if="
                                innerWidth > 768 &&
                                (permsArray.includes('pos_view') ||
                                    permsArray.includes('admin')) &&
                                willSubscriptionModuleVisible('pos')
                            "
                        >
                            <a-button
                                @click="
                                    () => {
                                        $router.push({ name: 'admin.pos.index' });
                                    }
                                "
                                type="link"
                            >
                                <ShoppingCartOutlined />
                                <span>{{ $t("menu.pos") }}</span>
                            </a-button>

                            <a-divider type="vertical" />
                        </template>
                        <template v-if="selectedWarehouse && selectedWarehouse.name">
                            <template v-if="appSetting.shortcut_menus != 'bottom'">
                                <AffixButton position="top" />
                                <a-divider type="vertical" />
                            </template>
                            <ChangeWarehouse />
                            <a-divider type="vertical" />
                        </template>
                        <LanguageSwitch />
                        <a-divider type="vertical" />
                        <a-button
                            type="link"
                            @click="
                                () => {
                                    $router.push({
                                        name: 'admin.settings.profile.index',
                                    });
                                }
                            "
                            class="p-0"
                        >
                            <a-avatar size="small" :src="user.profile_image_url" />
                        </a-button>
                    </a-space>
                </HeaderRightIcons>
            </a-col>
        </a-row>
    </a-layout-header>
</template>

<script>
import { ref } from "vue";
import { useStore } from "vuex";
import { MenuOutlined, ShoppingCartOutlined } from "@ant-design/icons-vue";
import { HeaderRightIcons } from "./style";
import common from "../../common/composable/common";
import MenuMode from "./MenuMode.vue";
import ChangeWarehouse from "./ChangeWarehouse.vue";
import AffixButton from "./AffixButton.vue";
import TopbarIconVue from "../../main/views/hrm/topbarIcon.vue";
import LanguageSwitch from "../components/LanguageSwitch.vue";

export default {
    components: {
        MenuOutlined,
        HeaderRightIcons,
        MenuMode,
        ChangeWarehouse,
        AffixButton,
        ShoppingCartOutlined,
        TopbarIconVue,
        LanguageSwitch,
    },
    setup(props, { emit }) {
        const {
            user,
            appSetting,
            permsArray,
            menuCollapsed,
            willSubscriptionModuleVisible,
            selectedWarehouse,
            appModules,
        } = common();
        const store = useStore();
        const themeMode = ref(window.config.theme_mode == "light" ? false : true);
        const themeModeLoading = ref(false);

        const showHideMenu = () => {
            store.commit("auth/updateMenuCollapsed", !menuCollapsed.value);
        };

        const logout = () => {
            store.dispatch("auth/logout");
        };

        const themeModeChanged = (checked) => {
            const mode = checked ? "dark" : "light";
            themeModeLoading.value = true;

            axiosAdmin
                .post("change-theme-mode", {
                    theme_mode: mode,
                })
                .then((response) => {
                    if (response.data.status == "success") {
                        window.location.reload();
                    }
                    themeModeLoading.value = false;
                });
        };

        return {
            selectedWarehouse,
            permsArray,
            appSetting,
            willSubscriptionModuleVisible,
            logout,
            showHideMenu,
            appModules,

            user,

            themeMode,
            themeModeChanged,
            themeModeLoading,

            innerWidth: window.innerWidth,
        };
    },
};
</script>

<style lang="less">
.trigger {
    font-size: 18px;
    line-height: 64px;
    padding-top: 4px;
    cursor: pointer;
    transition: color 0.3s;
}

.admin-topbar.ant-layout-header {
    padding: 0 20px;
    height: 64px;
    line-height: 64px;
    background: #fff !important;
    box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06);
}

.trigger:hover {
    color: #0f172a;
}
</style>
