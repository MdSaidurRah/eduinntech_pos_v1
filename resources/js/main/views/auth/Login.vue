<template>
    <div class="login-main-container">
        <div class="login-lang-switch">
            <LanguageSwitch :light="isDesktop" />
        </div>
        <a-row class="main-container-div">
            <a-col :xs="24" :sm="24" :md="24" :lg="10" :xl="8">
                <div class="login-left-div">
                    <div class="login-panel">
                        <a-card
                            v-if="resetPassword"
                            :title="null"
                            class="login-div"
                            :bordered="false"
                        >
                            <a-form layout="vertical">
                                <div class="login-logo">
                                    <img
                                        class="login-img-logo"
                                        :src="logoUrl"
                                        width="160"
                                        height="52"
                                        decoding="async"
                                        fetchpriority="high"
                                        alt="Logo"
                                    />
                                </div>
                                <div class="login-heading">
                                    <h1 class="login-heading-title">
                                        {{ $t("user.sign_in") }}
                                    </h1>
                                    <p class="login-heading-sub">
                                        {{
                                            $t(
                                                "messages.please_login_to_your_account"
                                            )
                                        }}
                                    </p>
                                </div>
                                <a-alert
                                    v-if="onRequestSend.error != ''"
                                    :message="onRequestSend.error"
                                    type="error"
                                    show-icon
                                    class="mb-20"
                                />
                                <a-alert
                                    v-if="onRequestSend.success"
                                    :message="$t('messages.login_success')"
                                    type="success"
                                    show-icon
                                    class="mb-20"
                                />
                                <a-form-item
                                    :label="$t('user.email_phone')"
                                    name="email"
                                    :help="rules.email ? rules.email.message : null"
                                    :validateStatus="rules.email ? 'error' : null"
                                >
                                    <a-input
                                        size="large"
                                        v-model:value="credentials.email"
                                        @pressEnter="onSubmit"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('user.email_phone'),
                                            ])
                                        "
                                    />
                                </a-form-item>

                                <a-form-item
                                    :label="$t('user.password')"
                                    name="password"
                                    :help="
                                        rules.password ? rules.password.message : null
                                    "
                                    :validateStatus="
                                        rules.password ? 'error' : null
                                    "
                                >
                                    <a-input-password
                                        size="large"
                                        v-model:value="credentials.password"
                                        @pressEnter="onSubmit"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('user.password'),
                                            ])
                                        "
                                    />
                                </a-form-item>

                                <a-form-item class="login-submit-item">
                                    <a-button
                                        :loading="loading"
                                        @click="onSubmit"
                                        class="login-btn"
                                        block
                                        size="large"
                                        type="primary"
                                    >
                                        {{ $t("menu.login") }}
                                    </a-button>
                                </a-form-item>
                                <div class="login-link-item">
                                    <a @click="onResetPass">
                                        {{ $t("menu.reset_password") }}
                                    </a>
                                </div>
                                <div
                                    v-if="appType === 'saas' && !isSubdomainModuleEnabled"
                                    class="login-link-item"
                                >
                                    <a-button
                                        type="link"
                                        :loading="loading"
                                        @click="
                                            () =>
                                                $router.push({
                                                    name: 'superadmin.register',
                                                })
                                        "
                                    >
                                        {{ $t("front_website.register") }}
                                    </a-button>
                                </div>
                            </a-form>
                            <DemoCredentials
                                v-if="showDemoCredentials"
                                :credentials="credentials"
                            />
                        </a-card>
                        <a-card
                            v-else
                            :title="null"
                            class="login-div"
                            :bordered="false"
                        >
                            <a-alert
                                v-if="onResetRequest.success"
                                :message="$t('messages.reset_success')"
                                type="success"
                                show-icon
                                class="mb-20"
                            />
                            <a-form layout="vertical" v-else>
                                <div class="login-logo">
                                    <img
                                        class="login-img-logo"
                                        :src="logoUrl"
                                        width="160"
                                        height="52"
                                        decoding="async"
                                        fetchpriority="high"
                                        alt="Logo"
                                    />
                                </div>
                                <div class="login-heading">
                                    <h1 class="login-heading-title">
                                        {{ $t("menu.reset_password") }}
                                    </h1>
                                    <p class="login-heading-sub">
                                        {{ $t("user.email_phone") }}
                                    </p>
                                </div>
                                <a-alert
                                    v-if="onResetRequest.error != ''"
                                    :message="onResetRequest.error"
                                    type="error"
                                    show-icon
                                    class="mb-20"
                                />

                                <a-form-item
                                    :label="$t('user.email_phone')"
                                    name="email"
                                    :help="rules.email ? rules.email.message : null"
                                    :validateStatus="rules.email ? 'error' : null"
                                >
                                    <a-input
                                        size="large"
                                        v-model:value="resetCredential.email"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('user.email_phone'),
                                            ])
                                        "
                                    />
                                </a-form-item>

                                <a-form-item class="login-submit-item">
                                    <a-button
                                        :loading="loading"
                                        @click="onReset"
                                        class="login-btn"
                                        block
                                        size="large"
                                        type="primary"
                                    >
                                        {{ $t("menu.reset") }}
                                    </a-button>
                                </a-form-item>
                                <div class="login-link-item">
                                    <a @click="onResetClose">
                                        {{ $t("common.back") }}
                                    </a>
                                </div>
                            </a-form>
                        </a-card>
                    </div>
                </div>
            </a-col>
            <a-col :xs="0" :sm="0" :md="0" :lg="14" :xl="16" class="login-right-col">
                <div class="right-login-div" :style="rightBgStyle">
                    <div class="right-login-overlay">
                        <p class="right-login-kicker">POS</p>
                        <h2 class="right-login-title">
                            {{ $t("user.sign_in") }}
                        </h2>
                        <p class="right-login-copy">
                            {{ $t("messages.please_login_to_your_account") }}
                        </p>
                    </div>
                </div>
            </a-col>
        </a-row>
    </div>
