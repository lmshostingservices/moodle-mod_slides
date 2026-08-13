
import * as jQuery from 'jquery';
import {loadFragment} from 'core/fragment';
import * as LoadingIcon from 'core/loadingicon';

const SELECTORS = {
    form: '#page-mod-slides-autosplit form.mform',
    dynamicBtn: '#fitem_id_updatedynamic input[type=submit]#id_updatedynamic',
    dynamicEditor: '#fitem_id_dynamiccontent #id_dynamiccontent',
    slide: '#id_slide',
    contentEditors: '#id_contentsection [id^="fitem_id_content_"] textarea[id^="id_content_"]',
    contentSection: '#id_contentsection',
    titleEditor: '#fitem_id_title #id_title',
    previewSection: '.autosplit-preview-section',
    previewBtn: '#id_splitpreview',
    other: {
        contentID: 'id_content_',
    },
};

class autoSplit {

    constructor(id, contextid) {
        this.contextID = contextid;
        this.id = id;
        this.addEventListeners();
        this.updateSplitPreview();
    }

    addEventListeners() {
        var thisQ = this;
        /* document.querySelector(SELECTORS.previewBtn)?.addEventListener('click', function() {
            thisQ.updateSplitPreview();
        }); */
    }

    updateSplitPreview() {

        /* global tinyMCE; */
        const form = document.querySelector(SELECTORS.form);
        if (form === null) {
            return false;
        }
        const previewBtn = form.querySelector(SELECTORS.previewBtn);
        if (previewBtn === null) {
            return false;
        }
        const split_content = (e) => {
            e.preventDefault();

            // var spinner = LoadingIcon.addIconToContainerWithPromise(previewBtn.parentNode);

            let dynamicElem = document.querySelector(SELECTORS.dynamicEditor);
            let dynamicContent = tinyMCE.get(dynamicElem.id).getContent();
            let slidename = document.querySelector(SELECTORS.slide)?.value;

            loadFragment('mod_slides', 'split_content', this.contextID, { content: dynamicContent, slidename: slidename }).then((html, js) => {
                // spinner.resolve();
                html = JSON.parse(html);
                if (html == '' || html == null) {
                    // form.submit();
                    Notification.exception('EmptyContent');
                    return false;
                }

                var previewSection = document.querySelector(previewSection);
                previewSection.innerHTML = html;

                // let title = html.title !== null ? html.title : '';
                /* if (title) {
                    const titleElem = form.querySelector(SELECTORS.editForm.titleEditor);
                    tinyMCE.get(titleElem.id).setContent(title);
                }
                let contents = html.contents !== null ? html.contents : {}; */

                // console.log(contents);
               /*  if (Object.entries(contents).length >= 1) {
                    const contentEditors = form.querySelectorAll(SELECTORS.editForm.contentEditors);
                    contentEditors.forEach((e) => {
                        var index = e.id.replace(SELECTORS.editForm.other.contentID, '');
                        if (contents.hasOwnProperty(index)) {
                            tinyMCE.get(e.id).setContent(contents[index]);
                        } else {
                            tinyMCE.get(e.id).setContent("");
                        }
                    })
                } */
            }).catch(Notification.exception);
        };
        previewBtn.onclick = split_content;
    }


}


export const init = (id, contextid) => {
    return new autoSplit(id, contextid)
};
