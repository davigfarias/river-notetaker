import { nextTabIndex } from './navigation.js';

const TAB = '[data-flux-tab]:not([data-action])';
const PANEL = '[data-flux-tab-panel]';

/**
 * @param {{ findable: boolean }} config
 */
export function xTabs(config) {
    return {
        selected: null,
        model: null,
        live: false,

        init() {
            const tabs = this.$el.querySelector('[data-flux-tabs]');

            this.model = tabs?.dataset.model || null;
            this.live = tabs?.dataset.live === 'true';
            this.nameUnnamed(this.$el.querySelectorAll(TAB));
            this.nameUnnamed(this.$el.querySelectorAll(PANEL));
            this.selected = this.initialSelection();

            if (this.model && this.$wire) {
                this.$wire.$watch(this.model, (value) => {
                    this.selected = value ?? this.selected;
                });
            }
        },

        // Tabs and panels without a `name` pair up by position.
        nameUnnamed(elements) {
            elements.forEach((element, index) => {
                element.dataset.name ||= String(index);
            });
        },

        initialSelection() {
            const bound = this.model && this.$wire ? this.$wire.get(this.model) : null;
            const marked = this.$el.querySelector(`${TAB}[data-initial]:not(:disabled), ${PANEL}[data-initial]`);
            const first = this.$el.querySelector(`${TAB}:not(:disabled)`);

            return bound ?? marked?.dataset.name ?? first?.dataset.name ?? null;
        },

        select(name) {
            this.selected = name;

            if (this.model && this.$wire) {
                this.$wire.set(this.model, name, this.live);
            }
        },

        isSelected(element) {
            return this.selected === element.dataset.name;
        },

        syncPanel(element) {
            if (this.isSelected(element)) {
                element.removeAttribute('hidden');
                return;
            }

            element.setAttribute('hidden', config.findable ? 'until-found' : 'hidden');
        },

        onKeydown(event) {
            const tabs = [...this.$el.querySelectorAll(TAB)];
            const current = tabs.findIndex((tab) => tab === document.activeElement);
            const disabled = tabs.flatMap((tab, index) => (tab.disabled ? [index] : []));
            const target = nextTabIndex({ count: tabs.length, current, key: event.key, disabled });

            if (target === null) {
                return;
            }

            event.preventDefault();
            tabs[target].focus();
            this.select(tabs[target].dataset.name);
        },
    };
}