</template>

<script>
import { defineComponent, reactive, ref, computed, onMounted, defineAsyncComponent } from "vue";
import { useStore } from "vuex";
import { useRouter } from "vue-router";
import apiAdmin from "../../../common/composable/apiAdmin";
import LanguageSwitch from "../../../common/components/LanguageSwitch.vue";

const DemoCredentials = defineAsyncComponent(() =>
    import("./DemoCredentials.vue")
);

export default defineComponent({
    components: {
        DemoCredentials,
        LanguageSwitch,
    },
    setup() {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const store = useStore();
        const router = useRouter();
        const appType = window.config.app_type;
        const showDemoCredentials = window.config.app_env != "envato";
        const isMobile = window.innerWidth <= 768;
        const isDesktop = window.innerWidth >= 992;
        const globalSetting = computed(() => store.state.auth.globalSetting || {});
        const isSubdomainModuleEnabled = computed(
            () => store.state.auth.isSubdomainModuleEnabled
        );
        const logoUrl = computed(
            () => globalSetting.value.light_logo_url || ""
        );
        const rightBgStyle = ref({
            backgroundColor: "#0f172a",
        });
        const resetPassword = ref(true);
        const resetCredential = reactive({
            email: "",
        });
        const credentials = reactive({
            email: null,
            password: null,
        });
        const onRequestSend = ref({
            error: "",
            success: "",
        });

        const onResetRequest = ref({
            error: "",
            success: "",
        });

        onMounted(() => {
            if (window.innerWidth < 992) {
                return;
            }

            const url =
                globalSetting.value.login_image_url ||
                window.config.login_background;
            if (!url) {
                return;
            }

            const img = new Image();
            img.decoding = "async";
            img.onload = () => {
                rightBgStyle.value = {
                    backgroundColor: "#0f172a",
                    backgroundImage: `url("${url}")`,
                    backgroundSize: "cover",
                    backgroundPosition: "center",
                };
            };
            img.src = url;
        });

        const onResetPass = () => {
            resetErrorMessages();

            resetPassword.value = false;
        };

        const onResetClose = () => {
            resetPassword.value = true;

            resetErrorMessages();
        };

        const resetErrorMessages = () => {
            onRequestSend.value = {
                error: "",
                success: "",
            };
            onResetRequest.value = {
                error: "",
                success: "",
            };
            rules.value = {};
        };

        const onReset = () => {
            addEditRequestAdmin({
                url: "auth/forgot-password",
                data: resetCredential,
                success: (response) => {
                    onResetRequest.value = {
                        error: "",
                        success: true,
                    };
                },
                error: (err) => {
                    onResetRequest.value = {
                        error: err.error.message ? err.error.message : "",
                        success: false,
                    };
                },
            });
        };

        const onSubmit = () => {
            onRequestSend.value = {
                error: "",
                success: false,
            };

            addEditRequestAdmin({
                url: "auth/login",
                data: credentials,
                success: (response) => {
                    const user = response.user;
                    store.commit("auth/updateUser", user);
                    store.commit("auth/updateToken", response.token);
                    store.commit("auth/updateExpires", response.expires_in);
                    store.commit(
                        "auth/updateVisibleSubscriptionModules",
                        response.visible_subscription_modules
                    );

                    if (appType == "non-saas") {
                        store.dispatch("auth/updateAllWarehouses");
                        store.commit("auth/updateWarehouse", response.user.warehouse);

                        router.push({
                            name: "admin.dashboard.index",
                            params: { success: true },
                        });
                    } else {
                        if (user.is_superadmin && user.user_type == "super_admins") {
                            store.commit("auth/updateApp", response.app);
                            store.commit(
                                "auth/updateEmailVerifiedSetting",
                                response.email_setting_verified
                            );
                            router.push({
                                name: "superadmin.dashboard.index",
                                params: { success: true },
                            });
                        } else {
                            store.commit("auth/updateApp", response.app);
                            store.commit(
                                "auth/updateEmailVerifiedSetting",
                                response.email_setting_verified
                            );
                            store.commit(
                                "auth/updateAddMenus",
                                response.shortcut_menus.credentials
                            );
                            store.dispatch("auth/updateAllWarehouses");
                            store.commit("auth/updateWarehouse", response.user.warehouse);
                            router.push({
                                name: "admin.dashboard.index",
                                params: { success: true },
                            });
                        }
                    }
                },
                error: (err) => {
                    onRequestSend.value = {
                        error: err.error.message ? err.error.message : "",
                        success: false,
                    };
                },
            });
        };

        return {
            appType,
            isSubdomainModuleEnabled,
            showDemoCredentials,
            isMobile,
            isDesktop,
            loading,
            rules,
            credentials,
            onSubmit,
            onRequestSend,
            globalSetting,
            logoUrl,
            rightBgStyle,
            onResetPass,
            onResetClose,
            resetPassword,
            onReset,
            resetCredential,
            onResetRequest,
        };
    },
});
</script>

