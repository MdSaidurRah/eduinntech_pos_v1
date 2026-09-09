<template>
    <div class="lang-switch" :class="{ 'lang-switch--light': light }">
        <button
            type="button"
            class="lang-switch__btn"
            :class="{ 'is-active': currentLang === 'bn' }"
            @click="switchLang('bn')"
        >
            BN
        </button>
        <button
            type="button"
            class="lang-switch__btn"
            :class="{ 'is-active': currentLang === 'en' }"
            @click="switchLang('en')"
        >
            EN
        </button>
    </div>
</template>

<script>
import { defineComponent } from "vue";
import useLanguage from "../composable/language";

export default defineComponent({
    name: "LanguageSwitch",
    props: {
        light: {
            type: Boolean,
            default: false,
        },
    },
    setup() {
        const { currentLang, switchLang } = useLanguage();

        return {
            currentLang,
            switchLang,
        };
    },
});
</script>

<style lang="less" scoped>
.lang-switch {
    display: inline-flex;
    align-items: center;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
    vertical-align: middle;
}

.lang-switch__btn {
    border: 0;
    background: transparent;
    padding: 2px 10px;
    font-size: 12px;
    font-weight: 700;
    line-height: 22px;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.65);
    min-width: 40px;
}

.lang-switch__btn + .lang-switch__btn {
    border-left: 1px solid #d9d9d9;
}

.lang-switch__btn.is-active {
    background: #1890ff;
    color: #fff;
}

.lang-switch--light {
    border-color: rgba(255, 255, 255, 0.45);
    background: rgba(255, 255, 255, 0.12);
}

.lang-switch--light .lang-switch__btn {
    color: #fff;
}

.lang-switch--light .lang-switch__btn + .lang-switch__btn {
    border-left-color: rgba(255, 255, 255, 0.35);
}

.lang-switch--light .lang-switch__btn.is-active {
    background: #fff;
    color: #1890ff;
}
</style>
