import { reducedMotion } from '@js/lib/gsap';

/**
 * Accordion — animates native <details> items and keeps one open at a time.
 *
 * The markup works without this: <details> opens and closes on its own, with
 * the right keyboard and screen-reader behaviour. This only adds the height
 * transition (via the Web Animations API, so it works everywhere and needs no
 * CSS support for animating to `auto`) and closes the siblings.
 */

const DURATION = 400;
const EASING = 'cubic-bezier(.2, .8, .2, 1)';

export default class Accordion {
    constructor($el) {
        this.$$items = Array.from($el.querySelectorAll('.js-accordionItem'));

        this.$$items.forEach(($item) => {
            $item.querySelector('summary')?.addEventListener('click', (event) => {
                event.preventDefault();
                this.toggle($item);
            });
        });
    }

    toggle($item) {
        if ($item.open && !$item.dataset.closing) {
            this.close($item);
            return;
        }

        this.$$items.forEach(($other) => {
            if ($other !== $item && $other.open) {
                this.close($other);
            }
        });

        this.open($item);
    }

    content($item) {
        return $item.querySelector('.js-accordionItemContent');
    }

    open($item) {
        const $content = this.content($item);

        delete $item.dataset.closing;
        $content.getAnimations().forEach((animation) => animation.cancel());
        $item.open = true;

        if (reducedMotion.matches) {
            return;
        }

        const height = $content.scrollHeight;

        $content.animate(
            [
                { height: '0px', opacity: 0 },
                { height: `${height}px`, opacity: 1 },
            ],
            { duration: DURATION, easing: EASING },
        );
    }

    close($item) {
        const $content = this.content($item);

        $content.getAnimations().forEach((animation) => animation.cancel());

        if (reducedMotion.matches) {
            $item.open = false;
            return;
        }

        $item.dataset.closing = '';

        const animation = $content.animate(
            [
                { height: `${$content.scrollHeight}px`, opacity: 1 },
                { height: '0px', opacity: 0 },
            ],
            { duration: DURATION, easing: EASING },
        );

        animation.onfinish = () => {
            // Only close if a re-open did not cancel this animation meanwhile.
            if ('closing' in $item.dataset) {
                delete $item.dataset.closing;
                $item.open = false;
            }
        };
    }
}
