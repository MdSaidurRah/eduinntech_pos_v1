import { computed } from "vue";
import { useStore } from "vuex";
import { useI18n } from "vue-i18n";
import { loadLocaleMessages, setI18nLanguage } from "../i18n";

export default function useLanguage() {
    const store = useStore();
    const { locale } = useI18n();
    const currentLang = computed(() => store.state.auth.lang || "en");

    const switchLang = async (lang) => {
        if (!lang || lang === currentLang.value) {
            return;
        }

        store.commit("auth/updateLang", lang);
        await loadLocaleMessages(window.i18n, lang);
        locale.value = lang;
        setI18nLanguage(window.i18n, lang);
    };

    return {
        currentLang,
        switchLang,
    };
}
