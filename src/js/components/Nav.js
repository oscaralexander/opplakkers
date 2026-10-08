/**
 * Nav — the off-canvas menu toggle, and the bar's response to scrolling.
 *
 * Three classes are set on the nav:
 *   .is-scrolled — the page has left the top (the bar gets its shadow);
 *   .is-docked   — the page has scrolled past --nav-offset-y; on desktop the
 *                  floating bar docks full-width against the top edge and
 *                  stays put;
 *   .is-hidden   — the reader is scrolling down (the bar slides away) and
 *                  comes back the moment they scroll up. Never on a docked
 *                  desktop bar.
 *
 * "Left the top" and "past the offset" are answered by IntersectionObservers
 * on sentinels pinned to the top of the document rather than a scroll
 * listener, so they cost nothing per frame. Scroll direction genuinely needs scroll events; those are
 * passive, coalesced to one per frame, and only touch the DOM on a change.
 */

const THRESHOLD = 16; // px scrolled before the bar counts as "scrolled"
const HIDE_AFTER = 160; // px from the top before hiding is allowed
const DIRECTION_SLACK = 6; // px of movement before a direction flip registers

// Matches screen-min(m) in the stylesheets.
const desktop = window.matchMedia('(min-width: 1024px)');

export default class Nav {
    constructor($el) {
        this.$el = $el;
        this.$toggle = $el.querySelector('.js-navMenuToggle');
        this.$overlay = $el.querySelector('.js-navMenuOverlay');
        this.$menu = $el.querySelector('#navMenu');

        this.lastY = window.scrollY;
        this.ticking = false;

        this.initMenu();
        this.initSentinels();
        this.initScroll();
    }

    get isMenuOpen() {
        return this.$toggle?.getAttribute('aria-expanded') === 'true';
    }

    setMenu(open) {
        this.$toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            this.$el.classList.remove('is-hidden');
        }
    }

    initMenu() {
        if (!this.$toggle) {
            return;
        }

        this.$toggle.addEventListener('click', () => this.setMenu(!this.isMenuOpen));
        this.$overlay?.addEventListener('click', () => this.setMenu(false));

        // Following a link on the same page (an anchor) should close the menu.
        this.$menu?.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                this.setMenu(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.isMenuOpen) {
                this.setMenu(false);
                this.$toggle.focus();
            }
        });

        // Resizing past the breakpoint swaps to the horizontal menu; make
        // sure the off-canvas state does not linger.
        desktop.addEventListener('change', (event) => {
            if (event.matches) {
                this.setMenu(false);
            }
        });
    }

    initSentinels() {
        this.observeSentinel(`${THRESHOLD}px`, (isAtTop) => {
            this.$el.classList.toggle('is-scrolled', !isAtTop);

            if (isAtTop) {
                this.$el.classList.remove('is-hidden');
            }
        });

        // At least a pixel tall, so a zero offset still docks on the first
        // pixel scrolled.
        this.observeSentinel('max(1px, var(--nav-offset-y))', (isAtTop) => {
            this.$el.classList.toggle('is-docked', !isAtTop);
        });
    }

    /**
     * Calls `callback(isAtTop)` whenever the page scrolls past `height`.
     */
    observeSentinel(height, callback) {
        // Pinned to the top of the document rather than the viewport, so it
        // scrolls away with the page. Built here rather than in the template
        // because it is machinery, not content. One pixel wide, no background:
        // it paints nothing, but is not display:none, which would stop the
        // observer reporting it.
        const $sentinel = document.createElement('div');

        Object.assign($sentinel.style, {
            height,
            left: '0',
            pointerEvents: 'none',
            position: 'absolute',
            top: '0',
            width: '1px',
        });

        $sentinel.setAttribute('aria-hidden', 'true');
        document.body.prepend($sentinel);

        new IntersectionObserver(([entry]) => callback(entry.isIntersecting), { threshold: 0 }).observe($sentinel);
    }

    initScroll() {
        window.addEventListener(
            'scroll',
            () => {
                if (this.ticking) {
                    return;
                }

                this.ticking = true;

                requestAnimationFrame(() => {
                    this.updateDirection();
                    this.ticking = false;
                });
            },
            { passive: true },
        );
    }

    updateDirection() {
        const y = window.scrollY;
        const delta = y - this.lastY;

        if (Math.abs(delta) < DIRECTION_SLACK) {
            return;
        }

        this.lastY = y;

        if (this.isMenuOpen) {
            return;
        }

        const isSticky = desktop.matches && this.$el.classList.contains('is-docked');
        const hide = delta > 0 && y > HIDE_AFTER && !isSticky;
        this.$el.classList.toggle('is-hidden', hide);
    }
}