<style lang="less">
.login-main-container {
    background: linear-gradient(180deg, #f4f6fb 0%, #eef1f8 100%);
    height: 100vh;
    position: relative;
    contain: layout;
    overflow: hidden;
    display: flex;
    align-items: center;
}

.login-lang-switch {
    position: absolute;
    top: 20px;
    right: 24px;
    z-index: 10;
}

.main-container-div {
    width: 100%;
    height: auto;
    align-items: stretch;
}

.login-left-div {
    height: auto;
    display: flex;
    align-items: stretch;
    justify-content: center;
    padding: 32px 24px;
}

.login-right-col {
    display: flex;
    padding: 32px 32px 32px 8px;
}

.login-panel {
    width: 100%;
    max-width: 420px;
}

.login-div {
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
    background: #fff;
}

.login-div .ant-card-body {
    padding: 36px 32px 28px;
}

.login-logo {
    text-align: center;
    margin-bottom: 20px;
}

.login-img-logo {
    width: 160px;
    height: auto;
    max-height: 56px;
    object-fit: contain;
}

.login-heading {
    text-align: left;
    margin-bottom: 24px;
}

.login-heading-title {
    font-weight: 700;
    font-size: 26px;
    line-height: 1.2;
    margin: 0 0 6px;
    color: #0f172a;
}

.login-heading-sub {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.login-submit-item {
    margin-bottom: 8px;
    margin-top: 8px;
}

.login-btn {
    height: 46px;
    font-weight: 600;
    border-radius: 8px;
}

.login-link-item {
    margin-top: 8px;
    text-align: center;
    font-weight: 600;
}

.right-login-div {
    background: #0f172a;
    flex: 1;
    width: 100%;
    min-height: 100%;
    height: auto;
    position: relative;
    content-visibility: auto;
    border-radius: 16px;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}

.right-login-overlay {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 32px 40px 36px;
    background: linear-gradient(
        180deg,
        rgba(15, 23, 42, 0) 0%,
        rgba(15, 23, 42, 0.72) 55%,
        rgba(15, 23, 42, 0.9) 100%
    );
    color: #fff;
}

.right-login-kicker {
    letter-spacing: 0.16em;
    font-size: 12px;
    font-weight: 700;
    margin: 0 0 8px;
    opacity: 0.75;
}

.right-login-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #fff;
}

.right-login-copy {
    margin: 0;
    max-width: 420px;
    font-size: 15px;
    opacity: 0.85;
}

@media (max-width: 991px) {
    .login-lang-switch {
        right: 16px;
        top: 12px;
    }

    .login-left-div {
        padding: 72px 16px 24px;
        align-items: flex-start;
    }

    .login-div .ant-card-body {
        padding: 28px 20px 20px;
    }
}
</style>
