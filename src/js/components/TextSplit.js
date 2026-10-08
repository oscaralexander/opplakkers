import { gsap, SplitText, reducedMotion } from '@js/lib/gsap';

/**
 * TextSplit — a headline whose words rise into place, line by line, as it
 * scrolls into view.
 *
 * GSAP's SplitText does the splitting. Two of its features carry the whole
 * effect: `mask: 'lines'` wraps each line in an overflow-hidden element, so
 * the words can slide up from below it without clipping descenders the way a
 * per-word mask would; and `autoSplit` re-splits when the element's width
 * changes or when web fonts finish loading, so the lines always match what
 * the reader sees.
 *
 * The entrance only plays once. After that a re-split (a resize) rebuilds the
 * lines without animating, so a rotated phone never replays the headline.
 */

const DURATION = 0.9;
const STAGGER = 0.035;

// autoSplit re-splits whenever the element's width changes. If a layout makes
// the width depend on the split itself (a shrink-to-fit heading, say), that
// becomes a loop — after this many splits in one second we give the heading
// back untouched rather than freeze the page.
const MAX_SPLITS_PER_SECOND = 6;

export default class TextSplit {
    constructor($el) {
        this.$el = $el;
        this.played = false;
        this.splitTimes = [];

        if (!$el.textContent.trim() || reducedMotion.matches) {
            return;
        }

        $el.classList.add('textSplit');

        // Split only once the fonts are in; SplitText would re-split anyway,
        // but a first split in the fallback font can flash the wrong wrap.
        document.fonts.ready.then(() => this.split());
    }

    split() {
        this.splitText = SplitText.create(this.$el, {
            type: 'lines,words',
            mask: 'lines',
            autoSplit: true,
            linesClass: 'line',
            wordsClass: 'word',
            onSplit: (self) => this.animate(self),
        });
    }

    /**
     * Returns the tween so SplitText can revert it before the next re-split.
     */
    animate(self) {
        const now = performance.now();
        this.splitTimes = this.splitTimes.filter((time) => now - time < 1000);
        this.splitTimes.push(now);

        if (this.splitTimes.length > MAX_SPLITS_PER_SECOND) {
            console.warn('TextSplit: re-split loop detected, reverting', this.$el);
            // Revert asynchronously: we are inside SplitText's own split call.
            setTimeout(() => self.revert());
            return null;
        }

        if (this.played) {
            this.finish();
            return null;
        }

        return gsap.from(self.words, {
            yPercent: 110,
            duration: DURATION,
            ease: 'power3.out',
            stagger: STAGGER,
            scrollTrigger: {
                trigger: this.$el,
                start: 'top 92%',
                once: true,
                onEnter: () => {
                    this.played = true;
                },
            },
            onComplete: () => this.finish(),
        });
    }

    /**
     * Once the words are in place the line masks have done their job. They
     * keep clipping to the line box, which at a display line-height cuts off
     * descenders — so lift the clipping (see text-split.scss).
     */
    finish() {
        this.$el.classList.add('is-textSplitDone');
    }
}
