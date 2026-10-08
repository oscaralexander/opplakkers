/**
 * App — binds components to elements by class name.
 *
 * A binding is { name, component, selector?, options? }. Every element
 * matching `.js-<name>` (or `selector`) gets one instance of `component`,
 * stored on the element as `el.__APP__[name]` so it is never bound twice.
 *
 * `component` is either a class, or a function returning a Promise of a
 * module whose default export is the class — `() => import('./X')` — in
 * which case the code is only fetched once an element actually needs it.
 *
 * A MutationObserver binds components inside any markup added later (an
 * AJAX-loaded fragment, a lazily rendered embed). It walks only the nodes
 * that were added, not the whole subtree of their parents.
 */
export default class App {
    constructor(bindings = []) {
        this.bindings = bindings.map((binding) => ({
            ...binding,
            selector: binding.selector ?? `.js-${binding.name}`,
            resolved: null,
        }));

        this.bindAll(document);
        this.observe();
    }

    /**
     * Resolves a binding's component class, loading it on first use.
     *
     * @returns {Promise<Function>}
     */
    resolve(binding) {
        if (!binding.resolved) {
            const isLoader = typeof binding.component === 'function' && !binding.component.prototype;

            binding.resolved = isLoader
                ? binding.component().then((module) => module.default ?? module)
                : Promise.resolve(binding.component);
        }

        return binding.resolved;
    }

    bind($root, binding) {
        const $$targets = [];

        if ($root instanceof Element && $root.matches(binding.selector)) {
            $$targets.push($root);
        }

        $$targets.push(...$root.querySelectorAll(binding.selector));

        $$targets.forEach(($el) => {
            $el.__APP__ ||= {};

            if ($el.__APP__[binding.name] !== undefined) {
                return;
            }

            // Reserve the slot synchronously so a second pass (a mutation
            // arriving while the module is still loading) does not bind twice.
            $el.__APP__[binding.name] = null;

            this.resolve(binding).then((Component) => {
                if ($el.isConnected) {
                    $el.__APP__[binding.name] = new Component($el, binding.options ?? {});
                }
            });
        });
    }

    bindAll($root) {
        this.bindings.forEach((binding) => this.bind($root, binding));
    }

    observe() {
        const observer = new MutationObserver((mutations) => {
            const targets = [];

            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) {
                        this.bindAll(node);
                        targets.push(node);
                    }
                });
            });

            if (targets.length) {
                document.body.dispatchEvent(new CustomEvent('domchanged', { detail: { targets } }));
            }
        });

        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
}
