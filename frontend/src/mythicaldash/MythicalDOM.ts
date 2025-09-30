import { useSettingsStore } from '@/stores/settings';
import { onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
export class MythicalDOM {
    /**
     * Sets the page title
     * @param title The title to set for the page
     */
    public static setPageTitle(title: string): void {
        const Settings = useSettingsStore();
        document.title = 'MythicalDash ' + ' - ' + title;
        this.addFavicon('https://github.com/mythicalltd.png');
        const updateTitle = () => {
            document.title = Settings.getSetting('app_name') + ' - ' + title;
            this.addFavicon(Settings.getSetting('app_logo'));
        };
        const titleInterval = setInterval(updateTitle, 5000);
        // Cleanup interval when component unmounts
        onUnmounted(() => {
            clearInterval(titleInterval);
        });
    }

    /**
     * Sets the favicon for the page
     * @param iconUrl URL or path to the favicon image
     * @param type Optional MIME type of the favicon (default: 'image/x-icon')
     */
    public static setFavicon(iconUrl: string, type: string = 'image/x-icon'): void {
        const link: HTMLLinkElement = document.querySelector("link[rel*='icon']") || document.createElement('link');
        link.type = type;
        link.rel = 'shortcut icon';
        link.href = iconUrl;
        const head = document.getElementsByTagName('head')[0];
        if (head) {
            head.appendChild(link);
        }
    }

    /**
     * Sets the theme color meta tag for mobile browsers
     * @param color Color in hex format (e.g., '#ff0000')
     */
    public static setThemeColor(color: string): void {
        const meta: HTMLMetaElement =
            document.querySelector("meta[name='theme-color']") || document.createElement('meta');
        meta.name = 'theme-color';
        meta.content = color;
        const head = document.getElementsByTagName('head')[0];
        if (head) {
            head.appendChild(meta);
        }
    }

    /**
     * Adds a meta tag to the document head
     * @param name Name of the meta tag
     * @param content Content of the meta tag
     */
    public static setMetaTag(name: string, content: string): void {
        const meta: HTMLMetaElement = document.querySelector(`meta[name='${name}']`) || document.createElement('meta');
        meta.name = name;
        meta.content = content;
        const head = document.getElementsByTagName('head')[0];
        if (head) {
            head.appendChild(meta);
        }
    }

    /**
     * Sets the Open Graph meta tags for social media sharing
     * @param config Object containing Open Graph properties
     */
    public static setOpenGraphTags(config: {
        title?: string;
        description?: string;
        image?: string;
        url?: string;
        type?: string;
    }): void {
        Object.entries(config).forEach(([key, value]) => {
            if (value) {
                const meta: HTMLMetaElement =
                    document.querySelector(`meta[property='og:${key}']`) || document.createElement('meta');
                meta.setAttribute('property', `og:${key}`);
                meta.content = value;
                const head = document.getElementsByTagName('head')[0];
                if (head) {
                    head.appendChild(meta);
                }
            }
        });
    }
    public static addFavicon(iconUrl: string, type: string = 'image/x-icon'): void {
        const link: HTMLLinkElement = document.querySelector("link[rel*='icon']") || document.createElement('link');
        link.type = type;
        link.rel = 'shortcut icon';
        link.href = iconUrl;
        const head = document.getElementsByTagName('head')[0];
        if (head) {
            head.appendChild(link);
        }
    }
    /**
     * Checks if the page is currently visible to the user
     * @returns boolean indicating if the page is visible
     */
    public static isPageVisible(): boolean {
        return !document.hidden;
    }

    /**
     * Adds a callback for when the page visibility changes
     * @param callback Function to be called when visibility changes
     */
    public static onVisibilityChange(callback: (isVisible: boolean) => void): void {
        document.addEventListener('visibilitychange', () => {
            callback(!document.hidden);
        });
    }

    public static getTranslation(key: string): string {
        const { t } = useI18n();
        return t(key);
    }
}
