define("mod_slides/autosplit", ["exports", "jquery", "core/fragment", "core/loadingicon"], function (_exports, jQuery, _fragment, LoadingIcon) {
  "use strict";

  _exports.__esModule = true;
  _exports.init = void 0;
  jQuery = _interopRequireWildcard(jQuery);
  LoadingIcon = _interopRequireWildcard(LoadingIcon);
  function _interopRequireWildcard(e, t) { if ("function" == typeof WeakMap) var r = new WeakMap(), n = new WeakMap(); return (_interopRequireWildcard = function (e, t) { if (!t && e && e.__esModule) return e; var o, i, f = { __proto__: null, default: e }; if (null === e || "object" != typeof e && "function" != typeof e) return f; if (o = t ? n : r) { if (o.has(e)) return o.get(e); o.set(e, f); } for (const t in e) "default" !== t && {}.hasOwnProperty.call(e, t) && ((i = (o = Object.defineProperty) && Object.getOwnPropertyDescriptor(e, t)) && (i.get || i.set) ? o(f, t, i) : f[t] = e[t]); return f; })(e, t); }
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
      contentID: 'id_content_'
    }
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
    }
    updateSplitPreview() {
      const form = document.querySelector(SELECTORS.form);
      if (form === null) {
        return false;
      }
      const previewBtn = form.querySelector(SELECTORS.previewBtn);
      if (previewBtn === null) {
        return false;
      }
      const split_content = e => {
        e.preventDefault();
        let dynamicElem = document.querySelector(SELECTORS.dynamicEditor);
        let dynamicContent = tinyMCE.get(dynamicElem.id).getContent();
        let slidename = document.querySelector(SELECTORS.slide)?.value;
        (0, _fragment.loadFragment)('mod_slides', 'split_content', this.contextID, {
          content: dynamicContent,
          slidename: slidename
        }).then((html, js) => {
          html = JSON.parse(html);
          if (html == '' || html == null) {
            Notification.exception('EmptyContent');
            return false;
          }
          var previewSection = document.querySelector(previewSection);
          previewSection.innerHTML = html;
        }).catch(Notification.exception);
      };
      previewBtn.onclick = split_content;
    }
  }
  const init = (id, contextid) => {
    return new autoSplit(id, contextid);
  };
  _exports.init = init;
});