define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/str',
    'core/fragment', 'core/templates', 'core/notification', 'core/loadingicon', 'core/sortable_list'],
    function ($, Modal, ModalEvents, Str, Fragment, Templates, Notification, LoadingIcon, SortableList) {

    /* global slides */
    var slidesData = typeof slides !== 'undefined' ? slides : {};

    let contextID;

    let cmID;

    let loaderItem = '.slides-content';

    let root = '.nct-mod-slides-content';

    const SELECTORS = {
        root: '.nct-mod-slides-content',
        addSlide: root + ' .slides-addslide',
        slideItem: '.slide-item',
        sortList: root + ' .slides-slide-list',
        sortItem: root + ' .nctslides-slide-parent',
        moveHandlerSelector: '.move-slide',
        spinnerParent: '.loading-icon-container',
        editForm: {
            form: '#page-mod-slides-slide form.mform',
            headingFont: '#id_options_fontstyle_headingfont',
            contentFont: '#id_options_fontstyle_contentfont'
        }
    };

    const slideEditor = (contextid, cmid) => {
        if (document.body.id !== 'page-mod-slides-editor') {
            return null;
        }

        contextID = contextid;
        cmID = cmid;
        initEventListeners();

        return null;
    };

    const initEventListeners = () => {

        // Click on body delegate to handle click on slide actions.
        document.body.addEventListener('click', (e) => {

            var addSlide = e.target.closest(SELECTORS.addSlide);

            var slideAction = e.target.closest(".slide-item .slide-actions .action-item");
            var slideItem = e.target.closest("div.slide-item");

            if (slideAction && slideAction != undefined && slideItem && slideItem != undefined) {

                var action = slideAction.getAttribute('data-action');

                // var elementId = moduleElement.getAttribute('data-elementid');
                var slide = slideItem.getAttribute('data-slideshortname');
                var slideinstanceid = slideItem.getAttribute('data-slideinstanceid');
                if (action === 'delete') {
                    e.preventDefault();
                    // Deleting requires confirmation.
                    confirmDeleteElement(slide, function () {
                        deleteSlide(slideItem, slide, slideinstanceid, action);
                    });
                }

                /* if (action == 'moveup' || action == 'movedown') {
                    moveElement(moduleElement, action);
                } */

                if (action == 'status') {
                    updateStatus(slideItem);
                }
            }

            if (addSlide && addSlide != undefined) {
                e.preventDefault();
                showAddSlideModal();
            }

        });

        // Initialize sortable list to handle active conditions moving (note JQuery dependency, see MDL-72293 for resolution).
        console.log(SELECTORS.sortList);

        var activeConditionsSortableList = new SortableList(SELECTORS.sortList, {
            isHorizontal: false,
            moveHandlerSelector: SELECTORS.moveHandlerSelector,
            /*  handle: '.nctslides-slide-parent',
             dragClass: 'nctslides-slide-parent-dragging',
             placeholder: 'nctslides-slide-parent-placeholder',
             onDrop: (event, ui) => {
                 console.log('drop');
             } */
        });

        // activeConditionsSortableList.getElementName = element => Promise.resolve(element.data('conditionName'));

        // Events
        $(document).on(SortableList.EVENTS.DRAGEND, SELECTORS.root, (event, info) => {
            const orderedElements = [];

            console.log(info);

            Array.from(info.targetList.children()).forEach((item) => {
                orderedElements.push(item.dataset.slideinstanceid);
            });

            if (orderedElements.length > 0) {
                updateSlidesOrder(orderedElements);
            }
        });
    };

    const updateSlidesOrder = (orderedElements) => {
        const params = {
            slides: orderedElements.join(','),
            cmid: cmID
        };
        // Loading icon.
        var spinnerParent = document.querySelector(SELECTORS.spinnerParent);
        var spinner = LoadingIcon.addIconToContainerWithPromise(spinnerParent);

        var promise = Fragment.loadFragment('mod_slides', 'update_slides_order', contextID, params);
        promise.done((html, js) => {
            spinner.resolve();
        });
        return promise;
    };


    const updateChapterElements = (chapter) => {
        let contents = [];
        var items = chapter.querySelectorAll('li.element-item > div.element-item');
        items.forEach((item) => {
            contents.push(item.dataset.contentid);
        });
        let params = {
            contents: contents.join(','),
            chapterid: chapter.dataset.id,
            cmid: Data.cm.id
        };
        var promise = Fragment.loadFragment('mod_slides', 'move_element', Data.contextid, params);
        promise.done((html, js) => {
            Templates.replaceNode('.slides-content', html, js);
        });

        return promise;
    };

    var updateStatus = (moduleElement) => {
        let statusElement = moduleElement.querySelector('[data-action="status"] > i');

        const visibility = moduleElement.dataset.visibility == 'true';

        var params = {
            slideshortname: moduleElement.dataset.slideshortname,
            slideinstanceid: moduleElement.dataset.slideinstanceid,
            status:  visibility == true ? false : true,
            cmid: cmID
        };

        if (visibility == true) {
            statusElement.classList.remove('fa-eye');
            statusElement.classList.add('fa-eye-slash');
            moduleElement.dataset.visibility = false;
            moduleElement.classList.add('text-muted');
            moduleElement.classList.add('disabled');
        } else {
            statusElement.classList.remove('fa-eye-slash');
            statusElement.classList.add('fa-eye');
            moduleElement.dataset.visibility = true;
            moduleElement.classList.remove('text-muted');
            moduleElement.classList.remove('disabled');
        }

        var promise = Fragment.loadFragment('mod_slides', 'update_visibility', contextID, params).then(() => {
            return true;
        });
        LoadingIcon.addIconToContainerRemoveOnCompletion(loaderItem, promise);
    };


    /**
     * Performs an action on a element (moving, deleting, duplicating, hiding, etc.)
     *
     * @param {JQuery} moduleElement activity element we perform action on
     * @param {Number} instanceId
     * @param {String} action Action of the current clicked element.
     */
    var deleteSlide = function (moduleElement, slideshortname, slideinstanceid, action) {
        var args = {
            cmid: cmID,
            action: action,
            slideshortname: slideshortname,
            slideinstanceid: slideinstanceid,
        };
        Fragment.loadFragment('mod_slides', 'delete_slide', contextID, args).then((html) => {
            moduleElement.parentNode.remove();
            return;
        }).fail(Notification.exception);
    };

    /**
     * Displays the delete confirmation to delete a module
     *
     * @param {String} slide
     * @param {Function} onconfirm function to execute on confirm
     */
    var confirmDeleteElement = function (slide, onconfirm) {
        var slideTypename = 'slidetype_' + slide;
        Str.get_string('pluginname', slideTypename).done(function () {
            var plugindata = {
                slide: slide
            };
            Str.get_strings([
                {key: 'confirm', component: 'core'},
                {key: 'deletechecktype', component: 'mod_slides', param: plugindata},
                {key: 'yes'},
                {key: 'no'}
            ]).done(function (s) {
                    Notification.confirm(s[0], s[1], s[2], s[3], onconfirm);
                }
            );
        });
    };

    /**
     * Show the list of available slides in the modal to insert.
     *
     * @param {String} position where the element need to insert.
     * @param {Boolean} chapter chapter id to insert element.
     * @returns {Object}
     */
    const showAddSlideModal = (position = "bottom", chapter = 0) => {

        var params = {cmid: slidesData.cm.id};

        return Modal.create({
            type: Modal.TYPE,
            title: Str.get_string('addslide', 'slides'),
            body: Fragment.loadFragment('mod_slides', 'get_slides_list', contextID, params),
            large: false,
        }).then(modal => {
            modal.getRoot().on(ModalEvents.bodyRendered, function () {
                modal.getRoot().get(0).querySelectorAll(SELECTORS.slideItem).forEach((e) => {
                    e.addEventListener('click', function (e) {
                        if (e.target.closest(SELECTORS.slideItem)) {
                            var slideType = e.currentTarget.dataset.slidetype;

                            var params = {
                                cmid: cmID,
                                slidetype: slideType,
                                sesskey: M.cfg.sesskey
                            };
                            const urlParams = new URLSearchParams(params);
                            window.location = M.cfg.wwwroot + '/mod/slides/slide.php?' + urlParams.toString();
                        }
                    });
                });
            });
            modal.show();
            return modal;
        });
    };

    return {
        init: function (contextid, cmid) {
            return slideEditor(contextid, cmid);
        },

        loadFontExample: function (demoSelectors) {
            var form = document.querySelector(SELECTORS.editForm.form);
            if (form === null) {
                return false;
            }

            var url = "https://fonts.googleapis.com/css2?family=";

            const updateFamily = (e, styleRule) => {
                var fontFamily = e.value;
                var fontFamilyURL = fontFamily.replace(' ', '+');
                var importURL = url + fontFamilyURL;
                var style = document.createElement('style');
                var cstyle = '@import url("' + importURL + '");';
                cstyle += styleRule + '{'
                    + 'font-family: "' + fontFamily + '", serif;'
                    + 'font-optical-sizing: auto;}';

                style.innerHTML = cstyle;

                document.head.append(style);
            };

            /* var headingFontSelector = form.querySelector(SELECTORS.editForm.headingFont);
            if (headingFontSelector !== null) {
                headingFontSelector.onchange = (e) => {
                    updateFamily(e.target, '.slides-heading-style');
                };

                updateFamily(headingFontSelector, '.slides-heading-style');
            } */

            demoSelectors.forEach((id) => {
                var fontSelector = form.querySelector(id);
                if (fontSelector !== null) {
                    fontSelector.onchange = (e) => {
                        updateFamily(e.target, '.slides-fontstyle-demo[data-target="' + id + '"]');
                    };
                    updateFamily(fontSelector, '.slides-fontstyle-demo[data-target="' + id + '"]');
                }
            });

            return true;
        }
    };
});
