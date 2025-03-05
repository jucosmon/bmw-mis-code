export class ScrollManager {
    getScrollRegions(element) {
        if (!element || !(element instanceof Element)) {
            console.warn('Invalid element provided to getScrollRegions');
            return [];
        }

        try {
            return Array.from(element.querySelectorAll('[scroll-region]')).filter(
                region => region instanceof HTMLElement
            );
        } catch (error) {
            console.warn('Error getting scroll regions:', error);
            return [];
        }
    }

    scrollToTop() {
        window.scrollTo(0, 0);
    }

    scrollRegionsToTop(element) {
        const regions = this.getScrollRegions(element);
        regions.forEach(region => {
            region.scrollTop = 0;
        });
    }
}
