import { nextTick } from 'vue';
import { createI18n } from 'vue-i18n';

export function setupI18n(options = { locale: 'en', warnHtmlMessage: false }) {
    const i18n = createI18n(options)
    setI18nLanguage(i18n, options.locale)
    return i18n
}

export function setI18nLanguage(i18n, locale) {
    if (i18n.mode === 'legacy') {
        i18n.global.locale = locale
    } else {
        i18n.global.locale.value = locale
    }
    /**
     * NOTE:
     * If you need to specify the language setting for headers, such as the `fetch` API, set it here.
     * The following is an example for axios.
     *
     * axios.defaults.headers.common['Accept-Language'] = locale
     */
    document.querySelector('html').setAttribute('lang', locale)
}

export async function loadLocaleMessages(i18n, locale) {
    const res = await axiosBase.get('lang-trans');

    setLangsLocaleMessage(res, i18n, locale);
}

export function setLangsLocaleMessage(res, i18n, locale) {
    const messages = {};

    res.data.data.map((lang) => {
        messages[lang.key] = {};

        lang.translations.map((translation) => {
            if (messages[lang.key][translation.group] == undefined) {
                messages[lang.key][translation.group] = {};
            }
            messages[lang.key][translation.group][translation.key] = translation.value;
        });
    });

    const fallbacks = {
        common: {
            load_more: "Load more",
        },
        stock: {
            save_draft: "Save Draft",
            draft: "Draft",
            drafts: "Drafts",
            draft_saved: "Draft saved successfully",
            recall_draft: "Recall",
            no_drafts: "No draft sales found",
            draft_recalled: "Draft loaded successfully",
            delete_draft: "Delete Draft",
            delete_draft_message: "Are you sure you want to delete this draft?",
            insufficient_stock: "Insufficient stock to complete this order.",
            insufficient_stock_item: "{0} — available {1}, required {2}",
        },
        payments: {
            add_and_complete: "Add and Complete",
            walk_in_zero_payment:
                "Walk In Customer cannot complete order with 0 payment. Please add a payment first.",
        },
    };

    const localeMessages = messages[locale] || {};
    const merged = { ...fallbacks };
    Object.keys(localeMessages).forEach((group) => {
        merged[group] = {
            ...(fallbacks[group] || {}),
            ...localeMessages[group],
        };
    });

    i18n.global.setLocaleMessage(locale, merged);

    return nextTick()
}
