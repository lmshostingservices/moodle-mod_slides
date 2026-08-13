
const root = '#mod-nct-slides';

export const SELECTORS = {
    root: root,
    slideItem: root + ' .slides-list .slide-item',
    clickToView: root + ' .clicktoview',
    listenItem: '.listen-content-item',
    hideItem: '.listen-content-item.hide',
    listenItems: '.slide-listen-contents',
    listenContentImage: '[data-target="listencontentimage"]',
    currentItem: '.listen-content-item[data-currentitem="true"]:not(.viewed)',
    courseIndex: '#theme_boost-drawers-courseindex',
    carouselInner: root + ' .slides-list .carousel-inner',
    carousel: '.nct-slides-carousel.carousel',
    activeSlide: '.active.carousel-item',
    slideItemElem: '.slide-item',
    nextArrow: root + ' .carousel-control-next',
    indicators: '.carousel-indicators',
    slideAutoFontParent: '[data-target="slideautofontparent"]',
    hideCompletionFont: '[data-target="completion-autofont"]',
    autoFontRemoveContent: '[data-target="notavailable"]',
    forceNext: '[data-slide-target="forcenextbtn"]',
    forcePrev: '[data-slide-target="forceprevbtn"]',
    finishButton: '[data-slide-target="finishbtn"]',
    activityRegion: '.activity-header[data-for="page-activity-header"]',
    editForm: {
        form: '#page-mod-slides-slide form.mform',
        headingFont: '#id_fontstyle_headingfont',
        contentFont: '#id_fontstyle_contentfont'
    },
    others: {
        audioPlayed: 'nctslides-audio-playing',
        forceListen: 'nctslides-force-listen',
        nextSlideAvailable: 'next-slide-available',
        autoResized: 'font-auto-resized',
        clickToViewAvailable: 'click-to-view-box-available'
    }
};

export const forceListen = {
    none: 0,
    audio: 1,
    duration: 2,
};

export default {
    SELECTORS: SELECTORS,
    forceListen: forceListen
}
